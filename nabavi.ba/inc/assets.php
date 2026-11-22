<?php
/**
 * Front-end assets.
 *
 * @package Nabavi
 */

defined( 'ABSPATH' ) || exit;

/**
 * File modification time based version, with a safe fallback.
 *
 * @param string $relative_path Path relative to the theme root.
 * @return string
 */
function nabavi_asset_version( $relative_path ) {
	$file = NABAVI_DIR . '/' . ltrim( $relative_path, '/' );

	return file_exists( $file ) ? (string) filemtime( $file ) : NABAVI_VERSION;
}

/**
 * Enqueue front-end styles and scripts.
 */
function nabavi_enqueue_assets() {
	$is_shop     = function_exists( 'is_shop' ) && ( is_shop() || is_product_category() || is_product_tag() || is_tax( nabavi_get_brand_taxonomy() ) );
	$is_product  = function_exists( 'is_product' ) && is_product();
	$is_cart     = function_exists( 'is_cart' ) && is_cart();
	$is_checkout = function_exists( 'is_checkout' ) && is_checkout();
	$is_account  = function_exists( 'is_account_page' ) && is_account_page();
	$is_woo      = function_exists( 'is_woocommerce' ) && is_woocommerce();
	$is_contact  = is_page_template( 'page-kontakt.php' ) || is_page( 'kontakt' );

	wp_enqueue_style(
		'nabavi-global',
		NABAVI_URI . '/assets/css/global.css',
		array(),
		nabavi_asset_version( 'assets/css/global.css' )
	);

	wp_enqueue_style(
		'nabavi-product-card',
		NABAVI_URI . '/assets/css/product-card.css',
		array( 'nabavi-global' ),
		nabavi_asset_version( 'assets/css/product-card.css' )
	);

	if ( is_front_page() ) {
		wp_enqueue_style(
			'nabavi-home',
			NABAVI_URI . '/assets/css/home.css',
			array( 'nabavi-global' ),
			nabavi_asset_version( 'assets/css/home.css' )
		);
	}

	if ( $is_shop || ( is_search() && ! is_admin() ) ) {
		wp_enqueue_style(
			'nabavi-shop',
			NABAVI_URI . '/assets/css/archive-product.css',
			array( 'nabavi-global' ),
			nabavi_asset_version( 'assets/css/archive-product.css' )
		);
	}

	if ( $is_product ) {
		wp_enqueue_style(
			'nabavi-product',
			NABAVI_URI . '/assets/css/single-product.css',
			array( 'nabavi-global' ),
			nabavi_asset_version( 'assets/css/single-product.css' )
		);
	}

	if ( $is_woo || $is_cart || $is_checkout || $is_account ) {
		wp_enqueue_style(
			'nabavi-woocommerce',
			NABAVI_URI . '/assets/css/woocommerce.css',
			array( 'nabavi-global' ),
			nabavi_asset_version( 'assets/css/woocommerce.css' )
		);
	}

	if ( $is_account ) {
		wp_enqueue_style(
			'nabavi-account',
			NABAVI_URI . '/assets/css/account.css',
			array( 'nabavi-woocommerce' ),
			nabavi_asset_version( 'assets/css/account.css' )
		);
	}

	if ( is_page() && ! $is_cart && ! $is_checkout && ! $is_account ) {
		wp_enqueue_style(
			'nabavi-pages',
			NABAVI_URI . '/assets/css/pages.css',
			array( 'nabavi-global' ),
			nabavi_asset_version( 'assets/css/pages.css' )
		);
	}

	wp_enqueue_script(
		'nabavi-main',
		NABAVI_URI . '/assets/js/main.js',
		array(),
		nabavi_asset_version( 'assets/js/main.js' ),
		true
	);

	wp_localize_script(
		'nabavi-main',
		'nabaviData',
		array(
			'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
			'homeUrl'    => home_url( '/' ),
			'shopUrl'    => nabavi_get_shop_url(),
			'cartUrl'    => nabavi_get_cart_url(),
			'accountUrl' => nabavi_get_account_url(),
			'isCart'     => $is_cart,
			'isShop'     => $is_shop,
			'nonce'      => wp_create_nonce( 'nabavi_nonce' ),
			'strings'    => array(
				'loading'   => __( 'Učitavanje…', 'nabavi' ),
				'error'     => __( 'Došlo je do greške. Pokušajte ponovo.', 'nabavi' ),
				'added'     => __( 'Dodano u korpu.', 'nabavi' ),
				'remove'    => __( 'Ukloni', 'nabavi' ),
				'close'     => __( 'Zatvori', 'nabavi' ),
				'menu'      => __( 'Meni', 'nabavi' ),
				'search'    => __( 'Pretraži…', 'nabavi' ),
			),
		)
	);

	wp_script_add_data( 'nabavi-main', 'strategy', 'defer' );

	if ( $is_shop || ( is_search() && ! is_admin() ) ) {
		wp_enqueue_script(
			'nabavi-shop',
			NABAVI_URI . '/assets/js/archive-product.js',
			array( 'nabavi-main' ),
			nabavi_asset_version( 'assets/js/archive-product.js' ),
			true
		);
		wp_script_add_data( 'nabavi-shop', 'strategy', 'defer' );
	}

	if ( $is_product ) {
		wp_enqueue_script(
			'nabavi-product',
			NABAVI_URI . '/assets/js/single-product.js',
			array( 'nabavi-main' ),
			nabavi_asset_version( 'assets/js/single-product.js' ),
			true
		);
		wp_script_add_data( 'nabavi-product', 'strategy', 'defer' );
	}

	if ( $is_account ) {
		wp_enqueue_script(
			'nabavi-account',
			NABAVI_URI . '/assets/js/account.js',
			array( 'nabavi-main' ),
			nabavi_asset_version( 'assets/js/account.js' ),
			true
		);
		wp_script_add_data( 'nabavi-account', 'strategy', 'defer' );
	}

	if ( $is_contact ) {
		wp_enqueue_script(
			'nabavi-contact',
			NABAVI_URI . '/assets/js/contact.js',
			array( 'nabavi-main' ),
			nabavi_asset_version( 'assets/js/contact.js' ),
			true
		);
		wp_script_add_data( 'nabavi-contact', 'strategy', 'defer' );
	}
}
add_action( 'wp_enqueue_scripts', 'nabavi_enqueue_assets' );

/**
 * Preload the homepage hero image (LCP).
 */
function nabavi_preload_hero_image() {
	if ( ! is_front_page() || ! file_exists( nabavi_image_path( 'hero-1200.webp' ) ) ) {
		return;
	}

	$srcset = array();

	foreach ( array( 800, 1200, 1584 ) as $width ) {
		$path = nabavi_image_path( 'hero-' . $width . '.webp' );

		if ( file_exists( $path ) ) {
			$srcset[] = esc_url( nabavi_image_url( 'hero-' . $width . '.webp' ) ) . ' ' . $width . 'w';
		}
	}

	printf(
		'<link rel="preload" as="image" type="image/webp" href="%1$s" imagesrcset="%2$s" imagesizes="(max-width: 1220px) 100vw, 1180px" fetchpriority="high">' . "\n",
		esc_url( nabavi_image_url( 'hero-1200.webp' ) ),
		esc_attr( implode( ', ', $srcset ) )
	);
}
add_action( 'wp_head', 'nabavi_preload_hero_image', 2 );

/**
 * Remove the WooCommerce "prettyPhoto" default styles only when the theme
 * already replaces them, keeping checkout forms intact.
 *
 * @param array $styles WooCommerce styles.
 * @return array
 */
function nabavi_woocommerce_styles( $styles ) {
	// Keep WooCommerce styles: they provide a solid base for cart, checkout,
	// account and form validation. Our CSS overrides the visual design.
	return $styles;
}
add_filter( 'woocommerce_enqueue_styles', 'nabavi_woocommerce_styles' );

/**
 * Remove the block library CSS on classic pages where it is not needed.
 */
function nabavi_dequeue_block_styles() {
	if ( is_admin() || is_singular() && has_blocks() ) {
		return;
	}

	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'classic-theme-styles' );
	wp_dequeue_style( 'global-styles' );
}
add_action( 'wp_enqueue_scripts', 'nabavi_dequeue_block_styles', 100 );

/**
 * Drop core assets the theme does not use.
 */
function nabavi_dequeue_core_assets() {
	if ( is_admin() ) {
		return;
	}

	wp_dequeue_script( 'wp-embed' );
}
add_action( 'wp_enqueue_scripts', 'nabavi_dequeue_core_assets', 100 );

/**
 * Disable the WordPress emoji detection script and styles.
 */
function nabavi_disable_emojis() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'nabavi_disable_emojis' );

/**
 * Load WooCommerce CSS only on pages that render store components.
 */
function nabavi_dequeue_woocommerce_styles() {
	if ( is_admin() ) {
		return;
	}

	$is_store = function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() );

	if ( $is_store ) {
		return;
	}

	foreach ( array( 'woocommerce-general', 'woocommerce-layout', 'woocommerce-smallscreen', 'woocommerce-inline' ) as $handle ) {
		wp_dequeue_style( $handle );
	}
}
add_action( 'wp_enqueue_scripts', 'nabavi_dequeue_woocommerce_styles', 100 );
