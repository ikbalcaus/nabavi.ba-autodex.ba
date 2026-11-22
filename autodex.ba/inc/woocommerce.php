<?php
/**
 * WooCommerce integration.
 *
 * @package Autodex
 */

defined( 'ABSPATH' ) || exit;

/*
 * Every function in this file is WooCommerce-specific. Bailing out here keeps
 * the theme fully functional (and fatal-error free) when the plugin is not
 * active. WooCommerce is always loaded before the theme, so this check is
 * reliable on activation.
 */
if ( ! autodex_is_woocommerce_active() ) {
	return;
}

/* -------------------------------------------------------------------------
 * Store settings
 * ---------------------------------------------------------------------- */

/**
 * Five products per row, like the original storefront.
 *
 * @return int
 */
function autodex_loop_columns() {
	return 5;
}

/**
 * Twenty products per archive page.
 *
 * @return int
 */
function autodex_products_per_page() {
	return 20;
}

/**
 * Catalog sorting labels.
 *
 * @param array $options Existing options.
 * @return array
 */
function autodex_catalog_orderby( $options ) {
	return array(
		'menu_order' => 'Relevantnost',
		'rating'     => 'Najviše ocjene',
		'title-asc'  => 'Naziv (A → Z)',
		'title-desc' => 'Naziv (Z → A)',
		'price-asc'  => 'Cijena (najniža → najviša)',
		'price-desc' => 'Cijena (najviša → najniža)',
	);
}

/**
 * Map the storefront's sorting keys to WooCommerce query arguments.
 *
 * @param array $args Ordering arguments.
 * @return array
 */
function autodex_catalog_ordering_args( $args ) {
	$orderby = isset( $_GET['orderby'] ) ? wc_clean( wp_unslash( $_GET['orderby'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	switch ( $orderby ) {
		case 'price-asc':
			$args['orderby']  = 'meta_value_num';
			$args['order']    = 'ASC';
			$args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			break;
		case 'price-desc':
			$args['orderby']  = 'meta_value_num';
			$args['order']    = 'DESC';
			$args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			break;
		case 'title-asc':
			$args['orderby'] = 'title';
			$args['order']   = 'ASC';
			break;
		case 'title-desc':
			$args['orderby'] = 'title';
			$args['order']   = 'DESC';
			break;
	}

	return $args;
}

add_filter( 'loop_shop_columns', 'autodex_loop_columns', 999 );
add_filter( 'loop_shop_per_page', 'autodex_products_per_page', 20 );
add_filter( 'woocommerce_catalog_orderby', 'autodex_catalog_orderby' );
add_filter( 'woocommerce_get_catalog_ordering_args', 'autodex_catalog_ordering_args' );
add_filter( 'woocommerce_coupons_enabled', 'autodex_disable_coupons' );
add_filter( 'woocommerce_cart_needs_shipping', 'autodex_cart_needs_shipping' );
add_action( 'init', 'autodex_remove_woocommerce_sidebar', 20 );

/**
 * Remove the default WooCommerce sidebar.
 */
function autodex_remove_woocommerce_sidebar() {
	remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
}

/**
 * Disable the coupon form (the shop does not use coupons).
 *
 * @return bool
 */
function autodex_disable_coupons() {
	return false;
}

/**
 * The store is pickup/delivery only with no shipping calculation.
 *
 * @return bool
 */
function autodex_cart_needs_shipping() {
	return false;
}

/* -------------------------------------------------------------------------
 * Store setup (pages, currency, classic shortcode layouts)
 * ---------------------------------------------------------------------- */

/**
 * Create or rename a WooCommerce page.
 *
 * @param string $option  WooCommerce option name.
 * @param string $slug    Desired slug.
 * @param string $title   Desired title.
 * @param string $content Page content.
 * @return int
 */
function autodex_ensure_store_page( $option, $slug, $title, $content = '' ) {
	$page_id = (int) get_option( $option );
	$page    = $page_id ? get_post( $page_id ) : null;

	if ( $page && 'page' === $page->post_type ) {
		$update = array();

		$default_slugs = array( 'cart', 'checkout', 'my-account', 'shop' );

		if ( $page->post_name !== $slug && in_array( $page->post_name, $default_slugs, true ) ) {
			$update['post_name'] = $slug;
		}

		$default_titles = array( 'Cart', 'Checkout', 'My account', 'Shop', 'Korpa', 'Naplata', 'Moj račun' );

		if ( in_array( $page->post_title, $default_titles, true ) && $page->post_title !== $title ) {
			$update['post_title'] = $title;
		}

		if ( $content && ( false !== strpos( $page->post_content, 'wp:woocommerce/' ) || false !== strpos( $page->post_content, 'elementor' ) || '' === trim( $page->post_content ) ) ) {
			$update['post_content'] = $content;
		}

		if ( ! empty( $update ) ) {
			$update['ID'] = $page_id;
			wp_update_post( $update );
		}

		$current_template = (string) get_page_template_slug( $page_id );

		if ( $current_template && false !== strpos( $current_template, 'elementor' ) ) {
			delete_post_meta( $page_id, '_wp_page_template' );
		}

		return $page_id;
	}

	$existing = get_page_by_path( $slug );

	if ( $existing ) {
		update_option( $option, $existing->ID );
		return (int) $existing->ID;
	}

	$page_id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => $content,
		)
	);

	if ( $page_id && ! is_wp_error( $page_id ) ) {
		update_option( $option, $page_id );
		return (int) $page_id;
	}

	return 0;
}

/**
 * Configure the store the first time the theme runs with WooCommerce.
 */
function autodex_setup_store() {
	if ( '4' === get_option( 'autodex_store_setup_version' ) ) {
		return;
	}

	autodex_ensure_store_page( 'woocommerce_shop_page_id', 'shop', 'Shop' );
	autodex_ensure_store_page( 'woocommerce_cart_page_id', 'korpa', 'Korpa', '[woocommerce_cart]' );
	autodex_ensure_store_page( 'woocommerce_checkout_page_id', 'naplata', 'Naplata', '[woocommerce_checkout]' );
	autodex_ensure_store_page( 'woocommerce_myaccount_page_id', 'moj-racun', 'Moj račun', '[woocommerce_my_account]' );

	update_option( 'woocommerce_currency', 'BAM' );
	update_option( 'woocommerce_currency_pos', 'right_space' );
	update_option( 'woocommerce_price_num_decimals', 2 );
	update_option( 'woocommerce_price_decimal_sep', ',' );
	update_option( 'woocommerce_price_thousand_sep', '.' );
	update_option( 'woocommerce_default_country', 'BA' );
	update_option( 'woocommerce_brand_permalink', 'brend' );
	update_option( 'woocommerce_calc_taxes', 'no' );
	update_option( 'woocommerce_enable_coupons', 'no' );
	update_option( 'woocommerce_cart_redirect_after_add', 'no' );
	update_option( 'woocommerce_enable_ajax_add_to_cart', 'yes' );
	update_option( 'woocommerce_enable_guest_checkout', 'yes' );
	update_option( 'woocommerce_enable_myaccount_registration', 'no' );
	update_option( 'woocommerce_enable_checkout_login_reminder', 'no' );
	update_option( 'woocommerce_default_catalog_orderby', 'menu_order' );
	update_option( 'woocommerce_catalog_columns', 5 );
	update_option( 'woocommerce_catalog_rows', 4 );

	update_option(
		'woocommerce_cod_settings',
		array(
			'enabled'      => 'yes',
			'title'        => 'Plaćanje pouzećem',
			'description'  => 'Plati gotovinom prilikom dostave.',
			'instructions' => 'Plati gotovinom prilikom dostave.',
		)
	);

	$permalinks = get_option( 'woocommerce_permalinks', array() );
	$permalinks = is_array( $permalinks ) ? $permalinks : array();

	$permalinks['product_base']   = '/proizvod';
	$permalinks['category_base']  = '/shop';
	$permalinks['tag_base']       = '/tag';
	$permalinks['attribute_base'] = '';

	update_option( 'woocommerce_permalinks', $permalinks );

	update_option( 'autodex_store_setup_version', '4', false );
	flush_rewrite_rules();
}
add_action( 'init', 'autodex_setup_store', 20 );
add_action( 'after_switch_theme', 'autodex_setup_store', 30 );
add_action( 'woocommerce_activated_plugin', 'autodex_setup_store', 30 );

/* -------------------------------------------------------------------------
 * Search
 * ---------------------------------------------------------------------- */

/**
 * Redirect regular searches to the product archive.
 */
function autodex_redirect_search() {
	if ( is_admin() || ! is_search() ) {
		return;
	}

	$search_term = get_query_var( 's' );

	if ( ! $search_term ) {
		return;
	}

	$is_product_search = isset( $_GET['post_type'] ) && is_string( $_GET['post_type'] ) && 'product' === sanitize_key( wp_unslash( $_GET['post_type'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( $is_product_search ) {
		return;
	}

	$shop_url = autodex_get_shop_url();

	wp_safe_redirect(
		add_query_arg(
			array(
				'post_type' => 'product',
				's'         => $search_term,
			),
			$shop_url
		)
	);
	exit;
}
add_action( 'template_redirect', 'autodex_redirect_search', 5 );

/**
 * Search only products.
 *
 * @param WP_Query $query Query.
 */
function autodex_search_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
		return;
	}

	$query->set( 'post_type', 'product' );
	$query->set( 'posts_per_page', 20 );
}
add_action( 'pre_get_posts', 'autodex_search_query' );

/**
 * Use the shop archive template for search results.
 *
 * @param string $template Template path.
 * @return string
 */
function autodex_search_template( $template ) {
	if ( is_admin() || ! is_search() ) {
		return $template;
	}

	$shop_template = locate_template( 'woocommerce/archive-product.php' );

	return $shop_template ? $shop_template : $template;
}
add_filter( 'template_include', 'autodex_search_template', 99 );

/* -------------------------------------------------------------------------
 * Account endpoints and redirects
 * ---------------------------------------------------------------------- */

/**
 * Localized My Account endpoint slugs.
 *
 * @param array $vars WooCommerce query vars.
 * @return array
 */
function autodex_localize_account_endpoints( $vars ) {
	$map = array(
		'orders'             => 'narudzbe',
		'downloads'          => 'preuzimanja',
		'edit-address'       => 'adrese',
		'edit-account'       => 'detalji-naloga',
		'customer-logout'    => 'odjavi-se',
		'lost-password'      => 'zaboravljena-sifra',
		'add-payment-method' => 'dodaj-nacin-placanja',
	);

	foreach ( $map as $endpoint => $slug ) {
		if ( isset( $vars[ $endpoint ] ) ) {
			$vars[ $endpoint ] = $slug;
		}
	}

	return $vars;
}
add_filter( 'woocommerce_get_query_vars', 'autodex_localize_account_endpoints' );

/**
 * Flush rewrite rules once after the endpoint slugs change.
 */
function autodex_maybe_flush_endpoints() {
	$version = '1';

	if ( get_option( 'autodex_endpoints_version' ) !== $version ) {
		flush_rewrite_rules();
		update_option( 'autodex_endpoints_version', $version, false );
	}
}
add_action( 'init', 'autodex_maybe_flush_endpoints', 99 );

/**
 * Redirect legacy endpoint URLs to the localized ones.
 */
function autodex_redirect_legacy_account_endpoints() {
	if ( is_admin() || ! function_exists( 'wc_get_page_permalink' ) ) {
		return;
	}

	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$path        = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
	$account_url = (string) wp_parse_url( autodex_get_account_url(), PHP_URL_PATH );
	$path        = trim( $path, '/' );
	$account_url = trim( $account_url, '/' );

	if ( ! $account_url || 0 !== strpos( $path, $account_url . '/' ) ) {
		return;
	}

	$endpoint = substr( $path, strlen( $account_url ) + 1 );
	$endpoint = trim( $endpoint, '/' );

	$map = array(
		'orders'          => 'narudzbe',
		'downloads'       => 'preuzimanja',
		'edit-address'    => 'adrese',
		'edit-account'    => 'detalji-naloga',
		'lost-password'   => 'zaboravljena-sifra',
		'customer-logout' => 'odjavi-se',
	);

	if ( isset( $map[ $endpoint ] ) && $map[ $endpoint ] !== $endpoint ) {
		wp_safe_redirect( trailingslashit( autodex_get_account_url() ) . $map[ $endpoint ] . '/' );
		exit;
	}
}
add_action( 'template_redirect', 'autodex_redirect_legacy_account_endpoints', 6 );

/**
 * Send logged-in customers straight to their orders.
 */
function autodex_account_dashboard_redirect() {
	if ( is_admin() || ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
		return;
	}

	if ( ! is_user_logged_in() || ! function_exists( 'is_wc_endpoint_url' ) ) {
		return;
	}

	if ( ! is_wc_endpoint_url() ) {
		wp_safe_redirect( wc_get_account_endpoint_url( 'orders' ) );
		exit;
	}
}
add_action( 'template_redirect', 'autodex_account_dashboard_redirect', 7 );

/**
 * Account menu labels and order.
 *
 * @param array $items Menu items.
 * @return array
 */
function autodex_account_menu_items( $items ) {
	unset( $items['dashboard'] );

	if ( isset( $items['orders'] ) ) {
		$items['orders'] = 'Narudžbe';
	}

	if ( isset( $items['downloads'] ) ) {
		$items['downloads'] = 'Preuzimanja';
	}

	if ( isset( $items['edit-address'] ) ) {
		$items['edit-address'] = 'Adrese';
	}

	if ( isset( $items['edit-account'] ) ) {
		$items['edit-account'] = 'Detalji naloga';
	}

	if ( isset( $items['customer-logout'] ) ) {
		$items['customer-logout'] = 'Odjavi se';
	}

	return $items;
}
add_filter( 'woocommerce_account_menu_items', 'autodex_account_menu_items' );

/**
 * Translate endpoint page titles.
 *
 * @param string $title Title.
 * @return string
 */
function autodex_account_endpoint_title( $title ) {
	if ( function_exists( 'is_wc_endpoint_url' ) ) {
		if ( is_wc_endpoint_url( 'orders' ) ) {
			return 'Narudžbe';
		}

		if ( is_wc_endpoint_url( 'downloads' ) ) {
			return 'Preuzimanja';
		}

		if ( is_wc_endpoint_url( 'edit-address' ) ) {
			return 'Adrese';
		}

		if ( is_wc_endpoint_url( 'edit-account' ) ) {
			return 'Detalji naloga';
		}
	}

	return $title;
}
add_filter( 'woocommerce_endpoint_title', 'autodex_account_endpoint_title' );
add_filter( 'woocommerce_endpoint_orders_title', 'autodex_account_endpoint_title' );
add_filter( 'woocommerce_endpoint_downloads_title', 'autodex_account_endpoint_title' );
add_filter( 'woocommerce_endpoint_edit-address_title', 'autodex_account_endpoint_title' );
add_filter( 'woocommerce_endpoint_edit-account_title', 'autodex_account_endpoint_title' );

/* -------------------------------------------------------------------------
 * Product loop and single product
 * ---------------------------------------------------------------------- */

/**
 * Product stock line on the single product page.
 *
 * Mirrors the original store, which displays the product name in place of
 * the stock status.
 *
 * @param string     $html    Stock HTML.
 * @param WC_Product $product Product.
 * @return string
 */
function autodex_product_stock_html( $html, $product ) {
	if ( is_product() && $product instanceof WC_Product ) {
		return '<p class="stock in-stock">' . esc_html( $product->get_name() ) . '</p>';
	}

	return $html;
}
add_filter( 'woocommerce_get_stock_html', 'autodex_product_stock_html', 10, 2 );

/**
 * Single add-to-cart label.
 *
 * @return string
 */
function autodex_single_add_to_cart_text() {
	return 'Dodaj u korpu';
}
add_filter( 'woocommerce_product_single_add_to_cart_text', 'autodex_single_add_to_cart_text' );

/**
 * Loop add-to-cart label.
 *
 * @return string
 */
function autodex_loop_add_to_cart_text() {
	return 'Dodaj u korpu';
}
add_filter( 'woocommerce_product_add_to_cart_text', 'autodex_loop_add_to_cart_text' );

/**
 * Replace loop button labels for templates that use the default helper.
 *
 * @param string     $html    Button HTML.
 * @param WC_Product $product Product.
 * @param array      $args    Arguments.
 * @return string
 */
function autodex_loop_add_to_cart_link( $html, $product, $args ) {
	return preg_replace( '/>(.*?)</', '>Dodaj u korpu<', $html );
}
add_filter( 'woocommerce_loop_add_to_cart_link', 'autodex_loop_add_to_cart_link', 99, 3 );

/**
 * Sale badge label.
 *
 * @param string $html Sale flash HTML.
 * @return string
 */
function autodex_sale_flash( $html ) {
	return '<span class="onsale">Akcija</span>';
}
add_filter( 'woocommerce_sale_flash', 'autodex_sale_flash' );

/**
 * Related products: four columns, four products.
 *
 * @param array $args Arguments.
 * @return array
 */
function autodex_related_products_args( $args ) {
	$args['posts_per_page'] = 4;
	$args['columns']        = 4;

	return $args;
}
add_filter( 'woocommerce_output_related_products_args', 'autodex_related_products_args' );

/**
 * Upsells: four columns.
 *
 * @param array $args Arguments.
 * @return array
 */
function autodex_upsell_products_args( $args ) {
	$args['columns'] = 4;

	return $args;
}
add_filter( 'woocommerce_upsell_display_args', 'autodex_upsell_products_args' );

/**
 * Do not show the default WooCommerce page title.
 *
 * @return bool
 */
function autodex_hide_woocommerce_page_title() {
	return false;
}
add_filter( 'woocommerce_show_page_title', 'autodex_hide_woocommerce_page_title' );

/**
 * Out-of-stock badge label.
 *
 * @return string
 */
function autodex_out_of_stock_label() {
	if ( is_shop() || is_product_category() || is_product_tag() || is_tax( autodex_get_brand_taxonomy() ) || is_product() ) {
		return 'Rasprodano';
	}

	return 'SOLD OUT';
}

/**
 * Product meta block (category and brand, no tags).
 */
function autodex_product_meta_output() {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$categories = wc_get_product_category_list( $product->get_id(), ', ' );
	$brands     = autodex_get_product_brands( $product->get_id() );

	echo '<div class="product_meta autodex-product-meta">';

	if ( $categories ) {
		echo '<span class="posted_in"><strong>Kategorija:</strong> ' . wp_kses_post( $categories ) . '</span>';
	}

	if ( ! empty( $brands ) ) {
		$brand_links = array();

		foreach ( $brands as $brand ) {
			$link = get_term_link( $brand );

			if ( ! is_wp_error( $link ) ) {
				$brand_links[] = '<a href="' . esc_url( $link ) . '" rel="tag">' . esc_html( $brand->name ) . '</a>';
			}
		}

		if ( $brand_links ) {
			echo '<span class="posted_in"><strong>Brend:</strong> ' . wp_kses_post( implode( ', ', $brand_links ) ) . '</span>';
		}
	}

	if ( current_user_can( 'manage_options' ) ) {
		$tags = wc_get_product_tag_list( $product->get_id(), ', ' );

		if ( $tags ) {
			echo '<span class="tagged_as"><strong>Oznaka:</strong> ' . wp_kses_post( $tags ) . '</span>';
		}
	}

	echo '</div>';
}

/**
 * Custom product gallery.
 *
 * @param WC_Product $product Product.
 */
function autodex_product_gallery( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$main_id  = (int) $product->get_image_id();
	$gallery  = array_filter( array_map( 'intval', (array) $product->get_gallery_image_ids() ) );
	$all_ids  = array_values( array_unique( array_merge( $main_id ? array( $main_id ) : array(), $gallery ) ) );
	$main_url = $main_id ? wp_get_attachment_image_url( $main_id, 'woocommerce_single' ) : '';
	$main_alt = $product->get_name();

	echo '<div class="autodex-gallery">';
	echo '<figure class="autodex-gallery__main">';

	if ( $main_id ) {
		echo wp_get_attachment_image(
			$main_id,
			'woocommerce_single',
			false,
			array(
				'class'         => 'autodex-gallery__image',
				'alt'           => $main_alt,
				'loading'       => 'eager',
				'fetchpriority' => 'high',
			)
		);
	} else {
		echo autodex_product_placeholder(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	echo '</figure>';

	if ( count( $all_ids ) > 1 ) {
		echo '<div class="autodex-gallery__thumbs" role="list">';

		foreach ( $all_ids as $image_id ) {
			$thumb = wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' );
			$full  = wp_get_attachment_image_url( $image_id, 'woocommerce_single' );

			if ( ! $thumb ) {
				continue;
			}

			printf(
				'<button type="button" class="autodex-gallery__thumb%1$s" data-image="%2$s" data-srcset="%3$s" aria-label="%4$s" role="listitem">%5$s</button>',
				$image_id === $main_id ? ' is-active' : '',
				esc_url( $full ? $full : $thumb ),
				esc_attr( wp_get_attachment_image_srcset( $image_id, 'woocommerce_single' ) ),
				esc_attr__( 'Prikaži sliku', 'autodex' ),
				wp_get_attachment_image( $image_id, 'woocommerce_thumbnail', false, array( 'alt' => $main_alt ) )
			);
		}

		echo '</div>';
	}

	echo '</div>';
}

/**
 * Count product views for the "Najpopularnije" section.
 *
 * Counts a product at most once per visitor per day and skips obvious bots,
 * so normal traffic does not trigger a database write on every page view.
 */
function autodex_count_product_view() {
	if ( ! function_exists( 'is_product' ) || ! is_product() || is_admin() || wp_doing_ajax() || is_preview() ) {
		return;
	}

	if ( current_user_can( 'edit_products' ) ) {
		return;
	}

	$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) : '';

	if ( $user_agent && preg_match( '/bot|crawl|spider|slurp|bing|yandex|baidu|duckduck|facebookexternalhit|semrush|ahrefs/i', $user_agent ) ) {
		return;
	}

	$product_id = get_queried_object_id();

	if ( ! $product_id ) {
		return;
	}

	$cookie_name = 'autodex_viewed';
	$viewed      = isset( $_COOKIE[ $cookie_name ] ) ? explode( ',', sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) ) ) : array();
	$viewed      = array_values( array_filter( array_map( 'absint', $viewed ) ) );

	if ( in_array( $product_id, $viewed, true ) ) {
		return;
	}

	$viewed[] = $product_id;
	$viewed   = array_slice( $viewed, -40 );

	$views = (int) get_post_meta( $product_id, 'sk_pregledi_proizvoda', true );
	update_post_meta( $product_id, 'sk_pregledi_proizvoda', $views + 1 );

	if ( ! headers_sent() ) {
		setcookie(
			$cookie_name,
			implode( ',', $viewed ),
			time() + DAY_IN_SECONDS,
			COOKIEPATH ? COOKIEPATH : '/',
			COOKIE_DOMAIN,
			is_ssl(),
			true
		);
	}
}
add_action( 'template_redirect', 'autodex_count_product_view', 20 );

/* -------------------------------------------------------------------------
 * Shop sidebar: price, categories, brands
 * ---------------------------------------------------------------------- */

/**
 * Price range for the current shop/category.
 *
 * @param int $term_id Product category ID, 0 for the whole shop.
 * @return array{min:int,max:int}
 */
function autodex_get_price_range( $term_id = 0 ) {
	$term_id = (int) $term_id;

	return autodex_cache_get(
		'price_range_' . $term_id,
		static function () use ( $term_id ) {
			global $wpdb;

			if ( $term_id > 0 ) {
				$sql = "
					SELECT
						MIN(CAST(pm.meta_value AS DECIMAL(10,2))) AS min_price,
						MAX(CAST(pm.meta_value AS DECIMAL(10,2))) AS max_price
					FROM {$wpdb->postmeta} pm
					INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
					INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
					INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
					WHERE pm.meta_key = '_price'
						AND p.post_type = 'product'
						AND p.post_status = 'publish'
						AND tt.taxonomy = 'product_cat'
						AND tt.term_id = %d
						AND pm.meta_value > 0
				";

				$row = $wpdb->get_row( $wpdb->prepare( $sql, $term_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			} else {
				$sql = "
					SELECT
						MIN(CAST(pm.meta_value AS DECIMAL(10,2))) AS min_price,
						MAX(CAST(pm.meta_value AS DECIMAL(10,2))) AS max_price
					FROM {$wpdb->postmeta} pm
					INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
					WHERE pm.meta_key = '_price'
						AND p.post_type = 'product'
						AND p.post_status = 'publish'
						AND pm.meta_value > 0
				";

				$row = $wpdb->get_row( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}

			$min_price = ( $row && isset( $row->min_price ) && null !== $row->min_price ) ? (float) $row->min_price : 0;
			$max_price = ( $row && isset( $row->max_price ) && null !== $row->max_price ) ? (float) $row->max_price : 1000;

			return array(
				'min' => $min_price > 0 ? (int) floor( $min_price ) : 0,
				'max' => $max_price > 0 ? (int) ceil( $max_price ) : 1000,
			);
		},
		HOUR_IN_SECONDS
	);
}

/**
 * Render the sidebar price filter.
 */
function autodex_render_price_filter() {
	$term_id = function_exists( 'is_product_category' ) && is_product_category() ? (int) get_queried_object_id() : 0;
	$range   = autodex_get_price_range( $term_id );

	$min_value = isset( $_GET['min_price'] ) && is_numeric( wp_unslash( $_GET['min_price'] ) ) ? (float) wc_clean( wp_unslash( $_GET['min_price'] ) ) : $range['min']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$max_value = isset( $_GET['max_price'] ) && is_numeric( wp_unslash( $_GET['max_price'] ) ) ? (float) wc_clean( wp_unslash( $_GET['max_price'] ) ) : $range['max']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	$min_value = max( $range['min'], min( $range['max'], $min_value ) );
	$max_value = max( $range['min'], min( $range['max'], $max_value ) );

	if ( $min_value >= $max_value ) {
		$min_value = $range['min'];
		$max_value = $range['max'];
	}

	$min_value = (int) floor( $min_value );
	$max_value = (int) ceil( $max_value );
	?>
	<div class="price-filter">
		<br>
		<p class="filter_by">Filtriraj po cijeni</p>
		<br>

		<div class="drange">
			<input type="range" id="min-price" min="<?php echo esc_attr( $range['min'] ); ?>" max="<?php echo esc_attr( $range['max'] ); ?>" value="<?php echo esc_attr( $min_value ); ?>" aria-label="<?php esc_attr_e( 'Najniža cijena', 'autodex' ); ?>">
			<input type="range" id="max-price" min="<?php echo esc_attr( $range['min'] ); ?>" max="<?php echo esc_attr( $range['max'] ); ?>" value="<?php echo esc_attr( $max_value ); ?>" aria-label="<?php esc_attr_e( 'Najviša cijena', 'autodex' ); ?>">
			<div class="dmin"><?php echo esc_html( $min_value ); ?></div>
			<div class="dmax"><?php echo esc_html( $max_value ); ?></div>
		</div>

		<div class="price-inputs">
			<div class="price-input-wrap">
				<span class="price-prefix">OD</span>
				<input type="number" id="input-min-price" min="<?php echo esc_attr( $range['min'] ); ?>" max="<?php echo esc_attr( $range['max'] ); ?>" value="<?php echo esc_attr( $min_value ); ?>" aria-label="<?php esc_attr_e( 'Najniža cijena', 'autodex' ); ?>">
				<span class="price-suffix">KM</span>
			</div>

			<div class="price-input-wrap">
				<span class="price-prefix">DO</span>
				<input type="number" id="input-max-price" min="<?php echo esc_attr( $range['min'] ); ?>" max="<?php echo esc_attr( $range['max'] ); ?>" value="<?php echo esc_attr( $max_value ); ?>" aria-label="<?php esc_attr_e( 'Najviša cijena', 'autodex' ); ?>">
				<span class="price-suffix">KM</span>
			</div>
		</div>

		<button id="price-filter-submit" type="button">Potvrdi</button>
	</div>
	<br>
	<?php
}

/**
 * Apply min/max price filters to the main shop query.
 *
 * @param WP_Query $query Query.
 */
function autodex_price_filter_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( ! ( is_shop() || is_product_category() || is_product_tag() || is_search() || is_tax( autodex_get_brand_taxonomy() ) ) ) {
		return;
	}

	$min_price = isset( $_GET['min_price'] ) && is_numeric( wp_unslash( $_GET['min_price'] ) ) ? (float) wc_clean( wp_unslash( $_GET['min_price'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$max_price = isset( $_GET['max_price'] ) && is_numeric( wp_unslash( $_GET['max_price'] ) ) ? (float) wc_clean( wp_unslash( $_GET['max_price'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( $min_price <= 0 && $max_price <= 0 ) {
		return;
	}

	if ( $min_price > 0 && $max_price > 0 && $min_price > $max_price ) {
		$temp      = $min_price;
		$min_price = $max_price;
		$max_price = $temp;
	}

	$meta_query = $query->get( 'meta_query' );

	if ( ! is_array( $meta_query ) ) {
		$meta_query = array();
	}

	if ( $min_price > 0 ) {
		$meta_query[] = array(
			'key'     => '_price',
			'value'   => $min_price,
			'compare' => '>=',
			'type'    => 'NUMERIC',
		);
	}

	if ( $max_price > 0 ) {
		$meta_query[] = array(
			'key'     => '_price',
			'value'   => $max_price,
			'compare' => '<=',
			'type'    => 'NUMERIC',
		);
	}

	$query->set( 'meta_query', $meta_query );
}
add_action( 'pre_get_posts', 'autodex_price_filter_query' );

/**
 * Render shop/category links in the sidebar.
 */
function autodex_render_shop_categories() {
	$current_term = null;

	if ( is_product_category() ) {
		$current_term = get_queried_object();
	}

	if ( is_shop() ) {
		$categories = autodex_get_terms_sorted(
			'product_cat',
			array(
				'parent'     => 0,
				'hide_empty' => true,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		$label = 'Filtriraj po kategoriji:';
	} elseif ( $current_term instanceof WP_Term ) {
		$categories = autodex_get_terms_sorted(
			'product_cat',
			array(
				'parent'     => $current_term->term_id,
				'hide_empty' => true,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		$label = 'Filtriraj po kategoriji:';
	} else {
		return;
	}

	if ( empty( $categories ) ) {
		return;
	}
	?>
	<div class="shop-subcategories">
		<p class="filter_by"><?php echo esc_html( $label ); ?></p>
		<ul class="filter-list">
			<?php foreach ( $categories as $category ) : ?>
				<li>
					<a href="<?php echo esc_url( get_term_link( $category ) ); ?>"><?php echo esc_html( $category->name ); ?></a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<br>
	<?php
}

/**
 * Render the brand filter on category pages.
 */
function autodex_render_brand_filter() {
	if ( ! is_product_category() ) {
		return;
	}

	$taxonomy = autodex_get_brand_taxonomy();

	if ( ! taxonomy_exists( $taxonomy ) ) {
		return;
	}

	$current_term = get_queried_object();

	if ( ! $current_term instanceof WP_Term ) {
		return;
	}

	$brands = autodex_cache_get(
		'brands_' . $taxonomy . '_' . $current_term->term_id,
		static function () use ( $taxonomy, $current_term ) {
			$term_ids = array( $current_term->term_id );
			$children = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => false,
					'parent'     => $current_term->term_id,
					'fields'     => 'ids',
				)
			);

			if ( ! is_wp_error( $children ) && ! empty( $children ) ) {
				$term_ids = array_merge( $term_ids, $children );
			}

			$product_ids = get_objects_in_term( $term_ids, 'product_cat' );

			if ( is_wp_error( $product_ids ) || empty( $product_ids ) ) {
				return array();
			}

			return autodex_get_terms_sorted(
				$taxonomy,
				array(
					'hide_empty' => true,
					'orderby'    => 'name',
					'order'      => 'ASC',
					'object_ids' => array_map( 'intval', $product_ids ),
				)
			);
		},
		HOUR_IN_SECONDS
	);

	if ( empty( $brands ) ) {
		return;
	}
	?>
	<div class="shop-brands">
		<p class="filter_by">Filtriraj po brendu:</p>
		<ul class="filter-list">
			<?php foreach ( $brands as $brand ) : ?>
				<li>
					<a href="<?php echo esc_url( get_term_link( $brand ) ); ?>"><?php echo esc_html( $brand->name ); ?></a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<br>
	<?php
}

/* -------------------------------------------------------------------------
 * Cart, checkout, payment and fragments
 * ---------------------------------------------------------------------- */

/**
 * Update the header cart counter through WooCommerce fragments.
 *
 * @param array $fragments Fragments.
 * @return array
 */
function autodex_cart_count_fragment( $fragments ) {
	$count = autodex_get_cart_count();

	$fragments['span.autodex-cart-count'] = '<span class="autodex-cart-count">' . esc_html( $count ) . '</span>';

	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'autodex_cart_count_fragment' );

/**
 * COD payment gateway title.
 *
 * @param string $title      Title.
 * @param string $gateway_id Gateway ID.
 * @return string
 */
function autodex_gateway_title( $title, $gateway_id ) {
	if ( 'cod' === $gateway_id ) {
		return 'Plaćanje pouzećem';
	}

	return $title;
}
add_filter( 'woocommerce_gateway_title', 'autodex_gateway_title', 20, 2 );

/**
 * COD payment gateway description.
 *
 * @param string $description Description.
 * @param string $gateway_id  Gateway ID.
 * @return string
 */
function autodex_gateway_description( $description, $gateway_id ) {
	if ( 'cod' === $gateway_id ) {
		return 'Plati gotovinom prilikom dostave.';
	}

	return $description;
}
add_filter( 'woocommerce_gateway_description', 'autodex_gateway_description', 20, 2 );

/**
 * Localized country name for Bosnia and Herzegovina.
 *
 * @param array $countries Countries.
 * @return array
 */
function autodex_countries( $countries ) {
	if ( isset( $countries['BA'] ) ) {
		$countries['BA'] = 'Bosna i Hercegovina';
	}

	return $countries;
}
add_filter( 'woocommerce_countries', 'autodex_countries' );

/**
 * Checkout field labels and placeholders.
 *
 * @param array $fields Checkout fields.
 * @return array
 */
function autodex_checkout_fields( $fields ) {
	$labels = array(
		'billing_first_name' => 'Ime',
		'billing_last_name'  => 'Prezime',
		'billing_company'    => 'Naziv firme',
		'billing_country'    => 'Država / Region',
		'billing_address_1'  => 'Ulica i broj',
		'billing_address_2'  => 'Stan, apartman, jedinica',
		'billing_postcode'   => 'Poštanski broj',
		'billing_city'       => 'Grad',
		'billing_state'      => 'Kanton',
		'billing_phone'      => 'Telefon',
		'billing_email'      => 'Email adresa',
	);

	foreach ( $labels as $key => $label ) {
		if ( isset( $fields['billing'][ $key ] ) ) {
			$fields['billing'][ $key ]['label']       = $label;
			$fields['billing'][ $key ]['placeholder'] = $label;
		}
	}

	if ( isset( $fields['order']['order_comments'] ) ) {
		$fields['order']['order_comments']['label']       = 'Bilješke o narudžbi';
		$fields['order']['order_comments']['placeholder'] = 'Bilješke o vašoj narudžbi, npr. posebne napomene za dostavu.';
	}

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'autodex_checkout_fields' );

/**
 * Checkout privacy text.
 *
 * @param string $text Privacy text.
 * @param string $type checkout|registration.
 * @return string
 */
function autodex_checkout_privacy_text( $text, $type = 'checkout' ) {
	if ( 'checkout' !== $type && 'registration' !== $type ) {
		return $text;
	}

	$privacy_url = get_privacy_policy_url();

	if ( ! $privacy_url ) {
		return 'Vaši lični podaci će biti korišteni za obradu vaše narudžbe, podršku vašeg iskustva na ovoj web stranici i u druge svrhe opisane u našoj politici privatnosti.';
	}

	return 'Vaši lični podaci će biti korišteni za obradu vaše narudžbe, podršku vašeg iskustva na ovoj web stranici i za druge svrhe opisane u našim <a href="' . esc_url( $privacy_url ) . '" target="_blank" rel="noopener">pravilima o privatnosti</a>.';
}
add_filter( 'woocommerce_get_privacy_policy_text', 'autodex_checkout_privacy_text', 10, 2 );

/**
 * Remove the "Naplata " prefix from checkout error messages.
 *
 * @param string $error Error message.
 * @return string
 */
function autodex_fix_checkout_error_prefix( $error ) {
	return str_replace( 'Naplata ', '', $error );
}
add_filter( 'woocommerce_add_error', 'autodex_fix_checkout_error_prefix', 9999 );

/* -------------------------------------------------------------------------
 * Cache invalidation
 * ---------------------------------------------------------------------- */

/**
 * Flush theme caches when catalog data changes.
 */
function autodex_flush_catalog_cache() {
	autodex_cache_flush();
}

add_action( 'woocommerce_update_product', 'autodex_flush_catalog_cache' );
add_action( 'woocommerce_new_product', 'autodex_flush_catalog_cache' );
add_action( 'woocommerce_delete_product', 'autodex_flush_catalog_cache' );
add_action( 'woocommerce_product_set_stock', 'autodex_flush_catalog_cache' );
add_action( 'woocommerce_variation_set_stock', 'autodex_flush_catalog_cache' );
add_action( 'created_product_cat', 'autodex_flush_catalog_cache' );
add_action( 'edited_product_cat', 'autodex_flush_catalog_cache' );
add_action( 'delete_product_cat', 'autodex_flush_catalog_cache' );
add_action( 'created_product_brand', 'autodex_flush_catalog_cache' );
add_action( 'edited_product_brand', 'autodex_flush_catalog_cache' );
add_action( 'delete_product_brand', 'autodex_flush_catalog_cache' );
add_action( 'woocommerce_settings_saved', 'autodex_flush_catalog_cache' );
add_action( 'wp_update_nav_menu', 'autodex_flush_catalog_cache' );
add_action( 'save_post_page', 'autodex_flush_catalog_cache' );
add_action( 'deleted_post', 'autodex_flush_catalog_cache' );

/* -------------------------------------------------------------------------
 * Housekeeping
 * ---------------------------------------------------------------------- */

/**
 * Remove the default loop notice wrappers from the archive header; notices
 * are printed inside the custom shop layout instead.
 */
function autodex_remove_shop_notices() {
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_output_all_notices', 10 );
}
add_action( 'init', 'autodex_remove_shop_notices' );

/**
 * Adjust the shop loop item classes.
 *
 * @param array $classes Classes.
 * @return array
 */
function autodex_product_loop_classes( $classes ) {
	$classes[] = 'autodex-product-card';

	return $classes;
}
add_filter( 'woocommerce_post_class', 'autodex_product_loop_classes' );

/**
 * Hide the account privacy text.
 */
function autodex_hide_account_privacy_css() {
	if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
		return;
	}
	?>
	<style id="autodex-account-privacy">.woocommerce-privacy-policy-text{display:none!important}</style>
	<?php
}
add_action( 'wp_head', 'autodex_hide_account_privacy_css' );

/**
 * Hide product tags from non-admins.
 */
function autodex_hide_product_tags_css() {
	if ( current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<style id="autodex-product-tags">div.product_meta span.tagged_as{display:none!important}</style>
	<?php
}
add_action( 'wp_head', 'autodex_hide_product_tags_css' );
