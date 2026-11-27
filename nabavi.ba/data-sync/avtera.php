<?php
// Onemogući direktan pristup preko browsera radi sigurnosti
if (php_sapi_name() !== 'cli' && !isset($_GET['run_secret_key'])) {
    die('Zabranjen pristup.');
}

// Ako pokrećeš preko URL-a radi testa, postavi ključ
define('SECRET_KEY', 'eT4i6T9tELmd');
if (isset($_GET['run_secret_key']) && $_GET['run_secret_key'] !== SECRET_KEY) {
    die('Pogrešan ključ.');
}

// Učitaj WordPress jezgro da imamo pristup bazi i funkcijama
require_once(__DIR__ . '/wp-load.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');

// Postavi neograničeno vrijeme izvršavanja i memoriju
@set_time_limit(0);
@ini_set('memory_limit', '2048M');

global $wpdb;

echo "Započeto preuzimanje XML-a...\n";

$xml_url = 'https://www.avtera.ba/XmlExport/FPsE7MSNk7q9dX0IxUuaAxVcModVYOFZYHjGpqciyKdpF4vL3BbKeUGGu40cgLLm4bzYphzcVHDZlbOmEh9cWRUTobdk4WUY8wCS/QHa4rtMLWLXgGGW-dpUtbAJAFQrEgZ0qbX1WpeWpFgcvxBf5qi0n750h2PuhbPi8IYqRKOgz6y5OA5-6CU0Ku8DMcsipEJw5_mW7TYoGTix9T4FbrKn3fxjZY7YdQmPK/ZALAvteraNABAVId.o';

$response = wp_remote_get($xml_url, array('timeout' => 300));
if (is_wp_error($response)) {
    die("Greška pri preuzimanju XML-a.");
}

$xml_data = wp_remote_retrieve_body($response);
if (empty($xml_data)) {
    die("XML fajl je prazan.");
}

$xml = simplexml_load_string($xml_data);
if (!$xml) {
    die("Neuspješno parsiranje XML-a.");
}

echo "XML uspješno učitan. Pokrećem indeksiranje trenutnih proizvoda...\n";

// Brzi SQL upit: Pokupi sve proizvode koji imaju tag 'avtera.ba'
$existing_products = $wpdb->get_results("
    SELECT p.ID, p.post_title 
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
    INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
    INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
    WHERE p.post_type = 'product' AND t.slug = 'avtera-ba'
");

$wp_products_by_title = array();
foreach ($existing_products as $p) {
    $key = strtolower(trim($p->post_title));
    $wp_products_by_title[$key] = $p->ID;
}

$processed_product_ids = array();

echo "Sinhronizacija artikala u toku...\n";

foreach ($xml->xpath('.//izdelek') as $izdelek) {
    $ime = isset($izdelek->izdelekIme) ? trim((string)$izdelek->izdelekIme) : '';
    if (empty($ime)) continue;

    $price_raw = isset($izdelek->nabavnaCena) ? (string)$izdelek->nabavnaCena : '0';
    $stock_raw = isset($izdelek->zaloga) ? (string)$izdelek->zaloga : '0';

    $price = floatval(str_replace(',', '.', $price_raw));
    $stock = floatval($stock_raw);

    // Ako je zaliha ili cijena 0, preskačemo
    if ($stock <= 0 || $price <= 0) continue;

    // Matematika za cijenu
$final_price = ($price * 1.33784) + 24.33;
$final_price = round($final_price, 2);

    $match_key = strtolower($ime);

    if (array_key_exists($match_key, $wp_products_by_title)) {
        // --- UPDATE POSTOJEĆEG PROIZVODA PREKO WC_PRODUCT OBJEKTA ---
        $product_id = $wp_products_by_title[$match_key];
        $processed_product_ids[] = $product_id;

        $product = wc_get_product($product_id);
        
        if ($product) {
            $needs_save = false;

            // Provjera i ažuriranje cijene
            if (floatval($product->get_regular_price()) != $final_price) {
                $product->set_regular_price($final_price);
                $product->set_price($final_price);
                $needs_save = true;
            }

            // Provjera i ažuriranje zaliha
            if (floatval($product->get_stock_quantity()) != $stock) {
                $product->set_stock_quantity($stock);
                
                // Osiguraj da je status zalihe ispravan
                if ($stock > 0) {
                    $product->set_stock_status('instock');
                }
                $needs_save = true;
            }

            // Spasi izmjene samo ako se nešto stvarno promijenilo (štedi resurse)
            if ($needs_save) {
                $product->save();
                // Eksplicitno brišemo keš za ovaj proizvod
                wc_delete_product_transients($product_id);
            }
        }

    } else {
        // --- KREIRANJE NOVOG PROIZVODA ---
        $opis = isset($izdelek->opis) ? trim((string)$izdelek->opis) : '';
        $dodatne_lastnosti = array();
        if (isset($izdelek->dodatneLastnosti)) {
            foreach ($izdelek->dodatneLastnosti->lastnost as $lastnost) {
                $naziv = trim((string)$lastnost['naziv']);
                $vrijednost = trim((string)$lastnost);
                if ($naziv && $vrijednost) {
                    $dodatne_lastnosti[] = "{$naziv}: {$vrijednost}";
                }
            }
        }
        if (!empty($dodatne_lastnosti)) {
            $opis .= "\n\nSpecifikacije:\n" . implode("\n", $dodatne_lastnosti);
        }

        $category = isset($izdelek->skupinaIzdelka) ? trim((string)$izdelek->skupinaIzdelka) : '';
        $image_url = isset($izdelek->slikaVelika) ? trim((string)$izdelek->slikaVelika) : '';

        $product_id = wp_insert_post(array(
            'post_title'   => $ime,
            'post_content' => $opis,
            'post_status'  => 'publish',
            'post_type'    => "product",
        ));

        if (!is_wp_error($product_id)) {
            $processed_product_ids[] = $product_id;

            wp_set_object_terms($product_id, 'simple', 'product_type');
            update_post_meta($product_id, '_visibility', 'visible');
            update_post_meta($product_id, '_stock_status', 'instock');
            update_post_meta($product_id, '_manage_stock', 'yes');
            update_post_meta($product_id, '_regular_price', $final_price);
            update_post_meta($product_id, '_price', $final_price);
            
            // Postavljanje količine i brisanje keša za novi proizvod
            wc_update_product_stock($product_id, $stock);
            wc_delete_product_transients($product_id);

            // Dodaj tag 'avtera.ba'
            wp_set_object_terms($product_id, 'avtera.ba', 'product_tag', true);

            if (!empty($category)) {
                wp_set_object_terms($product_id, $category, 'product_cat', true);
            }

            // --- DOWNLOAD I DODAVANJE SLIKE ---
            if (!empty($image_url) && filter_var($image_url, FILTER_VALIDATE_URL)) {
                $attach_id = media_sideload_image($image_url, $product_id, $ime, 'id');
                if (!is_wp_error($attach_id) && is_numeric($attach_id)) {
                    set_post_thumbnail($product_id, $attach_id);
                }
            }
        }
    }
}

echo "Ažuriranje završeno. Pokrećem čišćenje nepostojećih artikala...\n";

// --- BRISANJE ARTIKALA KOJI NISU U XML-u ---
foreach ($wp_products_by_title as $key => $product_id) {
    if (!in_array($product_id, $processed_product_ids)) {
        wp_delete_post($product_id, true);
    }
}

echo "Sinhronizacija uspješno završena u " . date('Y-m-d H:i:s') . "\n";