<?php
/**
 * Helper functions: cache, options, icons and small utilities.
 *
 * @package Nabavi
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Caching
 * ---------------------------------------------------------------------- */

/**
 * Cache version. Bumping it invalidates every theme cache entry without
 * running expensive delete queries.
 *
 * @return int
 */
function nabavi_cache_version() {
	$version = get_option( 'nabavi_cache_version' );

	if ( ! $version ) {
		$version = time();
		update_option( 'nabavi_cache_version', $version, false );
	}

	return (int) $version;
}

/**
 * Build a namespaced cache key.
 *
 * @param string $key Raw key.
 * @return string
 */
function nabavi_cache_key( $key ) {
	return 'nabavi_' . md5( $key . '|' . nabavi_cache_version() );
}

/**
 * Read from cache or generate and store a value.
 *
 * Uses the object cache when a persistent cache backend is available,
 * otherwise falls back to transients so caching still works out of the box.
 *
 * @param string   $key        Raw cache key.
 * @param callable $callback   Value generator.
 * @param int      $expiration TTL in seconds.
 * @return mixed
 */
function nabavi_cache_get( $key, $callback, $expiration = HOUR_IN_SECONDS ) {
	$cache_key = nabavi_cache_key( $key );

	if ( wp_using_ext_object_cache() ) {
		$found = false;
		$value = wp_cache_get( $cache_key, 'nabavi', false, $found );

		if ( $found ) {
			return $value;
		}

		$value = call_user_func( $callback );
		wp_cache_set( $cache_key, $value, 'nabavi', $expiration );

		return $value;
	}

	$cached = get_transient( $cache_key );

	if ( false !== $cached ) {
		return $cached;
	}

	$value = call_user_func( $callback );
	set_transient( $cache_key, $value, $expiration );

	return $value;
}

/**
 * Delete one cache entry.
 *
 * @param string $key Raw cache key.
 */
function nabavi_cache_delete( $key ) {
	$cache_key = nabavi_cache_key( $key );

	if ( wp_using_ext_object_cache() ) {
		wp_cache_delete( $cache_key, 'nabavi' );
	}

	delete_transient( $cache_key );
}

/**
 * Invalidate all theme caches.
 */
function nabavi_cache_flush() {
	$version = (int) get_option( 'nabavi_cache_version' );
	$version = $version > 0 ? $version + 1 : time();

	update_option( 'nabavi_cache_version', $version, false );

	if ( wp_using_ext_object_cache() && function_exists( 'wp_cache_flush_group' ) ) {
		wp_cache_flush_group( 'nabavi' );
	}
}

/* -------------------------------------------------------------------------
 * WooCommerce helpers (safe when WooCommerce is inactive)
 * ---------------------------------------------------------------------- */

/**
 * Is WooCommerce active?
 *
 * @return bool
 */
function nabavi_is_woocommerce_active() {
	return class_exists( 'WooCommerce' );
}

/**
 * Shop URL.
 *
 * @return string
 */
function nabavi_get_shop_url() {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$url = wc_get_page_permalink( 'shop' );
		if ( $url ) {
			return $url;
		}
	}

	return home_url( '/shop/' );
}

/**
 * Cart URL.
 *
 * @return string
 */
function nabavi_get_cart_url() {
	if ( function_exists( 'wc_get_cart_url' ) ) {
		$url = wc_get_cart_url();

		if ( $url ) {
			return $url;
		}
	}

	return home_url( '/korpa/' );
}

/**
 * Checkout URL.
 *
 * @return string
 */
function nabavi_get_checkout_url() {
	if ( function_exists( 'wc_get_checkout_url' ) ) {
		$url = wc_get_checkout_url();

		if ( $url ) {
			return $url;
		}
	}

	return home_url( '/naplata/' );
}

/**
 * My account URL.
 *
 * @return string
 */
function nabavi_get_account_url() {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$url = wc_get_page_permalink( 'myaccount' );

		if ( $url ) {
			return $url;
		}
	}

	return home_url( '/moj-racun/' );
}

/**
 * Number of products in the cart.
 *
 * @return int
 */
function nabavi_get_cart_count() {
	if ( ! nabavi_is_woocommerce_active() || ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0;
	}

	return (int) WC()->cart->get_cart_contents_count();
}

/**
 * Brand taxonomy used by the store.
 *
 * Supports WooCommerce's native product_brand as well as common imports.
 *
 * @return string
 */
function nabavi_get_brand_taxonomy() {
	$candidates = array( 'product_brand', 'pa_brend', 'brand', 'product_brands' );

	foreach ( $candidates as $taxonomy ) {
		if ( taxonomy_exists( $taxonomy ) ) {
			return $taxonomy;
		}
	}

	return 'product_brand';
}

/**
 * Brand terms for a product.
 *
 * @param int $product_id Product ID.
 * @return array
 */
function nabavi_get_product_brands( $product_id ) {
	$taxonomy = nabavi_get_brand_taxonomy();

	if ( ! taxonomy_exists( $taxonomy ) ) {
		return array();
	}

	$terms = get_the_terms( $product_id, $taxonomy );

	return is_array( $terms ) ? $terms : array();
}

/**
 * Sorted terms with a case-insensitive name sort.
 *
 * @param string $taxonomy Taxonomy.
 * @param array  $args     get_terms arguments.
 * @return array
 */
function nabavi_get_terms_sorted( $taxonomy, $args = array() ) {
	$args['taxonomy'] = $taxonomy;

	$terms = get_terms( $args );

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return array();
	}

	usort(
		$terms,
		static function ( $a, $b ) {
			return strcasecmp( $a->name, $b->name );
		}
	);

	return $terms;
}

/* -------------------------------------------------------------------------
 * Theme option helpers
 * ---------------------------------------------------------------------- */

/**
 * Get a theme mod with a default.
 *
 * @param string $key     Theme mod key.
 * @param mixed  $default Default value.
 * @return mixed
 */
function nabavi_option( $key, $default = '' ) {
	return get_theme_mod( $key, $default );
}

/**
 * Contact phone.
 *
 * @return string
 */
function nabavi_get_phone() {
	return nabavi_option( 'nabavi_phone', '+387 61 306-122' );
}

/**
 * Contact email.
 *
 * @return string
 */
function nabavi_get_email() {
	return nabavi_option( 'nabavi_email', 'info@nabavi.ba' );
}

/**
 * Email address that receives contact form submissions.
 *
 * @return string
 */
function nabavi_get_contact_recipient() {
	$recipient = nabavi_option( 'nabavi_contact_recipient', nabavi_get_email() );

	return is_email( $recipient ) ? $recipient : get_option( 'admin_email' );
}

/**
 * Footer intro text.
 *
 * @return string
 */
function nabavi_get_footer_text() {
	return nabavi_option(
		'nabavi_footer_text',
		'Tvoja online destinacija za jednostavnu i sigurnu kupovinu, gdje najbolje ponude i kvalitet dolaze na dohvat ruke.'
	);
}

/**
 * Copyright line.
 *
 * @return string
 */
function nabavi_get_copyright() {
	return nabavi_option( 'nabavi_copyright', 'Sva prava zadržana | © ' . gmdate( 'Y' ) . ' Nabavi.ba' );
}

/**
 * Phone number formatted for a tel: link.
 *
 * @return string
 */
function nabavi_get_phone_href() {
	$phone = nabavi_get_phone();

	return preg_replace( '/[^0-9+]/', '', $phone );
}

/* -------------------------------------------------------------------------
 * Assets / images
 * ---------------------------------------------------------------------- */

/**
 * URL of a theme image.
 *
 * @param string $file File name.
 * @return string
 */
function nabavi_image_url( $file ) {
	return NABAVI_URI . '/assets/images/' . ltrim( $file, '/' );
}

/**
 * Path of a theme image.
 *
 * @param string $file File name.
 * @return string
 */
function nabavi_image_path( $file ) {
	return NABAVI_DIR . '/assets/images/' . ltrim( $file, '/' );
}

/**
 * URL of an image variant, falling back to the original file.
 *
 * @param string $file  Original file name.
 * @param int    $width Variant width.
 * @return string
 */
function nabavi_image_variant_url( $file, $width ) {
	$info    = pathinfo( $file );
	$variant = $info['filename'] . '-' . (int) $width . '.' . $info['extension'];

	if ( file_exists( nabavi_image_path( $variant ) ) ) {
		return nabavi_image_url( $variant );
	}

	return nabavi_image_url( $file );
}

/**
 * Preferred src for an image, using the width variant when it exists.
 *
 * @param string $file  Original file name.
 * @param int    $width Preferred width.
 * @return string
 */
function nabavi_image_src( $file, $width ) {
	return nabavi_image_variant_url( $file, $width );
}

/**
 * Build a srcset string from the original and the available width variants.
 *
 * @param string $file       Original file name.
 * @param int    $base_width Original intrinsic width.
 * @param array  $widths     Extra widths.
 * @return string
 */
function nabavi_image_srcset( $file, $base_width, $widths = array() ) {
	$info       = pathinfo( $file );
	$candidates = array( (int) $base_width => nabavi_image_url( $file ) );

	foreach ( $widths as $width ) {
		$width   = (int) $width;
		$variant = $info['filename'] . '-' . $width . '.' . $info['extension'];

		if ( file_exists( nabavi_image_path( $variant ) ) ) {
			$candidates[ $width ] = nabavi_image_url( $variant );
		}
	}

	ksort( $candidates );

	$srcset = array();

	foreach ( $candidates as $width => $url ) {
		$srcset[] = $url . ' ' . $width . 'w';
	}

	return implode( ', ', $srcset );
}

/* -------------------------------------------------------------------------
 * Inline SVG icons
 * ---------------------------------------------------------------------- */

/**
 * Return an inline SVG icon.
 *
 * @param string $name  Icon name.
 * @param string $class CSS class.
 * @return string
 */
function nabavi_get_icon( $name, $class = '' ) {
	$icons = array(
		'user'         => '<svg class="%1$s" width="24" height="24" viewBox="0 0 496 512" aria-hidden="true" focusable="false"><path fill="currentColor" d="M248 8C111 8 0 119 0 256s111 248 248 248 248-111 248-248S385 8 248 8zm0 96c48.6 0 88 39.4 88 88s-39.4 88-88 88-88-39.4-88-88 39.4-88 88-88zm0 344c-58.7 0-111.3-26.6-146.5-68.2 18.8-35.4 55.6-59.8 98.5-59.8 2.4 0 4.8.4 7.1 1.1 13 4.2 26.6 6.9 40.9 6.9 14.3 0 28-2.7 40.9-6.9 2.3-.7 4.7-1.1 7.1-1.1 42.9 0 79.7 24.4 98.5 59.8C359.3 421.4 306.7 448 248 448z"/></svg>',
		'cart'         => '<svg class="%1$s" width="24" height="24" viewBox="0 0 576 512" aria-hidden="true" focusable="false"><path fill="currentColor" d="M528.12 301.319l47.273-208C578.806 78.301 567.391 64 551.99 64H159.208l-9.166-44.81C147.758 8.021 137.93 0 126.529 0H24C10.745 0 0 10.745 0 24v16c0 13.255 10.745 24 24 24h69.883l70.248 343.435C147.325 417.1 136 435.222 136 456c0 30.928 25.072 56 56 56s56-25.072 56-56c0-15.674-6.447-29.835-16.824-40h209.647C430.447 426.165 424 440.326 424 456c0 30.928 25.072 56 56 56s56-25.072 56-56c0-22.172-12.888-41.332-31.579-50.405l5.517-24.276c3.413-15.018-8.002-29.319-23.403-29.319H218.117l-6.545-32h293.145c11.206 0 20.92-7.754 23.403-18.681z"/></svg>',
		'search'       => '<svg class="%1$s" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" d="M10.5 18a7.5 7.5 0 1 0 0-15 7.5 7.5 0 0 0 0 15Zm5.8.2L21 23"/></svg>',
		'chevron-down' => '<svg class="%1$s" width="10" height="10" viewBox="0 0 10 10" aria-hidden="true" focusable="false"><path fill="currentColor" d="M1.1 2.6 5 6.5l3.9-3.9.9.9L5 8.3.2 3.5z"/></svg>',
		'close'        => '<svg class="%1$s" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M5 5l14 14M19 5 5 19"/></svg>',
		'menu'         => '<svg class="%1$s" width="26" height="26" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M3 6h18M3 12h18M3 18h18"/></svg>',
		'filter'       => '<svg class="%1$s" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>',
		'eye'          => '<svg class="%1$s" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="1.8" d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>',
		'eye-off'      => '<svg class="%1$s" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="1.8" d="M3 3l18 18M10.6 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-2.1 3M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7c1.4 0 2.7-.3 3.8-.8"/></svg>',
		'phone'        => '<svg class="%1$s" width="18" height="18" viewBox="0 0 512 512" aria-hidden="true" focusable="false"><path fill="currentColor" d="M511.2 387l-23.25 100.8c-3.266 14.25-15.79 24.22-30.46 24.22C205.2 512 0 306.8 0 54.5c0-14.66 9.969-27.2 24.22-30.45l100.8-23.25c5.86-1.39 11.98 1.12 15.31 6.24l50.55 78.3c3.14 4.88 2.61 11.25-1.28 15.55L136.1 157.6c20.16 76.4 78.75 134.9 155.1 155.1l56.62-53.54c4.3-3.89 10.67-4.42 15.55-1.28l78.3 50.55c5.12 3.33 7.63 9.45 6.24 15.31z"/></svg>',
		'mail'         => '<svg class="%1$s" width="18" height="18" viewBox="0 0 512 512" aria-hidden="true" focusable="false"><path fill="currentColor" d="M464 64H48C21.49 64 0 85.49 0 112v288c0 26.51 21.49 48 48 48h416c26.51 0 48-21.49 48-48V112c0-26.51-21.49-48-48-48zm0 48v40.8L256 272 48 152.8V112h416zM48 400V193.6l198.8 114.4c5.6 3.2 12.8 3.2 18.4 0L464 193.6V400H48z"/></svg>',
		'arrow-left'   => '<svg class="%1$s" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M19 12H5m7-7-7 7 7 7"/></svg>',
		'arrow-right'  => '<svg class="%1$s" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-7-7 7 7-7 7"/></svg>',
		'check'        => '<svg class="%1$s" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" d="m4 12 5 5L20 6"/></svg>',
	);

	$svg = isset( $icons[ $name ] ) ? $icons[ $name ] : '';

	if ( ! $svg ) {
		return '';
	}

	$class = $class ? $class : 'nabavi-icon';
	$class = sanitize_html_class( $class );

	return sprintf( $svg, esc_attr( $class ) );
}

/* -------------------------------------------------------------------------
 * Body classes
 * ---------------------------------------------------------------------- */

/**
 * Add useful body classes.
 *
 * @param array $classes Existing classes.
 * @return array
 */
function nabavi_body_classes( $classes ) {
	$classes[] = 'nabavi-theme';

	if ( ! is_active_sidebar( 'sidebar-1' ) ) {
		$classes[] = 'nabavi-no-sidebar';
	}

	if ( nabavi_is_woocommerce_active() && ( is_cart() || is_checkout() || is_account_page() ) ) {
		$classes[] = 'nabavi-woo-page';
	}

	return $classes;
}
add_filter( 'body_class', 'nabavi_body_classes' );
