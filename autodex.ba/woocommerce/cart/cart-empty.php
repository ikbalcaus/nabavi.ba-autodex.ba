<?php
/**
 * Empty cart.
 *
 * WooCommerce prints the "cart is empty" message before this template, so we
 * only add the return-to-shop action here.
 *
 * @package Autodex
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_cart_is_empty' );
?>
<p class="return-to-shop">
	<a class="button wc-backward" href="<?php echo esc_url( autodex_get_shop_url() ); ?>"><?php esc_html_e( 'Povratak u trgovinu', 'autodex' ); ?></a>
</p>
