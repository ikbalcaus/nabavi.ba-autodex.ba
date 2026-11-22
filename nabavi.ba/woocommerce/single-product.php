<?php
/**
 * Single product page.
 *
 * @package Nabavi
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	global $product;

	if ( ! $product instanceof WC_Product ) {
		$product = wc_get_product( get_the_ID() );
	}

	do_action( 'woocommerce_before_single_product' );
	?>
	<div class="nabavi-product-page">
		<div class="nabavi-container nabavi-container--wide">
			<?php nabavi_breadcrumbs( array( 'class' => 'nabavi-breadcrumbs--product' ) ); ?>

			<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'nabavi-single-product', $product ); ?>>
				<div class="nabavi-single-product__main">
					<div class="nabavi-single-product__gallery">
						<?php nabavi_product_gallery( $product ); ?>
					</div>

					<div class="nabavi-single-product__summary summary entry-summary">
						<?php
						the_title( '<h1 class="product_title entry-title">', '</h1>' );
						woocommerce_template_single_price();
						woocommerce_template_single_excerpt();
						?>

						<div class="nabavi-single-product__divider" role="presentation"></div>

						<?php woocommerce_template_single_add_to_cart(); ?>

						<div class="nabavi-single-product__divider" role="presentation"></div>

						<?php nabavi_product_meta_output(); ?>
					</div>
				</div>

				<?php woocommerce_output_product_data_tabs(); ?>

				<?php woocommerce_upsell_display(); ?>

				<?php woocommerce_output_related_products(); ?>
			</div>
		</div>
	</div>
	<?php
	// Keep WooCommerce product schema even though the summary markup is custom.
	if ( function_exists( 'WC' ) && WC()->structured_data ) {
		WC()->structured_data->generate_product_data( $product );
	}

	do_action( 'woocommerce_after_single_product' );
endwhile;

get_footer();
