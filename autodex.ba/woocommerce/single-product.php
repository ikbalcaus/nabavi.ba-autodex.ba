<?php
/**
 * Single product page.
 *
 * @package Autodex
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
	<div class="autodex-product-page">
		<div class="autodex-container autodex-container--wide">
			<?php autodex_breadcrumbs( array( 'class' => 'autodex-breadcrumbs--product' ) ); ?>

			<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'autodex-single-product', $product ); ?>>
				<div class="autodex-single-product__main">
					<div class="autodex-single-product__gallery">
						<?php autodex_product_gallery( $product ); ?>
					</div>

					<div class="autodex-single-product__summary summary entry-summary">
						<?php
						the_title( '<h1 class="product_title entry-title">', '</h1>' );
						woocommerce_template_single_price();
						?>

						<div class="autodex-single-product__divider" role="presentation"></div>

						<?php woocommerce_template_single_add_to_cart(); ?>

						<div class="autodex-single-product__divider" role="presentation"></div>

						<?php autodex_product_meta_output(); ?>
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
