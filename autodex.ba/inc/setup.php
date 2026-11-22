<?php
/**
 * Theme setup.
 *
 * @package Autodex
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $content_width ) ) {
	$content_width = 1290;
}

/**
 * Register theme supports, menus and image sizes.
 */
function autodex_setup() {
	load_theme_textdomain( 'autodex', AUTODEX_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );

	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
			'navigation-widgets',
		)
	);

	add_theme_support(
		'custom-logo',
		array(
			'height'      => 120,
			'width'       => 300,
			'flex-height' => true,
			'flex-width'  => true,
			'header-text' => array( 'site-title', 'site-description' ),
		)
	);

	add_theme_support(
		'woocommerce',
		array(
			'thumbnail_image_width' => 600,
			'single_image_width'    => 900,
			'product_grid'          => array(
				'default_rows'    => 4,
				'min_rows'        => 1,
				'max_rows'        => 10,
				'default_columns' => 5,
				'min_columns'     => 2,
				'max_columns'     => 6,
			),
		)
	);

	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	add_image_size( 'autodex-card', 600, 600, false );
	add_image_size( 'autodex-category', 640, 360, true );

	register_nav_menus(
		array(
			'primary' => __( 'Glavni meni', 'autodex' ),
			'footer'  => __( 'Footer meni', 'autodex' ),
		)
	);
}
add_action( 'after_setup_theme', 'autodex_setup' );

/**
 * Register widget areas.
 */
function autodex_widgets_init() {
	register_sidebar(
		array(
			'name'          => __( 'Sidebar', 'autodex' ),
			'id'            => 'sidebar-1',
			'description'   => __( 'Dodatni widgeti u sidebaru.', 'autodex' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);

	register_sidebar(
		array(
			'name'          => __( 'Footer widgeti', 'autodex' ),
			'id'            => 'footer-1',
			'description'   => __( 'Opcionalni widgeti iznad copyright linije.', 'autodex' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'autodex_widgets_init' );

/**
 * Create the two static pages used by the theme when they do not exist yet.
 */
function autodex_create_default_pages() {
	$pages = array(
		'o-nama'  => array(
			'title'    => 'O nama',
			'template' => 'page-o-nama.php',
		),
		'kontakt' => array(
			'title'    => 'Kontakt',
			'template' => 'page-kontakt.php',
		),
	);

	foreach ( $pages as $slug => $page ) {
		$existing = get_page_by_path( $slug );

		if ( $existing ) {
			$current_template = (string) get_page_template_slug( $existing->ID );

			if ( ! $current_template || false !== strpos( $current_template, 'elementor' ) ) {
				update_post_meta( $existing->ID, '_wp_page_template', $page['template'] );
			}
			continue;
		}

		$page_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $page['title'],
				'post_name'    => $slug,
				'post_content' => '',
			)
		);

		if ( $page_id && ! is_wp_error( $page_id ) ) {
			update_post_meta( $page_id, '_wp_page_template', $page['template'] );
		}
	}
}

/**
 * Create the curated top-level product categories used by the homepage.
 */
function autodex_create_default_categories() {
	if ( '1' === get_option( 'autodex_categories_setup_version' ) || ! taxonomy_exists( 'product_cat' ) ) {
		return;
	}

	$categories = array(
		'motor-i-dijelovi-motora' => 'Motor i dijelovi motora',
		'kvacilo-i-zamajac'       => 'Kvačilo i zamajac',
		'ovijes-i-upravljanje'    => 'Ovjes i upravljanje',
		'kocioni-sistem'          => 'Kočioni sistem',
		'elektronika-i-senzori'   => 'Elektronika i senzori',
		'izduvni-sistem'          => 'Izduvni sistem',
		'revenol-ulja'            => 'Revenol ulja',
		'manol-ulja'              => 'Manol ulja',
		'ostala-ulja-i-mazivi'    => 'Ostala ulja i mazivi',
		'karoserija'              => 'Karoserija',
		'rasvjeta'                => 'Rasvjeta',
		'retrovizori'             => 'Retrovizori',
		'stakla-retrovizora'      => 'Stakla retrovizora',
		'gume'                    => 'Gume',
		'metlice-brisaca'         => 'Metlice brisača',
		'patosnice'               => 'Patosnice',
		'autokozmetika'           => 'Autokozmetika',
		'veliki-servis'           => 'Veliki servis',
		'mali-servis'             => 'Mali servis',
	);

	foreach ( $categories as $slug => $name ) {
		if ( term_exists( $slug, 'product_cat' ) ) {
			continue;
		}

		wp_insert_term(
			$name,
			'product_cat',
			array(
				'slug' => $slug,
			)
		);
	}

	update_option( 'autodex_categories_setup_version', '1', false );
}

/**
 * Flush rewrite rules once after activation. WooCommerce endpoints are
 * localized in inc/woocommerce.php and need a fresh rewrite table.
 */
function autodex_after_switch_theme() {
	autodex_cache_flush();
	autodex_create_default_pages();
	autodex_create_default_categories();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'autodex_after_switch_theme' );

/**
 * Run the category setup when WooCommerce is activated after the theme.
 */
function autodex_setup_categories_on_woo_activation() {
	autodex_create_default_categories();
}
add_action( 'woocommerce_activated_plugin', 'autodex_setup_categories_on_woo_activation', 40 );

/**
 * Run the category setup on existing installations once.
 */
function autodex_maybe_create_default_categories() {
	autodex_create_default_categories();
}
add_action( 'init', 'autodex_maybe_create_default_categories', 21 );

/**
 * Flush permalinks when WooCommerce is activated after the theme.
 */
function autodex_woocommerce_activated() {
	flush_rewrite_rules();
	autodex_cache_flush();
}
add_action( 'woocommerce_activated_plugin', 'autodex_woocommerce_activated' );

/**
 * Front-end language attribute, matching the original Bosnian storefront.
 *
 * @param string $output Language attributes.
 * @return string
 */
function autodex_language_attributes( $output ) {
	return 'lang="bs-BA" dir="ltr"';
}
add_filter( 'language_attributes', 'autodex_language_attributes' );

/**
 * Remove the "Category:" / "Tag:" prefix noise from archives.
 *
 * @param string $title Archive title.
 * @return string
 */
function autodex_archive_title( $title ) {
	if ( is_category() || is_tag() || is_tax() ) {
		$title = single_term_title( '', false );
	} elseif ( is_search() ) {
		$title = sprintf( __( 'Rezultati pretrage za: %s', 'autodex' ), get_search_query() );
	}

	return $title;
}
add_filter( 'get_the_archive_title', 'autodex_archive_title' );

/**
 * Short excerpt.
 *
 * @param int $length Word count.
 * @return int
 */
function autodex_excerpt_length( $length ) {
	return 24;
}
add_filter( 'excerpt_length', 'autodex_excerpt_length', 999 );

/**
 * Excerpt more string.
 *
 * @return string
 */
function autodex_excerpt_more() {
	return '&hellip;';
}
add_filter( 'excerpt_more', 'autodex_excerpt_more' );

/**
 * Add a pingback header on singular pages.
 */
function autodex_pingback_header() {
	if ( is_singular() && pings_open() ) {
		printf( '<link rel="pingback" href="%s">', esc_url( get_bloginfo( 'pingback_url' ) ) );
	}
}
add_action( 'wp_head', 'autodex_pingback_header' );
