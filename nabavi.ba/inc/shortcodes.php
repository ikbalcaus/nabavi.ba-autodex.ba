<?php
/**
 * Shortcodes used by the original content and by page builders.
 *
 * @package Nabavi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render a product grid from a list of product IDs.
 *
 * @param array  $ids         Product IDs.
 * @param int    $columns     Columns.
 * @param string $extra_class Extra wrapper class.
 * @return string
 */
function nabavi_render_product_grid( $ids, $columns = 5, $extra_class = '' ) {
	if ( ! nabavi_is_woocommerce_active() || empty( $ids ) ) {
		return '';
	}

	$ids = array_values( array_filter( array_map( 'intval', (array) $ids ) ) );

	if ( empty( $ids ) ) {
		return '';
	}

	$columns = max( 1, min( 6, (int) $columns ) );

	$query = new WP_Query(
		array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'post__in'               => $ids,
			'orderby'                => 'post__in',
			'posts_per_page'         => count( $ids ),
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	if ( ! $query->have_posts() ) {
		return '';
	}

	ob_start();

	echo '<div class="woocommerce columns-' . esc_attr( $columns ) . ' ' . esc_attr( $extra_class ) . '">';

	wc_set_loop_prop( 'columns', $columns );
	wc_set_loop_prop( 'total', $query->post_count );
	wc_set_loop_prop( 'per_page', $query->post_count );
	wc_set_loop_prop( 'current_page', 1 );
	wc_set_loop_prop( 'is_paginated', false );

	woocommerce_product_loop_start();

	while ( $query->have_posts() ) {
		$query->the_post();
		wc_get_template_part( 'content', 'product' );
	}

	woocommerce_product_loop_end();

	echo '</div>';

	wp_reset_postdata();

	if ( function_exists( 'wc_reset_loop' ) ) {
		wc_reset_loop();
	}

	return ob_get_clean();
}

/**
 * [custom_breadcrumb]
 *
 * @return string
 */
function nabavi_shortcode_breadcrumb() {
	return nabavi_breadcrumbs(
		array(
			'echo'  => false,
			'class' => 'nabavi-breadcrumbs--shop',
		)
	);
}
add_shortcode( 'custom_breadcrumb', 'nabavi_shortcode_breadcrumb' );

/**
 * [korpa_button]
 *
 * @return string
 */
function nabavi_shortcode_cart_button() {
	$count = nabavi_get_cart_count();
	$badge = $count > 0 ? '<span class="korpa-badge">' . esc_html( $count ) . '</span>' : '';

	return '<a href="' . esc_url( nabavi_get_cart_url() ) . '" class="korpa-button">Korpa' . $badge . '</a>';
}
add_shortcode( 'korpa_button', 'nabavi_shortcode_cart_button' );

/**
 * [korpa_badge]
 *
 * @return string
 */
function nabavi_shortcode_cart_badge() {
	$count = nabavi_get_cart_count();

	if ( $count < 1 ) {
		return '';
	}

	return '<a href="' . esc_url( nabavi_get_cart_url() ) . '" class="korpa-badge">' . esc_html( $count ) . '</a>';
}
add_shortcode( 'korpa_badge', 'nabavi_shortcode_cart_badge' );

/**
 * [najpopularniji_proizvodi] / [nabavi_popular_products]
 *
 * @param array $atts Attributes.
 * @return string
 */
function nabavi_shortcode_popular_products( $atts = array() ) {
	$atts = shortcode_atts(
		array(
			'limit'   => 50,
			'columns' => 5,
		),
		$atts,
		'najpopularniji_proizvodi'
	);

	$ids = nabavi_get_popular_product_ids( (int) $atts['limit'] );

	return nabavi_render_product_grid( $ids, (int) $atts['columns'], 'nabavi-popular-grid' );
}
add_shortcode( 'najpopularniji_proizvodi', 'nabavi_shortcode_popular_products' );
add_shortcode( 'nabavi_popular_products', 'nabavi_shortcode_popular_products' );

/**
 * [nabavi_products] — flexible product grid for content pages.
 *
 * @param array $atts Attributes.
 * @return string
 */
function nabavi_shortcode_products( $atts = array() ) {
	$atts = shortcode_atts(
		array(
			'limit'    => 10,
			'columns'  => 5,
			'category' => '',
			'orderby'  => 'date',
			'order'    => 'DESC',
			'ids'      => '',
		),
		$atts,
		'nabavi_products'
	);

	if ( ! nabavi_is_woocommerce_active() ) {
		return '';
	}

	if ( ! empty( $atts['ids'] ) ) {
		$ids = array_map( 'intval', explode( ',', $atts['ids'] ) );
	} else {
		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => max( 1, (int) $atts['limit'] ),
			'orderby'        => sanitize_key( $atts['orderby'] ),
			'order'          => 'ASC' === strtoupper( $atts['order'] ) ? 'ASC' : 'DESC',
			'fields'         => 'ids',
			'no_found_rows'  => true,
		);

		if ( ! empty( $atts['category'] ) ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					'terms'    => array_map( 'sanitize_title', explode( ',', $atts['category'] ) ),
				),
			);
		}

		$ids = get_posts( $args );
	}

	return nabavi_render_product_grid( $ids, (int) $atts['columns'], 'nabavi-products-shortcode' );
}
add_shortcode( 'nabavi_products', 'nabavi_shortcode_products' );

/**
 * [nabavi_categories] — the curated homepage category grid.
 *
 * @return string
 */
function nabavi_shortcode_categories() {
	$categories = nabavi_get_curated_categories();

	if ( empty( $categories ) ) {
		return '';
	}

	ob_start();
	?>
	<div class="nabavi-category-grid">
		<?php foreach ( $categories as $category ) : ?>
			<a class="nabavi-category-card" href="<?php echo esc_url( $category['url'] ); ?>"<?php echo ! empty( $category['url'] ) && false !== strpos( $category['url'], 'autodex.ba' ) ? ' target="_blank" rel="noopener"' : ''; ?>>
				<span class="nabavi-category-card__media">
					<img src="<?php echo esc_url( nabavi_image_src( $category['image'], 640 ) ); ?>" srcset="<?php echo esc_attr( nabavi_image_srcset( $category['image'], 1600, array( 480, 640, 960 ) ) ); ?>" sizes="(max-width: 767px) 50vw, (max-width: 1024px) 33vw, 25vw" alt="<?php echo esc_attr( $category['label'] ); ?>" loading="lazy" width="640" height="360">
				</span>
				<span class="nabavi-category-card__title"><?php echo esc_html( $category['label'] ); ?></span>
			</a>
		<?php endforeach; ?>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'nabavi_categories', 'nabavi_shortcode_categories' );

/**
 * [nabavi_contact_form]
 *
 * @return string
 */
function nabavi_shortcode_contact_form() {
	return function_exists( 'nabavi_contact_form' ) ? nabavi_contact_form() : '';
}
add_shortcode( 'nabavi_contact_form', 'nabavi_shortcode_contact_form' );
