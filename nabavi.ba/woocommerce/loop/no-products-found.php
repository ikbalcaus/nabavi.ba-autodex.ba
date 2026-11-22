<?php
/**
 * No products found.
 *
 * @package Nabavi
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="nabavi-no-products">
	<p class="woocommerce-info"><?php esc_html_e( 'Žao nam je, trenutno nemamo proizvoda.', 'nabavi' ); ?></p>
	<a class="button" href="<?php echo esc_url( nabavi_get_shop_url() ); ?>"><?php esc_html_e( 'Povratak u trgovinu', 'nabavi' ); ?></a>
</div>
