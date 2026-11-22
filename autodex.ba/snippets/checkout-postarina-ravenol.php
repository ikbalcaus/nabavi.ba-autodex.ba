<?php
/**
 * Checkout: shipping weight table for carts that contain a Ravenol product.
 *
 * @package Autodex
 */

defined( 'ABSPATH' ) || exit;

/**
 * Does the current cart contain a Ravenol product?
 *
 * @return bool
 */
function autodex_cart_has_ravenol() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return false;
	}

	foreach ( WC()->cart->get_cart() as $cart_item ) {
		if ( empty( $cart_item['data'] ) || ! is_object( $cart_item['data'] ) ) {
			continue;
		}

		$product      = $cart_item['data'];
		$product_name = $product->get_name();

		// Variations often do not repeat the parent brand in their own name.
		if ( $product->is_type( 'variation' ) ) {
			$parent = wc_get_product( $product->get_parent_id() );

			if ( $parent ) {
				$product_name .= ' ' . $parent->get_name();
			}
		}

		if ( false !== stripos( $product_name, 'ravenol' ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Print the shipping table row above the order total.
 */
function prikazi_tekst_postarine_za_ravenol() {
	if ( ! autodex_cart_has_ravenol() ) {
		return;
	}
	?>
	<tr class="postarina-info-red">
		<td colspan="2">
			<div class="postarina-tekst">
				<strong class="postarina-naslov">Poštarina (Stope mase):</strong>
				• do 10 kg — 11,00 KM<br>
				• 10 - 20 kg — 14,00 KM<br>
				• 20 - 30 kg — 18,00 KM<br>
				• 30 - 40 kg — 30,00 KM<br>
				• 40 - 50 kg — 35,00 KM
			</div>
		</td>
	</tr>
	<?php
}
add_action( 'woocommerce_review_order_before_order_total', 'prikazi_tekst_postarine_za_ravenol', 9999 );

/**
 * Add the "+ Poštarina" label to the order total and keep it after AJAX updates.
 */
function autodex_ravenol_shipping_assets() {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || ! autodex_cart_has_ravenol() ) {
		return;
	}
	?>
	<style>
		.postarina-info-red td { padding: 15px 0; border-top: 1px dashed #e6e6e6; }
		.postarina-tekst { font-size: 13px; color: #444; font-weight: 500; line-height: 1.6; }
		.postarina-naslov { display: block; margin-bottom: 5px; color: #000; }
	</style>
	<script>
		( function ( $ ) {
			if ( ! $ ) {
				return;
			}

			function dodajPostarinuNaCijenu() {
				var $cijenaCilj = $( '.shop_table tfoot tr.order-total td strong .woocommerce-Price-amount' );

				if ( $cijenaCilj.length && ! $( '.postarina-dodatak' ).length && $( '.postarina-info-red' ).length ) {
					$cijenaCilj.after( '<span class="postarina-dodatak" style="font-size:14px;font-weight:700;color:#444;margin-left:5px;"> + Poštarina</span>' );
				}
			}

			$( function () {
				dodajPostarinuNaCijenu();
				$( document.body ).on( 'updated_checkout', dodajPostarinuNaCijenu );
			} );
		}( window.jQuery ) );
	</script>
	<?php
}
add_action( 'wp_footer', 'autodex_ravenol_shipping_assets', 99 );
