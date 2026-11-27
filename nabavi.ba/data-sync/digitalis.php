<?php
// Onemogući direktan pristup preko browsera radi sigurnosti
if (php_sapi_name() !== 'cli' && !isset($_GET['run_secret_key'])) {
    die('Zabranjen pristup.');
}

// Tajni ključ za ručno pokretanje (isti kao za prošlu skriptu radi lakšeg pamćenja)
define('SECRET_KEY', 'eT4i6T9tELmd');
if (isset($_GET['run_secret_key']) && $_GET['run_secret_key'] !== SECRET_KEY) {
    die('Pogrešan ključ.');
}

// Učitaj WordPress jezgro i potrebne funkcije za medije
require_once(__DIR__ . '/wp-load.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');

// Neograničeno vrijeme izvršavanja i memorija za velike XML-ove
@set_time_limit(0);
@ini_set('memory_limit', '2048M');

global $wpdb;

echo "Započeto preuzimanje Digitalis XML-a...\n";

// !!! Ovdje unesi stvarni URL sa kojeg preuzimaš digitalis.xml !!!
$xml_url = 'https://www.digitalis.ba/webshop/parsedata/226.Aosf281f4GjMESohoOZ2k-ahr/?out&type=xml'; 

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

echo "XML uspješno učitan. Pokrećem indeksiranje trenutnih Digitalis proizvoda...\n";

// Pokupi sve proizvode sa sajta koji imaju tag 'digitalis.ba'
$existing_products = $wpdb->get_results("
    SELECT p.ID, p.post_title 
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
    INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
    INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
    WHERE p.post_type = 'product' AND t.slug = 'digitalis-ba'
");

$wp_products_by_title = array();
foreach ($existing_products as $p) {
    $key = strtolower(trim($p->post_title));
    $wp_products_by_title[$key] = $p->ID;
}

$processed_product_ids = array();

echo "Sinhronizacija Digitalis artikala u toku...\n";

foreach ($xml->xpath('.//proizvod') as $proizvod) {
    $ime = isset($proizvod->naziv) ? trim((string)$proizvod->naziv) : '';
    if (empty($ime)) continue;

    $price_raw = isset($proizvod->cijena) ? (string)$proizvod->cijena : '0';
    $stanje_text = isset($proizvod->stanje) ? strtoupper(trim((string)$proizvod->stanje)) : '';

    $price = floatval(str_replace(',', '.', $price_raw));
    
    // Stanje logika: DA -> 10, sve ostalo -> 0
    $stock = ($stanje_text === 'DA') ? 10 : 0;

    if ($price <= 0) continue;

    // Kalkulacija cijene po tvojoj novoj formuli
    $final_price = ($price * 1.33784) + 24.33;
    $final_price = round($final_price, 2);

    // === FILTER: Samo proizvodi 200 KM i više ===
    if ($final_price < 200) {
        continue;
    }

    // Ako proizvod ima stanje 0, a nemamo ga na sajtu, preskačemo dodavanje
    $match_key = strtolower($ime);
    if ($stock <= 0 && !array_key_exists($match_key, $wp_products_by_title)) {
        continue;
    }

    if (array_key_exists($match_key, $wp_products_by_title)) {
        // --- FORSIRANI UPDATE POSTOJEĆEG PROIZVODA ---
        $product_id = $wp_products_by_title[$match_key];
        $processed_product_ids[] = $product_id;

        $product = wc_get_product($product_id);
        if ($product) {
            $product->set_regular_price($final_price);
            $product->set_price($final_price);
            $product->set_stock_quantity($stock);
            $product->set_stock_status(($stock > 0) ? 'instock' : 'outofstock');
            
            $product->save();
            wc_delete_product_transients($product_id);
            wp_cache_delete($product_id, 'post_meta');
        }
    } else {
        // --- KREIRANJE NOVOG PROIZVODA ---
        $opis = isset($proizvod->opis) ? trim((string)$proizvod->opis) : '';
        
        // Specifikacije stavke
        $specifikacije_list = array();
        if (isset($proizvod->specifikacije)) {
            foreach ($proizvod->specifikacije->specifikacija as $spec) {
                $n = isset($spec->naziv) ? trim((string)$spec->naziv) : '';
                $v = isset($spec->vrijednost) ? trim((string)$spec->vrijednost) : '';
                if ($n && $v) {
                    $specifikacije_list[] = "{$n}: {$v}";
                }
            }
        }
        if (!empty($specifikacije_list)) {
            $opis .= "\n\nSpecifikacije:\n" . implode("\n", $specifikacije_list);
        }

        // Izdvajanje zadnje kategorije iz putanje
        $raw_category = isset($proizvod->kategorija_podkategorija) ? (string)$proizvod->kategorija_podkategorija : '';
        if (strpos($raw_category, ' / ') !== false) {
            $parts = explode(' / ', $raw_category);
            $category = trim(end($parts));
        } elseif (strpos($raw_category, ' > ') !== false) {
            $parts = explode(' > ', $raw_category);
            $category = trim(end($parts));
        } else {
            $category = trim($raw_category);
        }

        // Izvlačenje prve slike
        $image_url = '';
        if (isset($proizvod->slike) && isset($proizvod->slike->slika)) {
            $image_url = trim((string)$proizvod->slike->slika[0]);
        }

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
            update_post_meta($product_id, '_manage_stock', 'yes');
            update_post_meta($product_id, '_regular_price', $final_price);
            update_post_meta($product_id, '_price', $final_price);
            
            wc_update_product_stock($product_id, $stock);
            wc_delete_product_transients($product_id);

            // Dodaj tag 'digitalis.ba' radi razlikovanja od Avtere
            wp_set_object_terms($product_id, 'digitalis.ba', 'product_tag', true);

            if (!empty($category)) {
                wp_set_object_terms($product_id, $category, 'product_cat', true);
            }

            // Download i postavljanje prve slike
            if (!empty($image_url) && filter_var($image_url, FILTER_VALIDATE_URL)) {
                $attach_id = media_sideload_image($image_url, $product_id, $ime, 'id');
                if (!is_wp_error($attach_id) && is_numeric($attach_id)) {
                    set_post_thumbnail($product_id, $attach_id);
                }
            }
        }
    }
}

echo "Ažuriranje završeno. Pokrećem čišćenje nepostojećih Digitalis artikala...\n";

// --- BRISANJE ARTIKALA KOJI NISU VIŠE U XML-U ILI SU PALI ISPOD 200 KM / POSTALI DETEKTINO STANJE 0 ---
foreach ($wp_products_by_title as $key => $product_id) {
    if (!in_array($product_id, $processed_product_ids)) {
        wp_delete_post($product_id, true); // Briše direktno, preskače Trash
    }
}

echo "Digitalis sinhronizacija uspješno završena u " . date('Y-m-d H:i:s') . "\n";