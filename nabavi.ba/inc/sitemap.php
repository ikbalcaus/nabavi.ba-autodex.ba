<?php
/**
 * XML sitemap at /sitemap.xml plus child sitemaps.
 *
 * @package Nabavi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Product URLs per child sitemap.
 *
 * @return int
 */
function nabavi_sitemap_product_chunk_size() {
	return 2000;
}

/**
 * Register sitemap query vars.
 *
 * @param array $vars Query vars.
 * @return array
 */
function nabavi_sitemap_query_vars( $vars ) {
	$vars[] = 'nabavi_sitemap';
	$vars[] = 'nabavi_sitemap_page';

	return $vars;
}
add_filter( 'query_vars', 'nabavi_sitemap_query_vars' );

/**
 * Register the sitemap rewrite rules.
 */
function nabavi_sitemap_rewrite_rules() {
	add_rewrite_rule( '^sitemap\.xml$', 'index.php?nabavi_sitemap=index', 'top' );
	add_rewrite_rule( '^sitemap-pages\.xml$', 'index.php?nabavi_sitemap=pages', 'top' );
	add_rewrite_rule( '^sitemap-posts\.xml$', 'index.php?nabavi_sitemap=posts', 'top' );
	add_rewrite_rule( '^sitemap-categories\.xml$', 'index.php?nabavi_sitemap=categories', 'top' );
	add_rewrite_rule( '^sitemap-brands\.xml$', 'index.php?nabavi_sitemap=brands', 'top' );
	add_rewrite_rule( '^sitemap-products-([0-9]+)\.xml$', 'index.php?nabavi_sitemap=products&nabavi_sitemap_page=$matches[1]', 'top' );
}
add_action( 'init', 'nabavi_sitemap_rewrite_rules', 5 );

/**
 * Flush rewrite rules once after the sitemap routes are added.
 */
function nabavi_sitemap_maybe_flush() {
	$version = '1';

	if ( get_option( 'nabavi_sitemap_rules_version' ) !== $version ) {
		flush_rewrite_rules( false );
		update_option( 'nabavi_sitemap_rules_version', $version, false );
	}
}
add_action( 'init', 'nabavi_sitemap_maybe_flush', 99 );

/**
 * Invalidate sitemap caches when content changes.
 */
function nabavi_sitemap_flush_cache() {
	if ( function_exists( 'nabavi_cache_flush' ) ) {
		nabavi_cache_flush();
	}
}
add_action( 'save_post', 'nabavi_sitemap_flush_cache' );
add_action( 'deleted_post', 'nabavi_sitemap_flush_cache' );
add_action( 'created_term', 'nabavi_sitemap_flush_cache' );
add_action( 'edited_term', 'nabavi_sitemap_flush_cache' );
add_action( 'delete_term', 'nabavi_sitemap_flush_cache' );

/**
 * Return the XML for a sitemap type, cached for an hour.
 *
 * @param string $type Sitemap type.
 * @param int    $page Product page.
 * @return string
 */
function nabavi_sitemap_get_xml( $type, $page = 1 ) {
	$key = 'sitemap_' . $type . ( 'products' === $type ? '_' . (int) $page : '' );

	$xml = nabavi_cache_get(
		$key,
		static function () use ( $type, $page ) {
			switch ( $type ) {
				case 'index':
					return nabavi_sitemap_index_xml();
				case 'pages':
					return nabavi_sitemap_pages_xml();
				case 'posts':
					return nabavi_sitemap_posts_xml();
				case 'categories':
					return nabavi_sitemap_categories_xml();
				case 'brands':
					return nabavi_sitemap_brands_xml();
				case 'products':
					return nabavi_sitemap_products_xml( $page );
				default:
					return '';
			}
		},
		HOUR_IN_SECONDS
	);

	return is_string( $xml ) ? $xml : '';
}

/**
 * Render the requested sitemap.
 */
function nabavi_render_sitemap() {
	$type = (string) get_query_var( 'nabavi_sitemap' );

	if ( '' === $type ) {
		return;
	}

	$page = max( 1, (int) get_query_var( 'nabavi_sitemap_page' ) );
	$xml  = nabavi_sitemap_get_xml( $type, $page );

	if ( '' === $xml ) {
		status_header( 404 );
		nocache_headers();
		exit;
	}

	header( 'Content-Type: application/xml; charset=UTF-8' );
	header( 'X-Robots-Tag: noindex, follow', true );
	header( 'Cache-Control: public, max-age=3600' );
	echo $xml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- XML is escaped during generation.
	exit;
}
add_action( 'template_redirect', 'nabavi_render_sitemap', 1 );

/**
 * XML declaration.
 *
 * @return string
 */
function nabavi_sitemap_declaration() {
	return '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
}

/**
 * A single URL entry.
 *
 * @param string $loc     URL.
 * @param string $lastmod Last modified date.
 * @return string
 */
function nabavi_sitemap_url_entry( $loc, $lastmod = '' ) {
	$loc = esc_url( $loc );

	if ( '' === $loc ) {
		return '';
	}

	$entry = "\t<url>\n\t\t<loc>" . $loc . "</loc>\n";

	if ( '' !== $lastmod ) {
		$entry .= "\t\t<lastmod>" . esc_html( $lastmod ) . "</lastmod>\n";
	}

	return $entry . "\t</url>\n";
}

/**
 * A single sitemap index entry.
 *
 * @param string $loc URL.
 * @return string
 */
function nabavi_sitemap_index_entry( $loc ) {
	$loc = esc_url( $loc );

	if ( '' === $loc ) {
		return '';
	}

	return "\t<sitemap>\n\t\t<loc>" . $loc . "</loc>\n\t</sitemap>\n";
}

/**
 * Page IDs that must not be listed.
 *
 * @return array
 */
function nabavi_sitemap_excluded_page_ids() {
	$exclude = array();

	if ( function_exists( 'wc_get_page_id' ) ) {
		foreach ( array( 'cart', 'checkout', 'myaccount' ) as $page ) {
			$page_id = (int) wc_get_page_id( $page );

			if ( $page_id > 0 ) {
				$exclude[] = $page_id;
			}
		}
	}

	$front_page = (int) get_option( 'page_on_front' );

	if ( $front_page > 0 ) {
		$exclude[] = $front_page;
	}

	return array_values( array_unique( $exclude ) );
}

/**
 * Sitemap index.
 *
 * @return string
 */
function nabavi_sitemap_index_xml() {
	$xml  = nabavi_sitemap_declaration();
	$xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
	$xml .= nabavi_sitemap_index_entry( home_url( '/sitemap-pages.xml' ) );

	$posts = wp_count_posts( 'post' );

	if ( $posts && (int) $posts->publish > 0 ) {
		$xml .= nabavi_sitemap_index_entry( home_url( '/sitemap-posts.xml' ) );
	}

	if ( taxonomy_exists( 'product_cat' ) ) {
		$count = wp_count_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
			)
		);

		if ( ! is_wp_error( $count ) && (int) $count > 0 ) {
			$xml .= nabavi_sitemap_index_entry( home_url( '/sitemap-categories.xml' ) );
		}
	}

	$brand_taxonomy = nabavi_get_brand_taxonomy();

	if ( $brand_taxonomy && taxonomy_exists( $brand_taxonomy ) && ( ! function_exists( 'is_taxonomy_viewable' ) || is_taxonomy_viewable( $brand_taxonomy ) ) ) {
		$count = wp_count_terms(
			array(
				'taxonomy'   => $brand_taxonomy,
				'hide_empty' => true,
			)
		);

		if ( ! is_wp_error( $count ) && (int) $count > 0 ) {
			$xml .= nabavi_sitemap_index_entry( home_url( '/sitemap-brands.xml' ) );
		}
	}

	$chunks = nabavi_sitemap_product_chunk_count();

	for ( $page = 1; $page <= $chunks; $page++ ) {
		$xml .= nabavi_sitemap_index_entry( home_url( '/sitemap-products-' . $page . '.xml' ) );
	}

	$xml .= '</sitemapindex>' . "\n";

	return $xml;
}

/**
 * Pages sitemap.
 *
 * @return string
 */
function nabavi_sitemap_pages_xml() {
	$exclude = nabavi_sitemap_excluded_page_ids();
	$pages   = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'modified',
			'order'          => 'DESC',
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	$xml  = nabavi_sitemap_declaration();
	$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
	$xml .= nabavi_sitemap_url_entry( home_url( '/' ) );

	foreach ( $pages as $page_id ) {
		$page_id = (int) $page_id;

		if ( in_array( $page_id, $exclude, true ) ) {
			continue;
		}

		$page = get_post( $page_id );

		if ( ! $page || '' !== $page->post_password ) {
			continue;
		}

		$xml .= nabavi_sitemap_url_entry(
			get_permalink( $page_id ),
			get_post_modified_time( 'c', true, $page_id )
		);
	}

	$xml .= '</urlset>' . "\n";

	return $xml;
}

/**
 * Blog posts sitemap.
 *
 * @return string
 */
function nabavi_sitemap_posts_xml() {
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'modified',
			'order'          => 'DESC',
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	if ( empty( $posts ) ) {
		return '';
	}

	$xml  = nabavi_sitemap_declaration();
	$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

	foreach ( $posts as $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post || '' !== $post->post_password ) {
			continue;
		}

		$xml .= nabavi_sitemap_url_entry(
			get_permalink( $post_id ),
			get_post_modified_time( 'c', true, $post_id )
		);
	}

	$xml .= '</urlset>' . "\n";

	return $xml;
}

/**
 * Product categories sitemap.
 *
 * @return string
 */
function nabavi_sitemap_categories_xml() {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return '';
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return '';
	}

	$xml  = nabavi_sitemap_declaration();
	$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

	foreach ( $terms as $term ) {
		$link = get_term_link( $term );

		if ( is_wp_error( $link ) ) {
			continue;
		}

		$xml .= nabavi_sitemap_url_entry( $link );
	}

	$xml .= '</urlset>' . "\n";

	return $xml;
}

/**
 * Product brands sitemap.
 *
 * @return string
 */
function nabavi_sitemap_brands_xml() {
	$taxonomy = nabavi_get_brand_taxonomy();

	if ( ! $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
		return '';
	}

	if ( function_exists( 'is_taxonomy_viewable' ) && ! is_taxonomy_viewable( $taxonomy ) ) {
		return '';
	}

	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return '';
	}

	$xml  = nabavi_sitemap_declaration();
	$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

	foreach ( $terms as $term ) {
		$link = get_term_link( $term );

		if ( is_wp_error( $link ) ) {
			continue;
		}

		$xml .= nabavi_sitemap_url_entry( $link );
	}

	$xml .= '</urlset>' . "\n";

	return $xml;
}

/**
 * Number of product sitemap chunks.
 *
 * @return int
 */
function nabavi_sitemap_product_chunk_count() {
	if ( ! post_type_exists( 'product' ) ) {
		return 0;
	}

	$counts = wp_count_posts( 'product' );
	$total  = $counts && isset( $counts->publish ) ? (int) $counts->publish : 0;

	if ( $total < 1 ) {
		return 0;
	}

	return (int) ceil( $total / nabavi_sitemap_product_chunk_size() );
}

/**
 * Products sitemap chunk.
 *
 * @param int $page Chunk number.
 * @return string
 */
function nabavi_sitemap_products_xml( $page ) {
	if ( ! post_type_exists( 'product' ) ) {
		return '';
	}

	$page  = max( 1, (int) $page );
	$total = nabavi_sitemap_product_chunk_count();

	if ( $total < 1 || $page > $total ) {
		return '';
	}

	$products = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => nabavi_sitemap_product_chunk_size(),
			'paged'          => $page,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	if ( empty( $products ) ) {
		return '';
	}

	$xml  = nabavi_sitemap_declaration();
	$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

	foreach ( $products as $product_id ) {
		$product = get_post( $product_id );

		if ( ! $product || '' !== $product->post_password ) {
			continue;
		}

		$xml .= nabavi_sitemap_url_entry(
			get_permalink( $product_id ),
			get_post_modified_time( 'c', true, $product_id )
		);
	}

	$xml .= '</urlset>' . "\n";

	return $xml;
}

/**
 * Advertise the sitemap in robots.txt.
 *
 * @param string $output Robots output.
 * @param bool   $public Whether the site is public.
 * @return string
 */
function nabavi_sitemap_robots( $output, $public ) {
	if ( ! $public ) {
		return $output;
	}

	if ( function_exists( 'nabavi_seo_plugin_active' ) && nabavi_seo_plugin_active() ) {
		return $output;
	}

	return $output . "\nSitemap: " . home_url( '/sitemap.xml' ) . "\n";
}
add_filter( 'robots_txt', 'nabavi_sitemap_robots', 20, 2 );
