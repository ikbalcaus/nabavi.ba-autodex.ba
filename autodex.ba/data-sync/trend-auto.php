<?php
/**
 * ===== TREND AUTO -> WOOCOMMERCE SINHRONIZACIJA =====
 *
 * Izvor:
 *   https://narudzbe.boxsistem.com/api/trend/lager.php?vrijeme=1778453340&uslov=3
 *   https://narudzbe.boxsistem.com/api/trend/lager.php?vrijeme=1778453340&uslov=4
 *   (Basic auth: Trend / dHJlbmRhcGk= ; uslov=3 je prva polovina, uslov=4 druga)
 *
 * Šta radi:
 *  - preuzima oba dijela feeda i spaja ih (duplikati po SKU se preskaču)
 *  - preskače sve proizvode brenda SWAG (i postojeće proizvode u SWAG kategoriji)
 *  - cijena = osnovna cijena (polje "c") + procenat brenda + PDV (17%) ako je označen
 *      * prvo se koriste procenti sačuvani na stranici Tools -> Brand cijene
 *      * ako brend nije sačuvan, koriste se defaulti (ABAKUS/AS-PL/... = 75%,
 *        TOPRAN = 45%, svi ostali = 35% + PDV)
 *  - zarada = osnovna cijena * procenat brenda / 100 (bez PDV-a).
 *      * ako je zarada manja od minimalne (default 20 KM, kao u Brand cijenama),
 *        proizvod ide u smeće (novi se ne kreira); kada ponovo pređe minimum,
 *        vraća se iz smeća i ažurira.
 *  - mapira: naziv, SKU, zaliha, cijena, kategorije, oznake, brend, istaknuto, datum
 *
 * Pokretanje (preporučeno, WP-CLI):
 *   wp eval-file wp-content/themes/<tema>/data-sync/trend-auto.php
 *   wp eval-file .../trend-auto.php -- --limit=25 --dry-run
 *   wp eval-file .../trend-auto.php -- --refresh      (ignoriši keš, ponovo preuzmi API)
 *   wp eval-file .../trend-auto.php -- --min-profit=0 (isključi provjeru zarade)
 *
 * Cron (sistem crontab, kao site user):
 *   15 3 * * * cd /home/USER/htdocs/SITE && /usr/local/bin/wp eval-file \
 *     wp-content/themes/SITE/data-sync/trend-auto.php >> \
 *     wp-content/uploads/trend-auto-cache/cron.log 2>&1
 *   Skripta sama drži lock (trend-auto.lock) pa se dva run-a ne mogu preklopiti.
 *   --force preskače lock (koristi samo ako si siguran da nema drugog run-a).
 *
 * Pokretanje iz browsera (samo prijavljen administrator):
 *   /wp-content/themes/<tema>/data-sync/trend-auto.php?confirm=1
 *   ...&limit=10&dry_run=1
 *
 * Napomena: API je spor (2-5 min po polovini). Rezultat se kešira 1h u
 * wp-content/uploads/trend-auto-cache/ da ponovni run ne čeka mrežu.
 *
 * @package Autodex/Nabavi
 */

/* -------------------------------------------------------------------------
 * 1. Sigurnost i bootstrap
 * ---------------------------------------------------------------------- */

if ( PHP_SAPI !== 'cli' ) {
    @header( 'Content-Type: text/plain; charset=utf-8' );

    if ( ! isset( $_GET['confirm'] ) || '1' !== (string) $_GET['confirm'] ) {
        @header( 'HTTP/1.1 403 Forbidden' );
        exit( "Zabranjen pristup.\nPokreni preko WP-CLI:\n  wp eval-file " . basename( __FILE__ ) . "\n" );
    }
}

if ( ! defined( 'ABSPATH' ) ) {
    $trend_dir  = __DIR__;
    $trend_load = '';

    for ( $trend_i = 0; $trend_i < 8; $trend_i++ ) {
        if ( is_readable( $trend_dir . '/wp-load.php' ) ) {
            $trend_load = $trend_dir . '/wp-load.php';
            break;
        }

        $trend_parent = dirname( $trend_dir );

        if ( $trend_parent === $trend_dir ) {
            break;
        }

        $trend_dir = $trend_parent;
    }

    if ( '' === $trend_load ) {
        exit( "wp-load.php nije pronađen.\n" );
    }

    require_once $trend_load;
}

if ( ! function_exists( 'WC' ) ) {
    exit( "WooCommerce nije aktivan.\n" );
}

if ( PHP_SAPI !== 'cli' ) {
    if ( ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) ) {
        @header( 'HTTP/1.1 403 Forbidden' );
        exit( "Potrebna je prijava i dozvola manage_woocommerce.\n" );
    }
}

@set_time_limit( 0 );
@ini_set( 'memory_limit', '2048M' );
ignore_user_abort( true );

// Guard za brand-cijene.php: dok traje import ne primjenjuj markup ponovo.
if ( ! defined( 'TREND_AUTO_SYNCING' ) ) {
    define( 'TREND_AUTO_SYNCING', true );
}

global $wpdb;

/* -------------------------------------------------------------------------
 * 2. Postavke
 * ---------------------------------------------------------------------- */

if ( ! defined( 'TREND_API_BASE' ) ) {
    define( 'TREND_API_BASE', 'https://narudzbe.boxsistem.com/api/trend/lager.php' );
}
if ( ! defined( 'TREND_API_USER' ) ) {
    define( 'TREND_API_USER', 'Trend' );
}
if ( ! defined( 'TREND_API_PASS' ) ) {
    define( 'TREND_API_PASS', 'dHJlbmRhcGk=' );
}
if ( ! defined( 'TREND_API_VRIJEME' ) ) {
    define( 'TREND_API_VRIJEME', '1778453340' );
}
if ( ! defined( 'TREND_HTTP_TIMEOUT' ) ) {
    define( 'TREND_HTTP_TIMEOUT', 420 );
}
if ( ! defined( 'TREND_CACHE_TTL' ) ) {
    define( 'TREND_CACHE_TTL', 3600 );
}
if ( ! defined( 'TREND_DEFAULT_PERCENT' ) ) {
    define( 'TREND_DEFAULT_PERCENT', 35 );
}
if ( ! defined( 'TREND_DEFAULT_VAT' ) ) {
    define( 'TREND_DEFAULT_VAT', 17 );
}
if ( ! defined( 'TREND_DEFAULT_CATEGORY' ) ) {
    define( 'TREND_DEFAULT_CATEGORY', 'Trend Auto' );
}
if ( ! defined( 'TREND_DEFAULT_MIN_PROFIT' ) ) {
    define( 'TREND_DEFAULT_MIN_PROFIT', 20 );
}
if ( ! defined( 'TREND_TAG' ) ) {
    define( 'TREND_TAG', 'trend-auto' );
}

/* -------------------------------------------------------------------------
 * 3. CLI / GET opcije
 * ---------------------------------------------------------------------- */

/**
 * Vrati vrijednost opcije iz CLI argumenata, GET-a ili env varijable.
 *
 * @param string $name    Naziv opcije.
 * @param mixed  $default Default vrijednost.
 * @return mixed
 */
function trend_opt( $name, $default = null ) {
    static $args = null;

    if ( null === $args ) {
        $args = array();

        if ( PHP_SAPI === 'cli' && ! empty( $_SERVER['argv'] ) ) {
            foreach ( $_SERVER['argv'] as $arg ) {
                if ( preg_match( '/^--([a-z0-9_-]+)(?:=(.*))?$/i', (string) $arg, $m ) ) {
                    $args[ strtolower( $m[1] ) ] = isset( $m[2] ) ? $m[2] : '1';
                }
            }
        }

        if ( ! empty( $_GET ) ) {
            foreach ( $_GET as $key => $value ) {
                if ( is_scalar( $value ) ) {
                    $args[ strtolower( (string) $key ) ] = (string) $value;
                }
            }
        }
    }

    $name = strtolower( $name );

    if ( array_key_exists( $name, $args ) ) {
        return $args[ $name ];
    }

    $env = getenv( 'TREND_' . strtoupper( str_replace( '-', '_', $name ) ) );

    if ( false !== $env ) {
        return $env;
    }

    return $default;
}

/**
 * Provjeri boolean opciju (podržava --dry-run i dry_run).
 *
 * @param array $names Nazivi opcija.
 * @return bool
 */
function trend_flag( $names ) {
    foreach ( (array) $names as $name ) {
        $value = trend_opt( $name, null );

        if ( null !== $value && in_array( strtolower( (string) $value ), array( '1', 'true', 'yes', 'on' ), true ) ) {
            return true;
        }
    }

    return false;
}

/* -------------------------------------------------------------------------
 * 4. Log, keš i API
 * ---------------------------------------------------------------------- */

/**
 * Direktorij za keš i log.
 *
 * @return string
 */
function trend_cache_dir() {
    static $dir = null;

    if ( null !== $dir ) {
        return $dir;
    }

    $uploads = wp_upload_dir( null, false );
    $base    = ! empty( $uploads['basedir'] ) ? $uploads['basedir'] : get_temp_dir();

    $dir = trailingslashit( $base ) . 'trend-auto-cache';

    if ( ! is_dir( $dir ) ) {
        wp_mkdir_p( $dir );
    }

    if ( is_dir( $dir ) && ! file_exists( $dir . '/index.php' ) ) {
        @file_put_contents( $dir . '/index.php', "<?php\n// Silence is golden.\n" );
    }

    return $dir;
}

/**
 * Putanja keš fajla za jednu polovinu feeda.
 *
 * @param int $uslov 3 ili 4.
 * @return string
 */
function trend_cache_file( $uslov ) {
    return trend_cache_dir() . '/trend-auto-uslov-' . (int) $uslov . '.json';
}

/**
 * Log u stdout i u fajl.
 *
 * @param string $message Poruka.
 */
function trend_log( $message ) {
    static $file = null;

    if ( null === $file ) {
        $file = trend_cache_dir() . '/trend-auto.log';
    }

    $line = '[' . date( 'Y-m-d H:i:s' ) . '] ' . $message;

    echo $line . "\n";

    if ( is_dir( dirname( $file ) ) ) {
        @file_put_contents( $file, $line . "\n", FILE_APPEND );
    }

    @flush();
}

/**
 * Prekini izvršavanje uz poruku.
 *
 * @param string $message Poruka.
 */
function trend_abort( $message ) {
    trend_log( 'GREŠKA: ' . $message );
    exit( 1 );
}

/**
 * Zaključaj izvršavanje da se dva cron run-a ne preklope.
 *
 * @return bool True ako je lock dobijen.
 */
function trend_acquire_lock() {
    static $handle = null;

    if ( null !== $handle ) {
        return true;
    }

    $file   = trend_cache_dir() . '/trend-auto.lock';
    $handle = @fopen( $file, 'c' );

    if ( ! $handle ) {
        trend_log( 'UPOZORENJE: lock fajl nije moguće otvoriti — nastavljam bez zaštite od preklapanja.' );
        $handle = false;
        return true;
    }

    if ( ! flock( $handle, LOCK_EX | LOCK_NB ) ) {
        fclose( $handle );
        $handle = false;
        return false;
    }

    ftruncate( $handle, 0 );
    fwrite( $handle, getmypid() . ' ' . date( 'c' ) . "\n" );
    fflush( $handle );

    return true;
}

/**
 * Preuzmi jednu polovinu feeda (uz keš i jedan retry).
 *
 * @param int  $uslov   3 ili 4.
 * @param bool $refresh Ignoriši keš.
 * @return array
 */
function trend_fetch_half( $uslov, $refresh = false ) {
    $file = trend_cache_file( $uslov );

    if ( ! $refresh && is_readable( $file ) ) {
        $age = time() - (int) filemtime( $file );

        if ( $age >= 0 && $age < TREND_CACHE_TTL ) {
            $rows = json_decode( (string) file_get_contents( $file ), true );

            if ( is_array( $rows ) && ! empty( $rows ) ) {
                trend_log(
                    sprintf(
                        'uslov=%d: keš (%d redova, star %d min)',
                        $uslov,
                        count( $rows ),
                        (int) round( $age / 60 )
                    )
                );

                return $rows;
            }
        }
    }

    $url = TREND_API_BASE . '?' . http_build_query(
        array(
            'vrijeme' => TREND_API_VRIJEME,
            'uslov'   => $uslov,
        ),
        '',
        '&'
    );

    trend_log( sprintf( 'uslov=%d: preuzimanje (može potrajati 2-5 min)...', $uslov ) );

    $body = '';

    for ( $attempt = 1; $attempt <= 2; $attempt++ ) {
        $response = wp_remote_get(
            $url,
            array(
                'timeout'     => TREND_HTTP_TIMEOUT,
                'redirection' => 3,
                'headers'     => array(
                    'Authorization' => 'Basic ' . base64_encode( TREND_API_USER . ':' . TREND_API_PASS ),
                    'Accept'        => 'application/json',
                ),
                'user-agent'  => 'TrendAutoSync/1.0; ' . home_url( '/' ),
            )
        );

        if ( is_wp_error( $response ) ) {
            trend_log(
                sprintf(
                    'uslov=%d: greška na pokušaju %d/2 (%s)',
                    $uslov,
                    $attempt,
                    $response->get_error_message()
                )
            );

            if ( $attempt < 2 ) {
                sleep( 5 );
            }

            continue;
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        $body = (string) wp_remote_retrieve_body( $response );

        if ( 200 === $code && '' !== $body ) {
            break;
        }

        trend_log( sprintf( 'uslov=%d: HTTP %d na pokušaju %d/2', $uslov, $code, $attempt ) );

        if ( $attempt < 2 ) {
            sleep( 5 );
            $body = '';
        }
    }

    if ( '' === $body ) {
        trend_abort( sprintf( 'uslov=%d: API nije vratio podatke.', $uslov ) );
    }

    $body = preg_replace( '/^\xEF\xBB\xBF/', '', $body );
    $rows = json_decode( $body, true );

    if ( ! is_array( $rows ) || empty( $rows ) || ! isset( $rows[0] ) || ! is_array( $rows[0] ) ) {
        $snippet = substr( preg_replace( '/\s+/', ' ', (string) $body ), 0, 300 );
        trend_abort( sprintf( 'uslov=%d: neočekivan odgovor: %s', $uslov, $snippet ) );
    }

    if ( is_dir( trend_cache_dir() ) ) {
        @file_put_contents( $file, $body );
    }

    trend_log( sprintf( 'uslov=%d: preuzeto %d redova.', $uslov, count( $rows ) ) );

    return $rows;
}

/* -------------------------------------------------------------------------
 * 5. Brendovi i procenti
 * ---------------------------------------------------------------------- */

/**
 * Taksonomija brendova (WooCommerce Brands, Perfect Brands, YITH, atribut).
 *
 * @return string
 */
function trend_brand_taxonomy() {
    static $tax = null;

    if ( null !== $tax ) {
        return $tax;
    }

    $tax = '';

    if ( function_exists( 'bpa_get_brand_taxonomy' ) ) {
        $tax = (string) bpa_get_brand_taxonomy();
    } elseif ( function_exists( 'autodex_get_brand_taxonomy' ) ) {
        $tax = (string) autodex_get_brand_taxonomy();
    } elseif ( function_exists( 'nabavi_get_brand_taxonomy' ) ) {
        $tax = (string) nabavi_get_brand_taxonomy();
    } else {
        foreach ( array( 'product_brand', 'pwb-brand', 'yith_product_brand', 'pa_brand', 'pa_brend', 'brand', 'product_brands' ) as $candidate ) {
            if ( taxonomy_exists( $candidate ) ) {
                $tax = $candidate;
                break;
            }
        }
    }

    if ( '' === $tax || ! taxonomy_exists( $tax ) ) {
        trend_log( 'UPOZORENJE: taksonomija brendova nije pronađena — brendovi se neće dodavati.' );
        $tax = '';
    }

    return $tax;
}

/**
 * Normalizacija teksta iz API-ja (trim, skupljanje razmaka).
 *
 * @param mixed $value Vrijednost.
 * @return string
 */
function trend_text( $value ) {
    $value = (string) $value;
    $value = preg_replace( '/^\xEF\xBB\xBF/', '', $value );
    $value = preg_replace( '/[\x00-\x1F\x7F]+/u', ' ', $value );
    $value = preg_replace( '/\s+/u', ' ', $value );

    return trim( $value );
}

/**
 * Ključ za poređenje brendova (samo A-Z i 0-9).
 *
 * @param string $value Vrijednost.
 * @return string
 */
function trend_compact( $value ) {
    return preg_replace( '/[^A-Z0-9]/', '', strtoupper( trend_text( $value ) ) );
}

/**
 * Aliasi brendova iz feeda u nazive koji se koriste u shopu.
 *
 * @return array
 */
function trend_brand_aliases() {
    $aliases = array(
        'ASPL'        => 'AS-PL',
        'AS PL'       => 'AS-PL',
        'DACOGERMANY' => 'DACO GERMANY',
        'SCTGERMANY'  => 'SCT GERMANY',
        'MANNOLHEMIJA' => 'MANNOL HEMIJA',
        'ABAKUS SP ZOO' => 'ABAKUS SP Z.O.O.',
    );

    return (array) apply_filters( 'trend_auto_brand_aliases', $aliases );
}

/**
 * Naziv brenda za prikaz (alias ako postoji, inače uppercase).
 *
 * @param string $raw Sirowi brend iz API-ja.
 * @return string
 */
function trend_brand_name( $raw ) {
    $brand = strtoupper( trend_text( $raw ) );

    if ( '' === $brand ) {
        return '';
    }

    $aliases = trend_brand_aliases();

    if ( isset( $aliases[ $brand ] ) ) {
        return (string) $aliases[ $brand ];
    }

    $compact = trend_compact( $brand );

    foreach ( $aliases as $from => $to ) {
        if ( trend_compact( $from ) === $compact ) {
            return (string) $to;
        }
    }

    return $brand;
}

/**
 * Da li je brend SWAG (preskače se u cjelosti).
 *
 * @param string $brand Brend.
 * @return bool
 */
function trend_brand_is_swag( $brand ) {
    $brand = strtoupper( trend_text( $brand ) );

    return ( false !== strpos( $brand, 'SWAG' ) );
}

/**
 * Mapa termina brendova (po nazivu, slugu i kompaktnom ključu).
 *
 * @return array
 */
function trend_brand_terms() {
    static $map = null;

    if ( null !== $map ) {
        return $map;
    }

    $map = array(
        'by_name'    => array(),
        'by_compact' => array(),
        'by_slug'    => array(),
    );

    $taxonomy = trend_brand_taxonomy();

    if ( '' === $taxonomy ) {
        return $map;
    }

    $terms = get_terms(
        array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
        )
    );

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return $map;
    }

    foreach ( $terms as $term ) {
        $map['by_name'][ strtoupper( trend_text( $term->name ) ) ] = $term;
        $map['by_compact'][ trend_compact( $term->name ) ]         = $term;
        $map['by_slug'][ strtolower( $term->slug ) ]               = $term;
    }

    return $map;
}

/**
 * Pronađi termin brenda za naziv iz feeda.
 *
 * @param string $brand Brend.
 * @return WP_Term|null
 */
function trend_find_brand_term( $brand ) {
    $brand = trend_text( $brand );

    if ( '' === $brand ) {
        return null;
    }

    $map = trend_brand_terms();

    $lookup = array(
        strtoupper( $brand ),
        strtoupper( trend_brand_name( $brand ) ),
    );

    foreach ( $lookup as $key ) {
        if ( isset( $map['by_name'][ $key ] ) ) {
            return $map['by_name'][ $key ];
        }
    }

    $compact = trend_compact( $brand );

    if ( isset( $map['by_compact'][ $compact ] ) ) {
        return $map['by_compact'][ $compact ];
    }

    $slug = sanitize_title( trend_brand_name( $brand ) );

    if ( isset( $map['by_slug'][ $slug ] ) ) {
        return $map['by_slug'][ $slug ];
    }

    return null;
}

/**
 * Default procenti (isto kao u brand-cijene.php). null = brend se preskače.
 *
 * @return array
 */
function trend_default_brand_percentages() {
    static $map = null;

    if ( null !== $map ) {
        return $map;
    }

    if ( function_exists( 'bpa_brand_default_overrides' ) ) {
        $source = bpa_brand_default_overrides();
    } else {
        $source = array(
            'ABAKUS'           => 75,
            'ABAKUS SP Z.O.O.' => 75,
            'AS-PL'            => 75,
            'AUTOMEGA'         => 75,
            'AUTOVENTIL'       => 75,
            'BGA'              => 75,
            'DACO'             => 75,
            'DACO GERMANY'     => 75,
            'GAUSS'            => 75,
            'ICER'             => 75,
            'MANNOL'           => 75,
            'MANNOL HEMIJA'    => 75,
            'MONEA'            => 75,
            'NEWCRE'           => 75,
            'RYMEC'            => 75,
            'SCT GERMANY'      => 75,
            'SCTGERMANY'       => 75,
            'STC'              => 75,
            'TOPRAN'           => 45,
            'SWAG'             => null,
        );
    }

    $map = array();

    foreach ( (array) $source as $brand => $percent ) {
        $map[ strtoupper( trend_text( $brand ) ) ] = ( null === $percent ) ? null : (float) $percent;
    }

    $map = (array) apply_filters( 'trend_auto_brand_percentages', $map );

    return $map;
}

/**
 * Procenti sačuvani na stranici Tools -> Brand cijene (term_id => cfg).
 *
 * @return array
 */
function trend_saved_brand_configs() {
    static $configs = null;

    if ( null !== $configs ) {
        return $configs;
    }

    $configs = array();
    $raw     = get_option( 'bpa_brand_percentages', array() );

    if ( ! is_array( $raw ) ) {
        return $configs;
    }

    foreach ( $raw as $term_id => $row ) {
        if ( is_array( $row ) ) {
            $configs[ (int) $term_id ] = array(
                'percent' => isset( $row['percent'] ) ? (float) $row['percent'] : 0.0,
                'vat'     => ! empty( $row['vat'] ),
                'empty'   => ! empty( $row['empty'] ),
            );
        } else {
            $configs[ (int) $term_id ] = array(
                'percent' => (float) $row,
                'vat'     => false,
                'empty'   => false,
            );
        }
    }

    return $configs;
}

/**
 * Pravilo za brend: procenat, PDV, da li je polje prazno i da li se preskače.
 *
 * @param string $brand Brend.
 * @return array
 */
function trend_brand_config( $brand ) {
    static $cache = array();

    $key = strtoupper( trend_text( $brand ) );

    if ( isset( $cache[ $key ] ) ) {
        return $cache[ $key ];
    }

    $vat_rate = (float) apply_filters(
        'trend_auto_vat_rate',
        function_exists( 'bpa_get_vat_rate' ) ? bpa_get_vat_rate() : TREND_DEFAULT_VAT
    );

    $config = array(
        'percent'  => (float) TREND_DEFAULT_PERCENT,
        'vat'      => true,
        'empty'    => false,
        'vat_rate' => $vat_rate,
        'source'   => 'default',
    );

    // 1) Sačuvane postavke (term_id) imaju prednost.
    $term    = trend_find_brand_term( $brand );
    $saved   = trend_saved_brand_configs();
    $applied = false;

    if ( $term && isset( $saved[ $term->term_id ] ) ) {
        $row = $saved[ $term->term_id ];

        $config['percent'] = $row['percent'];
        $config['vat']     = $row['vat'];
        $config['empty']   = $row['empty'];
        $config['source']  = 'saved';
        $applied           = true;
    }

    // 2) Defaulti po imenu brenda.
    if ( ! $applied && '' !== $key ) {
        $defaults = trend_default_brand_percentages();

        if ( array_key_exists( $key, $defaults ) ) {
            if ( null === $defaults[ $key ] ) {
                $config['empty']  = true;
                $config['percent'] = 0.0;
                $config['vat']    = false;
                $config['source'] = 'skipped';
            } else {
                $config['percent'] = (float) $defaults[ $key ];
                $config['vat']     = true;
                $config['source']  = 'default';
            }

            $applied = true;
        } else {
            foreach ( $defaults as $default_brand => $percent ) {
                if ( trend_compact( $default_brand ) !== trend_compact( $key ) ) {
                    continue;
                }

                if ( null === $percent ) {
                    $config['empty']   = true;
                    $config['percent'] = 0.0;
                    $config['vat']     = false;
                    $config['source']  = 'skipped';
                } else {
                    $config['percent'] = (float) $percent;
                    $config['vat']     = true;
                    $config['source']  = 'default';
                }

                $applied = true;
                break;
            }
        }
    }

    // 3) Sve ostalo: 35% + PDV.
    return $cache[ $key ] = $config;
}

/* -------------------------------------------------------------------------
 * 6. Cijena, naziv, zaliha
 * ---------------------------------------------------------------------- */

/**
 * Broj decimala za cijene.
 *
 * @return int
 */
function trend_price_decimals() {
    return function_exists( 'wc_get_price_decimals' ) ? (int) wc_get_price_decimals() : 2;
}

/**
 * Cijena kao string za WooCommerce.
 *
 * @param float $price Cijena.
 * @return string
 */
function trend_price_string( $price ) {
    $decimals = trend_price_decimals();
    $price    = round( (float) $price, $decimals );

    return function_exists( 'wc_format_decimal' ) ? wc_format_decimal( $price, $decimals ) : (string) $price;
}

/**
 * Finalna cijena: osnovna * (1 + procenat/100) * (1 + PDV/100).
 *
 * @param float $base   Osnovna cijena iz feeda.
 * @param array $config Pravilo brenda.
 * @return float
 */
function trend_calculate_price( $base, $config ) {
    $multiplier = 1.0;

    if ( empty( $config['empty'] ) ) {
        $multiplier = 1 + ( (float) $config['percent'] / 100 );

        if ( ! empty( $config['vat'] ) ) {
            $multiplier *= 1 + ( (float) $config['vat_rate'] / 100 );
        }
    }

    return round( (float) $base * $multiplier, trend_price_decimals() );
}

/**
 * Minimalna zarada u KM (bez PDV-a). 0 = provjera je isključena.
 *
 * @return float
 */
function trend_min_profit() {
    $default = function_exists( 'bpa_default_min_profit' ) ? (float) bpa_default_min_profit() : (float) TREND_DEFAULT_MIN_PROFIT;

    foreach ( array( 'min-profit', 'min_profit' ) as $option ) {
        $value = trend_opt( $option, null );

        if ( null !== $value && '' !== $value && is_numeric( $value ) ) {
            $default = (float) $value;
            break;
        }
    }

    return max( 0, (float) apply_filters( 'trend_auto_min_profit', $default ) );
}

/**
 * Zarada po komadu: osnovna cijena * procenat brenda / 100 (bez PDV-a).
 * null = brend ima prazno polje pa se provjera preskače (kao u Brand cijenama).
 *
 * @param float $base   Osnovna cijena.
 * @param array $config Pravilo brenda.
 * @return float|null
 */
function trend_profit( $base, $config ) {
    if ( ! empty( $config['empty'] ) ) {
        return null;
    }

    return round( (float) $base * (float) $config['percent'] / 100, 2 );
}

/**
 * Da li je zarada ispod minimuma.
 *
 * @param float|null $profit     Zarada ili null.
 * @param float      $min_profit Minimum.
 * @return bool
 */
function trend_profit_is_low( $profit, $min_profit ) {
    return ( null !== $profit && $min_profit > 0 && $profit < $min_profit );
}

/**
 * Očisti naziv: skini brend sa kraja, skupljaj razmake.
 *
 * @param string $raw       Naziv iz feeda.
 * @param string $brand     Brend za prikaz.
 * @param string $brand_raw Sirowi brend iz feeda (npr. DACOGERMANY).
 * @return string
 */
function trend_clean_name( $raw, $brand, $brand_raw = '' ) {
    $name = trend_text( $raw );

    if ( '' === $name ) {
        return '';
    }

    $candidates = array( $brand, $brand_raw );

    foreach ( array( $brand, $brand_raw ) as $candidate ) {
        $candidate = trend_text( $candidate );

        if ( '' === $candidate ) {
            continue;
        }

        $candidates[] = str_replace( ' ', '', $candidate );
        $candidates[] = trend_compact( $candidate );
    }

    $candidates = array_unique( array_filter( array_map( 'trend_text', $candidates ), 'strlen' ) );

    foreach ( $candidates as $suffix ) {
        $pattern = '/[\s\-–—\/,;:]*\b' . preg_quote( $suffix, '/' ) . '\s*$/iu';

        if ( preg_match( $pattern, $name ) ) {
            $name = preg_replace( $pattern, '', $name );
        }
    }

    $name = trend_text( $name );
    $name = preg_replace( '/[\s\-–—\/,;:]+$/u', '', $name );

    return trim( (string) $name );
}

/**
 * SKU normalizacija (trim + kontrola dužine).
 *
 * @param mixed $sku SKU.
 * @return string
 */
function trend_sku( $sku ) {
    $sku = trend_text( $sku );

    if ( strlen( $sku ) > 190 ) {
        $sku = substr( $sku, 0, 190 );
    }

    return $sku;
}

/**
 * Da li proizvod već ima SWAG kategoriju (tada se preskače).
 *
 * @param int $product_id ID proizvoda.
 * @return bool
 */
function trend_product_has_swag_category( $product_id ) {
    static $cache = array();

    $product_id = (int) $product_id;

    if ( isset( $cache[ $product_id ] ) ) {
        return $cache[ $product_id ];
    }

    $found = false;
    $terms = wp_get_post_terms( $product_id, 'product_cat' );

    if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
        foreach ( $terms as $term ) {
            if ( false !== stripos( $term->name, 'swag' ) || false !== stripos( $term->slug, 'swag' ) ) {
                $found = true;
                break;
            }
        }
    }

    return $cache[ $product_id ] = $found;
}

/**
 * Dodijeli brend proizvodu (brend se zamjenjuje, ne dodaje).
 *
 * @param int    $product_id ID proizvoda.
 * @param string $brand      Brend.
 */
function trend_assign_brand( $product_id, $brand ) {
    $taxonomy = trend_brand_taxonomy();

    if ( '' === $taxonomy || '' === $brand ) {
        return;
    }

    $result = wp_set_object_terms( $product_id, array( $brand ), $taxonomy, false );

    if ( is_wp_error( $result ) ) {
        trend_log( sprintf( 'SKU %s: brend "%s" nije dodijeljen (%s).', get_post_meta( $product_id, '_sku', true ), $brand, $result->get_error_message() ) );
    }
}

/**
 * JMJ atribut na proizvodu.
 *
 * @param WC_Product $product Proizvod.
 * @param string     $unit    Jedinica.
 * @return bool True ako je promijenjeno.
 */
function trend_set_unit_attribute( $product, $unit ) {
    if ( '' === $unit ) {
        return false;
    }

    $attributes = $product->get_attributes();
    $key        = 'jmj';

    if ( isset( $attributes[ $key ] ) && $attributes[ $key ] instanceof WC_Product_Attribute ) {
        $options = $attributes[ $key ]->get_options();

        if ( 1 === count( $options ) && (string) reset( $options ) === $unit ) {
            return false;
        }
    }

    $attribute = new WC_Product_Attribute();
    $attribute->set_id( 0 );
    $attribute->set_name( 'JMJ' );
    $attribute->set_options( array( $unit ) );
    $attribute->set_position( 0 );
    $attribute->set_visible( true );
    $attribute->set_variation( false );

    $attributes[ $key ] = $attribute;
    $product->set_attributes( $attributes );

    return true;
}

/**
 * Uporedi dvije cijene.
 *
 * @param mixed $old Stara.
 * @param float $new Nova.
 * @return bool
 */
function trend_price_differs( $old, $new ) {
    if ( '' === $old || null === $old || ! is_numeric( $old ) ) {
        return true;
    }

    return abs( (float) $old - (float) $new ) > 0.004;
}

/* -------------------------------------------------------------------------
 * 7. Glavni posao
 * ---------------------------------------------------------------------- */

$dry_run      = trend_flag( array( 'dry-run', 'dry_run' ) );
$refresh      = trend_flag( array( 'refresh' ) );
$update_names = trend_flag( array( 'update-names', 'update_names' ) );
$limit        = (int) trend_opt( 'limit', 0 );
$min_profit   = trend_min_profit();

if ( ! trend_flag( array( 'force' ) ) && ! trend_acquire_lock() ) {
    trend_log( 'Import je već u toku (lock fajl). Prekidam ovaj run.' );
    exit( 0 );
}

trend_log( '===== TREND AUTO SINHRONIZACIJA =====' );
trend_log(
    sprintf(
        'Režim: %s | Limit: %s | Minimalna zarada: %s KM | Brend taksonomija: %s',
        $dry_run ? 'DRY RUN (bez upisa)' : 'UPIS',
        $limit > 0 ? $limit : 'sve',
        $min_profit > 0 ? $min_profit : 'isključena',
        trend_brand_taxonomy() ? trend_brand_taxonomy() : 'nema'
    )
);

$started = microtime( true );

// 7.1 Preuzimanje obje polovine.
$rows_a = trend_fetch_half( 3, $refresh );
$rows_b = trend_fetch_half( 4, $refresh );
$all    = array_merge( $rows_a, $rows_b );

$feed        = array();
$duplicates  = 0;
$invalid_row = 0;

foreach ( $all as $row ) {
    if ( ! is_array( $row ) ) {
        $invalid_row++;
        continue;
    }

    $sku = trend_sku( isset( $row['s'] ) ? $row['s'] : ( isset( $row['f'] ) ? $row['f'] : '' ) );

    if ( '' === $sku ) {
        $invalid_row++;
        continue;
    }

    $key = strtoupper( $sku );

    if ( isset( $feed[ $key ] ) ) {
        $duplicates++;
        continue;
    }

    $feed[ $key ] = $row;
}

trend_log(
    sprintf(
        'Feed: %d + %d = %d redova; unikatnih SKU: %d; duplikata: %d; neispravnih: %d',
        count( $rows_a ),
        count( $rows_b ),
        count( $all ),
        count( $feed ),
        $duplicates,
        $invalid_row
    )
);

unset( $all, $rows_a, $rows_b );

// 7.2 Indeks postojećih proizvoda po SKU.
$db_rows = $wpdb->get_results(
    "SELECT p.ID, p.post_parent, p.post_status, pm.meta_value AS sku
     FROM {$wpdb->posts} p
     INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
     WHERE p.post_type IN ('product', 'product_variation')
       AND pm.meta_key = '_sku'
       AND pm.meta_value <> ''"
);

$existing = array();

foreach ( (array) $db_rows as $db_row ) {
    $db_key = strtoupper( trim( (string) $db_row->sku ) );

    if ( '' === $db_key ) {
        continue;
    }

    $row_status = (string) $db_row->post_status;

    // Ako isti SKU postoji i u smeću i kao aktivan proizvod, prednost ima aktivni.
    if ( isset( $existing[ $db_key ] ) ) {
        if ( 'trash' !== $existing[ $db_key ]['status'] || 'trash' === $row_status ) {
            continue;
        }
    }

    $existing[ $db_key ] = array(
        'id'     => (int) $db_row->ID,
        'parent' => (int) $db_row->post_parent,
        'status' => $row_status,
    );
}

unset( $db_rows );

trend_log( sprintf( 'Postojećih SKU u bazi: %d', count( $existing ) ) );

// 7.3 Obrada.
$created          = 0;
$updated          = 0;
$unchanged        = 0;
$skipped_swag     = 0;
$skipped_cat      = 0;
$skipped_variable = 0;
$skipped_price    = 0;
$skipped_empty    = 0;
$skipped_profit   = 0;
$trashed          = 0;
$restored         = 0;
$errors           = 0;
$processed        = 0;
$error_samples    = array();

foreach ( $feed as $sku_key => $row ) {
    if ( $limit > 0 && $processed >= $limit ) {
        break;
    }

    $processed++;

    $sku = '';

    try {
        $sku = trend_sku( isset( $row['s'] ) ? $row['s'] : ( isset( $row['f'] ) ? $row['f'] : '' ) );

        if ( '' === $sku ) {
            $skipped_empty++;
            continue;
        }

        $brand_raw = trend_text( isset( $row['b'] ) ? $row['b'] : '' );

        // SWAG se ne uvozi.
        if ( '' !== $brand_raw && trend_brand_is_swag( $brand_raw ) ) {
            $skipped_swag++;
            continue;
        }

        $brand = trend_brand_name( $brand_raw );
        $term  = trend_find_brand_term( $brand );

        if ( $term ) {
            $brand = $term->name;
        }

        if ( '' !== $brand && trend_brand_is_swag( $brand ) ) {
            $skipped_swag++;
            continue;
        }

        $name = trend_clean_name( isset( $row['n'] ) ? $row['n'] : '', $brand, $brand_raw );

        if ( '' === $name ) {
            $skipped_empty++;
            continue;
        }

        $base_raw = isset( $row['c'] ) ? str_replace( ',', '.', trend_text( $row['c'] ) ) : '';
        $base     = is_numeric( $base_raw ) ? (float) $base_raw : 0.0;

        if ( $base <= 0 ) {
            $skipped_price++;
            continue;
        }

        $config = trend_brand_config( $brand );

        if ( ! empty( $config['empty'] ) ) {
            // Prazno polje = cijena se ne mijenja (osnovna cijena bez markupa).
            $final = round( $base, trend_price_decimals() );
        } else {
            $final = trend_calculate_price( $base, $config );
        }

        if ( $final <= 0 ) {
            $skipped_price++;
            continue;
        }

        // Zarada = osnovna cijena * procenat brenda / 100 (bez PDV-a).
        $profit = trend_profit( $base, $config );

        $stock = (int) round( (float) ( isset( $row['l'] ) ? $row['l'] : 0 ) );

        if ( $stock < 0 ) {
            $stock = 0;
        }

        $unit     = strtoupper( trend_text( isset( $row['j'] ) ? $row['j'] : '' ) );
        $supplier = trend_text( isset( $row['f'] ) ? $row['f'] : '' );
        $note     = trend_text( isset( $row['o'] ) ? $row['o'] : '' );

        if ( strlen( $note ) > 500 ) {
            $note = substr( $note, 0, 500 );
        }

        $status = $stock > 0 ? 'instock' : 'outofstock';

        $meta = array(
            '_trend_base_price'     => trend_price_string( $base ),
            '_trend_final_price'    => trend_price_string( $final ),
            '_trend_price_final'    => trend_price_string( $final ),
            '_trend_brand'          => $brand,
            '_trend_brand_percent'  => $config['empty'] ? '' : (string) $config['percent'],
            '_trend_brand_vat'      => $config['vat'] ? '1' : '0',
            '_trend_supplier_code'  => $supplier,
            '_trend_unit'           => $unit,
            '_trend_group'          => isset( $row['z'] ) ? (string) trend_text( $row['z'] ) : '',
            '_trend_note'           => $note,
            '_trend_profit'         => ( null === $profit ) ? '' : number_format( (float) $profit, 2, '.', '' ),
            '_trend_synced_at'      => current_time( 'mysql' ),
        );

        $meta['_trend_price_source'] = $config['source'];

        // --- Postojeći proizvod ---
        if ( isset( $existing[ $sku_key ] ) ) {
            $product_id = (int) $existing[ $sku_key ]['id'];
            $is_trashed = ( 'trash' === $existing[ $sku_key ]['status'] );

            if ( $existing[ $sku_key ]['parent'] > 0 ) {
                $skipped_variable++;
                continue;
            }

            if ( trend_product_has_swag_category( $product_id ) ) {
                $skipped_cat++;
                continue;
            }

            // Varijabilne proizvode ne diramo (ni cijenu, ni smeće).
            $product     = null;
            $is_variable = false;

            if ( $is_trashed ) {
                $is_variable = has_term( 'variable', 'product_type', $product_id );
            } else {
                $product = wc_get_product( $product_id );

                if ( ! $product ) {
                    $errors++;
                    continue;
                }

                $is_variable = $product->is_type( 'variable' );
            }

            if ( $is_variable ) {
                $skipped_variable++;
                continue;
            }

            // Premala zarada -> proizvod ide u smeće (može se vratiti).
            if ( trend_profit_is_low( $profit, $min_profit ) ) {
                if ( $is_trashed ) {
                    $skipped_profit++;
                    continue;
                }

                if ( $dry_run ) {
                    $trashed++;
                    continue;
                }

                wp_trash_post( $product_id );
                wc_delete_product_transients( $product_id );

                $existing[ $sku_key ]['status'] = 'trash';
                $trashed++;
                continue;
            }

            // Ponovo isplativ -> vrati iz smeća i nastavi sa update-om.
            if ( $is_trashed ) {
                if ( $dry_run ) {
                    $restored++;
                    continue;
                }

                wp_untrash_post( $product_id );
                clean_post_cache( $product_id );

                $product = wc_get_product( $product_id );

                if ( ! $product ) {
                    $errors++;
                    continue;
                }

                $existing[ $sku_key ]['status'] = 'publish';
                $restored++;
            }

            $managed = metadata_exists( 'post', $product_id, '_trend_managed' );

            $changed = false;

            if ( ( $managed || $update_names ) && $product->get_name() !== $name ) {
                $product->set_name( $name );
                $changed = true;
            }

            if ( trend_price_differs( $product->get_regular_price( 'edit' ), $final ) ) {
                $product->set_regular_price( trend_price_string( $final ) );
                $product->set_price( trend_price_string( $final ) );
                $changed = true;
            }

            // Trend proizvodi nemaju sale cijenu (da ne pregazi izračunatu cijenu).
            if ( $managed && '' !== (string) $product->get_sale_price( 'edit' ) ) {
                $product->set_sale_price( '' );
                $changed = true;
            }

            if ( ! $product->get_manage_stock() ) {
                $product->set_manage_stock( true );
                $changed = true;
            }

            $current_stock = $product->get_stock_quantity( 'edit' );

            if ( null === $current_stock || (int) $current_stock !== $stock ) {
                $product->set_stock_quantity( $stock );
                $changed = true;
            }

            if ( $product->get_stock_status( 'edit' ) !== $status ) {
                $product->set_stock_status( $status );
                $changed = true;
            }

            if ( trend_set_unit_attribute( $product, $unit ) ) {
                $changed = true;
            }

            if ( $changed ) {
                if ( ! $dry_run ) {
                    $product->save();
                    wc_delete_product_transients( $product_id );
                }

                $updated++;
            } else {
                $unchanged++;
            }

            if ( $dry_run ) {
                continue;
            }

            foreach ( $meta as $meta_key => $meta_value ) {
                update_post_meta( $product_id, $meta_key, $meta_value );
            }

            trend_assign_brand( $product_id, $brand );

            wp_set_object_terms( $product_id, array( TREND_TAG ), 'product_tag', true );

            continue;
        }

        // --- Novi proizvod ---
        if ( trend_profit_is_low( $profit, $min_profit ) ) {
            $skipped_profit++;
            continue;
        }

        if ( $dry_run ) {
            $created++;
            continue;
        }

        $product = new WC_Product_Simple();
        $product->set_name( $name );
        $product->set_sku( $sku );
        $product->set_status( 'publish' );
        $product->set_catalog_visibility( 'visible' );
        $product->set_regular_price( trend_price_string( $final ) );
        $product->set_price( trend_price_string( $final ) );
        $product->set_manage_stock( true );
        $product->set_stock_quantity( $stock );
        $product->set_stock_status( $status );
        $product->set_featured( false );

        trend_set_unit_attribute( $product, $unit );

        $product_id = (int) $product->save();

        if ( $product_id <= 0 ) {
            $errors++;
            $error_samples[] = sprintf( 'SKU %s: proizvod nije kreiran.', $sku );
            continue;
        }

        wp_set_object_terms( $product_id, array( TREND_DEFAULT_CATEGORY ), 'product_cat', true );
        wp_set_object_terms( $product_id, array( TREND_TAG ), 'product_tag', true );

        trend_assign_brand( $product_id, $brand );

        foreach ( $meta as $meta_key => $meta_value ) {
            update_post_meta( $product_id, $meta_key, $meta_value );
        }

        update_post_meta( $product_id, '_trend_managed', '1' );

        wc_delete_product_transients( $product_id );

        $existing[ $sku_key ] = array(
            'id'     => $product_id,
            'parent' => 0,
        );

        $created++;
    } catch ( Throwable $e ) {
        $errors++;
        $message = isset( $sku ) ? sprintf( 'SKU %s: %s', $sku, $e->getMessage() ) : $e->getMessage();

        if ( count( $error_samples ) < 20 ) {
            $error_samples[] = $message;
        }

        if ( $errors <= 20 ) {
            trend_log( 'Greška — ' . $message );
        }
    }

    if ( 0 === $processed % 250 ) {
        trend_log(
            sprintf(
                'Napredak: %d obrada | novo %d | ažurirano %d | bez izmjene %d | u smeće %d | greške %d',
                $processed,
                $created,
                $updated,
                $unchanged,
                $trashed,
                $errors
            )
        );
    }
}

/* -------------------------------------------------------------------------
 * 8. Sažetak
 * ---------------------------------------------------------------------- */

if ( ! $dry_run ) {
    if ( class_exists( 'WC_Cache_Helper' ) ) {
        WC_Cache_Helper::get_transient_version( 'product', true );
    }

    if ( function_exists( 'wc_delete_product_transients' ) ) {
        wc_delete_product_transients( 0 );
    }
}

$duration = (int) round( microtime( true ) - $started );

trend_log( '----------------------------------------' );
trend_log( sprintf( 'Vrijeme: %d s (%s)', $duration, gmdate( 'H:i:s', $duration ) ) );
trend_log( sprintf( 'Kreirano: %d', $created ) );
trend_log( sprintf( 'Ažurirano: %d', $updated ) );
trend_log( sprintf( 'Bez izmjena: %d', $unchanged ) );
trend_log( sprintf( 'Preskočeno — SWAG brend: %d', $skipped_swag ) );
trend_log( sprintf( 'Preskočeno — SWAG kategorija: %d', $skipped_cat ) );
trend_log( sprintf( 'Preskočeno — varijabilni proizvod: %d', $skipped_variable ) );
trend_log( sprintf( 'Preskočeno — cijena: %d', $skipped_price ) );
trend_log( sprintf( 'Preskočeno — prazan SKU/naziv: %d', $skipped_empty ) );
trend_log( sprintf( 'Preskočeno — zarada ispod %s KM: %d', $min_profit > 0 ? $min_profit : '0', $skipped_profit ) );
trend_log( sprintf( 'Prebačeno u smeće (zarada ispod %s KM): %d', $min_profit > 0 ? $min_profit : '0', $trashed ) );
trend_log( sprintf( 'Vraćeno iz smeća (zarada dovoljna): %d', $restored ) );
trend_log( sprintf( 'Greške: %d', $errors ) );
trend_log( 'Log: ' . trend_cache_dir() . '/trend-auto.log' );
trend_log( 'Keš: ' . trend_cache_dir() );

if ( $error_samples ) {
    trend_log( 'Primjeri grešaka:' );

    foreach ( $error_samples as $sample ) {
        trend_log( '  - ' . $sample );
    }
}

trend_log( $dry_run ? 'DRY RUN završen (ništa nije upisano).' : 'Sinhronizacija završena.' );
