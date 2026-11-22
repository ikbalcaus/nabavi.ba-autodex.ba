<?php
/**
 * Product card used by the shop, search, related products and homepage.
 *
 * @package Autodex
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! isset( $product ) || ! $product instanceof WC_Product ) {
	$product = wc_get_product( get_the_ID() );
}

if ( ! $product instanceof WC_Product || ! $product->is_visible() ) {
	return;
}

$product_id = $product->get_id();
$permalink  = get_permalink( $product_id );
$category   = autodex_get_primary_product_category( $product_id );
$is_out         = ! $product->is_in_stock();
$is_sale        = $product->is_on_sale();
$button_url     = $permalink;
$button_classes = 'button autodex-product-card__button';

if ( $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) {
	$button_url      = $product->add_to_cart_url();
	$button_classes .= ' product_type_simple add_to_cart_button ajax_add_to_cart';
} else {
	$button_classes .= ' product_type_' . $product->get_type();
}
?>
<li <?php wc_product_class( 'autodex-product-card', $product ); ?>>
	<figure class="autodex-product-card__figure">
		<?php if ( $is_sale ) : ?>
			<span class="autodex-badge autodex-badge--sale"><?php esc_html_e( 'Akcija', 'autodex' ); ?></span>
		<?php endif; ?>

		<?php if ( $is_out ) : ?>
			<span class="autodex-badge autodex-badge--out"><?php echo esc_html( autodex_out_of_stock_label() ); ?></span>
		<?php endif; ?>

		<a class="autodex-product-card__media" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( $product->get_name() ); ?>">
			<?php
			$image = $product->get_image(
				'woocommerce_thumbnail',
				array(
					'class'    => 'autodex-product-card__image',
					'alt'      => $product->get_name(),
					'loading'  => 'lazy',
					'decoding' => 'async',
				)
			);

			echo $image ? $image : autodex_product_placeholder(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</a>
	</figure>

	<h2 class="woocommerce-loop-product__title">
		<a class="woocommerce-LoopProduct-link" href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
	</h2>

	<span class="price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>

	<?php if ( $category instanceof WP_Term ) : ?>
		<div class="autodex-product-card__category">
			<a href="<?php echo esc_url( get_term_link( $category ) ); ?>"><?php echo esc_html( $category->name ); ?></a>
		</div>
	<?php endif; ?>

	<div class="autodex-product-card__actions">
		<a
			href="<?php echo esc_url( $button_url ); ?>"
			data-quantity="1"
			class="<?php echo esc_attr( $button_classes ); ?>"
			data-product_id="<?php echo esc_attr( $product_id ); ?>"
			data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>"
			aria-label="<?php echo esc_attr( sprintf( 'Dodaj u korpu: %s', $product->get_name() ) ); ?>"
			rel="nofollow"
		><?php esc_html_e( 'Dodaj u korpu', 'autodex' ); ?></a>
	</div>
</li>
