<?php
/**
 * Empty cart.
 *
 * WooCommerce prints the "cart is empty" message before this template, so we
 * only add the return-to-shop action here.
 *
 * @package Nabavi
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_cart_is_empty' );
?>
<p class="return-to-shop">
	<a class="button wc-backward" href="<?php echo esc_url( nabavi_get_shop_url() ); ?>"><?php esc_html_e( 'Povratak u trgovinu', 'nabavi' ); ?></a>
</p>
