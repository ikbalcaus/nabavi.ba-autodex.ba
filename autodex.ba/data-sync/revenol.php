<?php
// Onemogući direktan pristup preko browsera radi sigurnosti
if (php_sapi_name() !== 'cli' && !isset($_GET['run_secret_key'])) {
    die('Zabranjen pristup.');
}

// Ako pokrećeš preko URL-a radi testa, koristi isti ključ
define('SECRET_KEY', 'eT4i6T9tELmd');

if (
    isset($_GET['run_secret_key']) &&
    $_GET['run_secret_key'] !== SECRET_KEY
) {
    die('Pogrešan ključ.');
}

// Učitaj WordPress jezgro
require_once(__DIR__ . '/wp-load.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');

// Neograničeno vrijeme izvršavanja i memorija
@set_time_limit(0);
@ini_set('memory_limit', '2048M');

global $wpdb;

/*
|--------------------------------------------------------------------------
| POSTAVKE
|--------------------------------------------------------------------------
*/

// Direktna serverska putanja do CSV fajla
$csv_file = '/home/nabavi/htdocs/autodijelovi.nabavi.ba/wp-content/uploads/roba.csv';

// Fiksni podaci kao u Python skripti
$fiksna_slika = 'https://autodijelovi.nabavi.ba/wp-content/uploads/2026/05/viber_image_2026-05-18_19-17-21-131.webp';
$fiksni_brend = 'ravenol';

echo "Započeto čitanje CSV fajla...\n";

/*
|--------------------------------------------------------------------------
| PROVJERA CSV FAJLA
|--------------------------------------------------------------------------
*/

if (!file_exists($csv_file)) {
    die("CSV fajl ne postoji: {$csv_file}\n");
}

if (!is_readable($csv_file)) {
    die("CSV fajl nije dostupan za čitanje: {$csv_file}\n");
}

$handle = fopen($csv_file, 'rb');

if ($handle === false) {
    die("CSV fajl se ne može otvoriti.\n");
}

echo "CSV uspješno otvoren. Pokrećem indeksiranje trenutnih proizvoda...\n";

/*
|--------------------------------------------------------------------------
| INDEKSIRANJE POSTOJEĆIH PROIZVODA PO SKU-u
|--------------------------------------------------------------------------
|
| SIFRAROBE iz CSV-a koristi se kao WooCommerce SKU.
|
*/

$existing_products = $wpdb->get_results("
    SELECT
        p.ID,
        pm.meta_value AS sku
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm
        ON p.ID = pm.post_id
    WHERE p.post_type = 'product'
      AND p.post_status != 'trash'
      AND pm.meta_key = '_sku'
      AND pm.meta_value != ''
");

$wp_products_by_sku = array();

foreach ($existing_products as $p) {
    $sku = trim((string) $p->sku);

    if ($sku !== '') {
        $wp_products_by_sku[$sku] = (int) $p->ID;
    }
}

$processed_product_ids = array();

$created = 0;
$updated = 0;
$unchanged = 0;
$skipped = 0;
$errors = 0;
$line_number = 0;

echo "Sinhronizacija artikala u toku...\n";

/*
|--------------------------------------------------------------------------
| ČITANJE CSV REDOVA
|--------------------------------------------------------------------------
|
| Originalni CSV je odvojen tačka-zarezom.
|
| Format:
| SIFRAROBE;NAZIVROBE;MPC;ZALIHA;JMJ
|
| Naziv proizvoda može sadržavati dodatne tačka-zareze, zbog čega:
|
| prvi element      = SIFRAROBE
| posljednji        = JMJ
| pretposljednji    = ZALIHA
| treći od kraja    = MPC
| sve između        = NAZIVROBE
|
*/

while (($linija = fgets($handle)) !== false) {
    $line_number++;

    $linija = trim($linija);

    // Preskoči prazne redove
    if ($linija === '') {
        continue;
    }

    // Ukloni UTF-8 BOM sa početka prvog reda
    $linija = preg_replace('/^\xEF\xBB\xBF/', '', $linija);

    // Preskoči zaglavlje
    if (stripos($linija, 'SIFRAROBE') !== false) {
        continue;
    }

    // Razdvajanje po tačka-zarezu
    $dijelovi = str_getcsv($linija, ';', '"', '\\');

    if (count($dijelovi) < 5) {
        echo "Preskočen neispravan red {$line_number}.\n";
        $errors++;
        continue;
    }

    /*
     * Prva vrijednost je šifra.
     */
    $sifra = array_shift($dijelovi);
    $sifra = trim((string) $sifra);
    $sifra = trim($sifra, '"');

    /*
     * Posljednje tri vrijednosti su:
     * MPC, ZALIHA i JMJ.
     */
    $jmj = array_pop($dijelovi);
    $zaliha_raw = array_pop($dijelovi);
    $mpc_raw = array_pop($dijelovi);

    $jmj = trim((string) $jmj);
    $jmj = trim($jmj, '"');

    $zaliha_raw = trim((string) $zaliha_raw);
    $zaliha_raw = trim($zaliha_raw, '"');
    $zaliha_raw = str_replace(',', '.', $zaliha_raw);

    $mpc_raw = trim((string) $mpc_raw);
    $mpc_raw = trim($mpc_raw, '"');
    $mpc_raw = str_replace(',', '.', $mpc_raw);

    /*
     * Sve što je ostalo između šifre i posljednje tri kolone
     * predstavlja naziv proizvoda.
     */
    $naziv = implode(';', $dijelovi);
    $naziv = trim($naziv);
    $naziv = trim($naziv, '"');

    $mpc = floatval($mpc_raw);
    $zaliha = floatval($zaliha_raw);

    if ($sifra === '' || $naziv === '') {
        echo "Red {$line_number}: nedostaje šifra ili naziv.\n";
        $errors++;
        continue;
    }

    /*
     * Kao u ranijoj skripti:
     * proizvodi bez cijene se preskaču.
     *
     * Proizvodi sa zalihom 0 neće se preskočiti, nego će biti
     * postavljeni kao outofstock.
     */
    if ($mpc <= 0) {
        $skipped++;
        continue;
    }

    /*
     * Ako cijenu iz CSV-a treba dodatno obračunati,
     * matematiku dodaješ ovdje.
     *
     * Trenutno se koristi direktna MPC cijena.
     */
    $final_price = round($mpc, 2);

    /*
    Primjer dodatnog obračuna:

    $final_price = ($mpc * 1.33784) + 24.33;
    $final_price = round($final_price, 2);
    */

    /*
    |--------------------------------------------------------------------------
    | UPDATE POSTOJEĆEG PROIZVODA
    |--------------------------------------------------------------------------
    */

    if (array_key_exists($sifra, $wp_products_by_sku)) {
        $product_id = $wp_products_by_sku[$sifra];
        $processed_product_ids[] = $product_id;

        $product = wc_get_product($product_id);

        if (!$product) {
            echo "SKU {$sifra}: proizvod nije moguće učitati.\n";
            $errors++;
            continue;
        }

        $needs_save = false;

        /*
         * Ažuriranje naziva.
         */
        if ($product->get_name() !== $naziv) {
            $product->set_name($naziv);
            $needs_save = true;
        }

        /*
         * Ažuriranje cijene.
         */
        if (
            floatval($product->get_regular_price()) !==
            floatval($final_price)
        ) {
            $product->set_regular_price($final_price);
            $product->set_price($final_price);
            $needs_save = true;
        }

        /*
         * Uključi upravljanje zalihama.
         */
        if (!$product->get_manage_stock()) {
            $product->set_manage_stock(true);
            $needs_save = true;
        }

        /*
         * Ažuriranje zalihe.
         */
        if (
            floatval($product->get_stock_quantity()) !==
            floatval($zaliha)
        ) {
            $product->set_stock_quantity($zaliha);
            $needs_save = true;
        }

        /*
         * Status zalihe.
         */
        $stock_status = $zaliha > 0
            ? 'instock'
            : 'outofstock';

        if ($product->get_stock_status() !== $stock_status) {
            $product->set_stock_status($stock_status);
            $needs_save = true;
        }

        /*
         * Jedinica mjere kao lokalni atribut proizvoda.
         */
        if ($jmj !== '') {
            $attributes = $product->get_attributes();

            $jmj_attribute = new WC_Product_Attribute();
            $jmj_attribute->set_id(0);
            $jmj_attribute->set_name('JMJ');
            $jmj_attribute->set_options(array($jmj));
            $jmj_attribute->set_position(0);
            $jmj_attribute->set_visible(true);
            $jmj_attribute->set_variation(false);

            $attributes['jmj'] = $jmj_attribute;

            $product->set_attributes($attributes);
            $needs_save = true;
        }

        if ($needs_save) {
            $product->save();

            wc_delete_product_transients($product_id);
            clean_post_cache($product_id);

            $updated++;
        } else {
            $unchanged++;
        }

        /*
         * Dodaj Ravenol tag ako već nije postavljen.
         */
        wp_set_object_terms(
            $product_id,
            $fiksni_brend,
            'product_tag',
            true
        );

    } else {

        /*
        |--------------------------------------------------------------------------
        | KREIRANJE NOVOG PROIZVODA
        |--------------------------------------------------------------------------
        */

        try {
            $product = new WC_Product_Simple();

            $product->set_name($naziv);
            $product->set_sku($sifra);
            $product->set_status('publish');
            $product->set_catalog_visibility('visible');

            $product->set_regular_price($final_price);
            $product->set_price($final_price);

            $product->set_manage_stock(true);
            $product->set_stock_quantity($zaliha);

            if ($zaliha > 0) {
                $product->set_stock_status('instock');
            } else {
                $product->set_stock_status('outofstock');
            }

            /*
             * JMJ atribut.
             */
            if ($jmj !== '') {
                $jmj_attribute = new WC_Product_Attribute();
                $jmj_attribute->set_id(0);
                $jmj_attribute->set_name('JMJ');
                $jmj_attribute->set_options(array($jmj));
                $jmj_attribute->set_position(0);
                $jmj_attribute->set_visible(true);
                $jmj_attribute->set_variation(false);

                $product->set_attributes(array(
                    'jmj' => $jmj_attribute,
                ));
            }

            /*
             * Sačuvaj proizvod da dobije ID.
             */
            $product_id = $product->save();

            if (!$product_id) {
                echo "SKU {$sifra}: proizvod nije kreiran.\n";
                $errors++;
                continue;
            }

            /*
             * Postavi Ravenol tag.
             */
            wp_set_object_terms(
                $product_id,
                $fiksni_brend,
                'product_tag',
                true
            );

            /*
             * Preuzmi i postavi fiksnu sliku.
             */
            if ($fiksna_slika !== '') {
                $attachment_id = media_sideload_image(
                    $fiksna_slika,
                    $product_id,
                    $naziv,
                    'id'
                );

                if (!is_wp_error($attachment_id)) {
                    $product->set_image_id((int) $attachment_id);
                    $product->save();
                } else {
                    echo "SKU {$sifra}: slika nije preuzeta — ";
                    echo $attachment_id->get_error_message() . "\n";
                }
            }

            $wp_products_by_sku[$sifra] = $product_id;
            $processed_product_ids[] = $product_id;

            wc_delete_product_transients($product_id);

            $created++;

        } catch (Throwable $e) {
            echo "Greška za SKU {$sifra}: ";
            echo $e->getMessage() . "\n";

            $errors++;
        }
    }

    /*
     * Prikaz napretka svakih 500 redova.
     */
    if ($line_number % 500 === 0) {
        echo "Obrađeno redova: {$line_number}\n";
    }
}

fclose($handle);

echo "\n";
echo "Sinhronizacija završena.\n";
echo "Kreirano: {$created}\n";
echo "Ažurirano: {$updated}\n";
echo "Bez izmjena: {$unchanged}\n";
echo "Preskočeno: {$skipped}\n";
echo "Greške: {$errors}\n";