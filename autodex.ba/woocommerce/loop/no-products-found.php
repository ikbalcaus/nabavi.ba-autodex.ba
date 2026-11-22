<?php
/**
 * No products found.
 *
 * @package Autodex
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="autodex-no-products">
	<p class="woocommerce-info"><?php esc_html_e( 'Žao nam je, trenutno nemamo proizvoda.', 'autodex' ); ?></p>
	<a class="button" href="<?php echo esc_url( autodex_get_shop_url() ); ?>"><?php esc_html_e( 'Povratak u trgovinu', 'autodex' ); ?></a>
</div>
