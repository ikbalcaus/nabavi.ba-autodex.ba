<?php
/**
 * Hide SWAG brand products from the shop, category, tag and search archives.
 *
 * @package Autodex
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add a NOT IN tax query for the SWAG brand to the main archive query.
 *
 * @param WP_Query $query Query.
 */
function sakrij_swag_brend( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( ! function_exists( 'is_shop' ) ) {
		return;
	}

	if ( ! ( is_shop() || is_product_category() || is_product_tag() || is_search() ) ) {
		return;
	}

	$taxonomy = 'product_brand';

	if ( function_exists( 'bpa_get_brand_taxonomy' ) ) {
		$taxonomy = bpa_get_brand_taxonomy();
	} elseif ( function_exists( 'autodex_get_brand_taxonomy' ) ) {
		$taxonomy = autodex_get_brand_taxonomy();
	}

	if ( ! $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
		return;
	}

	$term = get_term_by( 'name', 'SWAG', $taxonomy );

	if ( ! $term || is_wp_error( $term ) ) {
		return;
	}

	$tax_query   = (array) $query->get( 'tax_query' );
	$tax_query[] = array(
		'taxonomy' => $taxonomy,
		'field'    => 'slug',
		'terms'    => array( $term->slug ),
		'operator' => 'NOT IN',
	);

	$query->set( 'tax_query', $tax_query );
}
add_action( 'pre_get_posts', 'sakrij_swag_brend' );
