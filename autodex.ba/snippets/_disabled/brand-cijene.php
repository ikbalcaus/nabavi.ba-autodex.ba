<?php
/**
 * ===== BRAND CIJENE (WooCommerce) =====
 *
 * Tools -> Brand cijene:
 *  - for each brand enter a percentage and (optionally) check "+ PDV 17%".
 *    Formula: price * (1 + percent/100) * (1 + 17/100 if VAT is checked)
 *    Example: 10000 + 50% = 15000, + 17% VAT = 17550.
 *  - EMPTY field = that brand's prices are not changed at all (no percent, no VAT).
 *  - brands that have not been saved yet are shown with their special percentage
 *    (from bpa_brand_default_overrides(), e.g. ABAKUS 75%, TOPRAN 45%), all others
 *    with 35% and VAT checked (suggestion only; prices change after Save).
 *  - at the bottom: "Minimalna zarada u KM" (default 20 KM, without VAT).
 *    Products below that profit can be moved to trash (preview first, then confirm).
 *
 * Base prices in the database are NOT changed.
 *
 * @package Autodex
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default percentage (shown in the form and used for the minimum profit default).
 *
 * @return float
 */
function bpa_default_percent() {
	return (float) apply_filters( 'bpa_default_percent', 35 );
}

/**
 * Brands with a special default percentage instead of 35.
 * Key = brand name in uppercase. Value = percentage, or null = empty field (prices untouched).
 * Can be extended with: add_filter( 'bpa_brand_default_overrides', ... );
 *
 * @return array
 */
function bpa_brand_default_overrides() {
	return apply_filters(
		'bpa_brand_default_overrides',
		array(
			// 75%
			'ABAKUS'           => 75,
			'ABAKUS SP Z.O.O.' => 75,
			'AS-PL'            => 75,
			'AUTOMEGA'         => 75,
			'AUTOVENTIL'       => 75,
			'BGA'              => 75,
			'DACO'             => 75,
			'DACO GERMANY'     => 75,
			'GAUSS'            => 75,
			'ICER'             => 75,
			'MANNOL'           => 75,
			'MANNOL HEMIJA'    => 75,
			'MONEA'            => 75,
			'NEWCRE'           => 75,
			'RYMEC'            => 75,
			'SCT GERMANY'      => 75,
			'SCTGERMANY'       => 75,
			'STC'              => 75,
			// 45%
			'TOPRAN'           => 45,
			// "temporarily hidden" is not a percentage -> empty field (prices untouched)
			'SWAG'             => null,
		)
	);
}

/**
 * Return array( 'percent' => float|'', 'vat' => bool ) for a not-yet-saved brand.
 *
 * @param WP_Term $term Brand term.
 * @return array
 */
function bpa_default_for_term( $term ) {
	$overrides = bpa_brand_default_overrides();
	$key       = strtoupper( trim( html_entity_decode( $term->name ) ) );

	if ( array_key_exists( $key, $overrides ) ) {
		if ( null === $overrides[ $key ] ) {
			return array(
				'percent' => '',
				'vat'     => false,
			);
		}

		return array(
			'percent' => (float) $overrides[ $key ],
			'vat'     => true,
		);
	}

	return array(
		'percent' => bpa_default_percent(),
		'vat'     => true,
	);
}

/**
 * Brand taxonomy. Auto-detection:
 * - product_brand      (WooCommerce 9.6+ built-in Brands)
 * - pwb-brand          (Perfect Brands for WooCommerce)
 * - yith_product_brand (YITH Brands)
 * - pa_brand           (attribute "brand")
 * If you use something else: add_filter( 'bpa_brand_taxonomy', fn() => 'my_slug' );
 *
 * @return string
 */
function bpa_get_brand_taxonomy() {
	$candidates = array( 'product_brand', 'pwb-brand', 'yith_product_brand', 'pa_brand', 'pa_brend', 'brand', 'product_brands' );
	$found      = '';

	foreach ( $candidates as $tax ) {
		if ( taxonomy_exists( $tax ) ) {
			$found = $tax;
			break;
		}
	}

	return apply_filters( 'bpa_brand_taxonomy', $found );
}

/**
 * Return array: term_id => array( 'percent' => float, 'vat' => bool, 'empty' => bool ).
 * Compatible with the old format (percentage only).
 *
 * @return array
 */
function bpa_get_percentages() {
	$data = get_option( 'bpa_brand_percentages', array() );

	if ( ! is_array( $data ) ) {
		return array();
	}

	$out = array();

	foreach ( $data as $term_id => $row ) {
		if ( is_array( $row ) ) {
			$out[ $term_id ] = array(
				'percent' => isset( $row['percent'] ) ? (float) $row['percent'] : 0,
				'vat'     => ! empty( $row['vat'] ),
				'empty'   => ! empty( $row['empty'] ),
			);
		} else {
			$out[ $term_id ] = array(
				'percent' => (float) $row,
				'vat'     => false,
				'empty'   => false,
			);
		}
	}

	return $out;
}

/**
 * VAT rate (17%). Can be changed with: add_filter( 'bpa_vat_rate', fn() => 20 );
 *
 * @return float
 */
function bpa_get_vat_rate() {
	return (float) apply_filters( 'bpa_vat_rate', 17 );
}

/* -------------------------------------------------------------------------
 * Admin page
 * ---------------------------------------------------------------------- */

add_action(
	'admin_menu',
	function () {
		add_management_page(
			'Brand cijene',
			'Brand cijene',
			'manage_woocommerce',
			'brand-cijene',
			'bpa_render_admin_page'
		);
	}
);

/**
 * Render the Brand cijene admin page.
 */
function bpa_render_admin_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$taxonomy = bpa_get_brand_taxonomy();

	if ( isset( $_POST['bpa_save'] ) && check_admin_referer( 'bpa_save_action', 'bpa_nonce' ) ) {
		$new  = array();
		$vats = ( ! empty( $_POST['bpa_vat'] ) && is_array( $_POST['bpa_vat'] ) ) ? $_POST['bpa_vat'] : array();
		$pcts = ( ! empty( $_POST['bpa_percent'] ) && is_array( $_POST['bpa_percent'] ) ) ? $_POST['bpa_percent'] : array();

		foreach ( $pcts as $term_id => $val ) {
			$val = str_replace( ',', '.', trim( wp_unslash( $val ) ) );

			if ( '' === $val || ! is_numeric( $val ) ) {
				// Empty field = brand is not changed at all (no percent, no VAT), regardless of the checkbox.
				$new[ absint( $term_id ) ] = array(
					'percent' => 0,
					'vat'     => false,
					'empty'   => true,
				);
			} else {
				$new[ absint( $term_id ) ] = array(
					'percent' => max( -100, (float) $val ),
					'vat'     => isset( $vats[ $term_id ] ),
					'empty'   => false,
				);
			}
		}

		update_option( 'bpa_brand_percentages', $new );

		// Clear cached product prices.
		if ( class_exists( 'WC_Cache_Helper' ) ) {
			WC_Cache_Helper::get_transient_version( 'product', true );
		}
		if ( function_exists( 'wc_delete_product_transients' ) ) {
			wc_delete_product_transients( 0 );
		}

		echo '<div class="notice notice-success is-dismissible"><p>Postavke su sačuvane.</p></div>';
	}

	$percentages = bpa_get_percentages();
	$vat_rate    = bpa_get_vat_rate();
	$default_pct = bpa_default_percent();
	?>
	<div class="wrap">
		<h1>Brand cijene <small style="font-size:12px;color:#888;">(v7)</small></h1>
		<p>Upiši procenat uvećanja za brend (npr. <code>50</code> = +50%, <code>-10</code> = popust 10%).
		Ako je polje <strong>prazno</strong>, cijene tog brenda se ne mijenjaju uopšte (ni PDV). Za samo PDV upiši <code>0</code>.
		Ako je označeno <strong>+ PDV</strong>, na ukupnu cijenu (cijena + procenat) dodaje se još <?php echo esc_html( $vat_rate ); ?>%.
		Primjer: (10000 + 50%) + <?php echo esc_html( $vat_rate ); ?>% = <?php echo esc_html( round( 15000 * ( 1 + $vat_rate / 100 ), 2 ) ); ?></p>
		<p><em>Brendovi koji još nisu sačuvani prikazani su sa svojim posebnim procentom (ako ga imaju na listi), a ostali sa <?php echo esc_html( $default_pct ); ?>% i označenim PDV-om. Cijene se mijenjaju tek nakon klika na Sačuvaj.</em></p>

		<?php
		if ( $taxonomy ) {
			bpa_render_cleanup_section();
		}
		?>

		<?php if ( ! $taxonomy ) : ?>
			<div class="notice notice-error"><p>Nije pronađena taksonomija brendova. Aktiviraj WooCommerce Brands ili postavi slug preko filtera <code>bpa_brand_taxonomy</code>.</p></div>
		<?php else :
			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'orderby'    => 'name',
				)
			);

			if ( is_wp_error( $terms ) || empty( $terms ) ) : ?>
				<p>Nema brendova.</p>
			<?php else : ?>
				<form method="post">
					<?php wp_nonce_field( 'bpa_save_action', 'bpa_nonce' ); ?>
					<table class="widefat striped" style="max-width:700px;">
						<thead>
							<tr>
								<th>Brend</th>
								<th style="width:80px;">Proizvoda</th>
								<th style="width:150px;">Procenat (%)</th>
								<th style="width:120px;">+ PDV <?php echo esc_html( $vat_rate ); ?>%</th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ( $terms as $term ) :
							if ( isset( $percentages[ $term->term_id ] ) ) {
								$row = $percentages[ $term->term_id ];
								$val = $row['empty'] ? '' : $row['percent'];
								$vat = $row['vat'];
							} else {
								// Not saved yet: special percentage if listed, otherwise default 35% + VAT.
								$def = bpa_default_for_term( $term );
								$val = $def['percent'];
								$vat = $def['vat'];
							}
							?>
							<tr>
								<td><strong><?php echo esc_html( $term->name ); ?></strong></td>
								<td><?php echo (int) $term->count; ?></td>
								<td>
									<input type="number" step="0.01" min="-100"
										name="bpa_percent[<?php echo (int) $term->term_id; ?>]"
										value="<?php echo esc_attr( $val ); ?>" placeholder="prazno" style="width:100px;"> %
								</td>
								<td>
									<label>
										<input type="checkbox"
											name="bpa_vat[<?php echo (int) $term->term_id; ?>]" value="1"
											<?php checked( $vat ); ?>> PDV
									</label>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
					<p><button type="submit" name="bpa_save" class="button button-primary">Sačuvaj</button></p>
				</form>
			<?php endif;
		endif;
		?>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * Delete products with too-low profit
 * ---------------------------------------------------------------------- */

/**
 * Default minimum profit per product in KM.
 *
 * @return float
 */
function bpa_default_min_profit() {
	return (float) apply_filters( 'bpa_default_min_profit', 20 );
}

/**
 * Find products whose profit is below the minimum profit in KM.
 * Profit = base price * brand percentage / 100, without VAT.
 * Brands with an empty field are skipped.
 * Variable products: profit is checked for each variation separately.
 *
 * @param float $min_km Minimum profit.
 * @return array
 */
function bpa_find_low_profit_items( $min_km ) {
	$map      = bpa_get_percentages();
	$taxonomy = bpa_get_brand_taxonomy();
	$items    = array();
	$seen     = array();

	if ( ! $taxonomy || ! $map ) {
		return $items;
	}

	foreach ( $map as $term_id => $cfg ) {
		if ( $cfg['empty'] ) {
			continue;
		}

		$term = get_term( (int) $term_id, $taxonomy );

		if ( ! $term || is_wp_error( $term ) ) {
			continue;
		}

		$ids = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'tax_query'      => array(
					array(
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => (int) $term_id,
					),
				),
			)
		);

		foreach ( $ids as $pid ) {
			$product = wc_get_product( $pid );

			if ( ! $product ) {
				continue;
			}

			$targets = $product->is_type( 'variable' )
				? array_filter( array_map( 'wc_get_product', $product->get_children() ) )
				: array( $product );

			foreach ( $targets as $t ) {
				$item_id = $t->get_id();

				if ( isset( $seen[ $item_id ] ) ) {
					continue;
				}

				$price = $t->get_price( 'edit' ); // Original database price, without markup.

				// Trend Auto proizvodi već imaju uračunat markup u sačuvanoj cijeni,
				// pa se za obračun zarade koristi sačuvana osnovna cijena.
				$trend_base = get_post_meta( $item_id, '_trend_base_price', true );

				if ( '' !== $trend_base && is_numeric( $trend_base ) ) {
					$price = (float) $trend_base;
				}

				if ( '' === $price || ! is_numeric( $price ) ) {
					continue;
				}

				$profit = (float) $price * $cfg['percent'] / 100;

				if ( $profit < $min_km ) {
					$seen[ $item_id ] = true;

					$items[] = array(
						'id'        => $item_id,
						'parent_id' => $t->is_type( 'variation' ) ? $pid : 0,
						'name'      => wp_strip_all_tags( $t->get_formatted_name() ),
						'brand'     => $term->name,
						'percent'   => $cfg['percent'],
						'price'     => (float) $price,
						'profit'    => $profit,
						'reason'    => 'zarada < ' . $min_km . ' KM',
					);
				}
			}
		}
	}

	return $items;
}

/**
 * Wrap the cleanup section.
 */
function bpa_render_cleanup_section() {
	echo '<div style="background:#fff;border:1px solid #c3c4c7;border-left:4px solid #d63638;padding:5px 20px 15px;margin:20px 0;max-width:1000px;">';
	bpa_render_cleanup_inner();
	echo '</div>';
}

/**
 * Render the cleanup UI and handle preview/delete actions.
 */
function bpa_render_cleanup_inner() {
	$min_km = bpa_default_min_profit();

	if ( isset( $_POST['bpa_min_km'] ) ) {
		$min_km = max( 0, (float) str_replace( ',', '.', wp_unslash( $_POST['bpa_min_km'] ) ) );
	}

	echo '<h2 style="margin-top:10px;">Brisanje proizvoda sa premalom zaradom</h2>';
	echo '<p>Proizvod ide u smeće (Trash, može se vratiti) ako je <strong>zarada u KM (bez PDV-a) manja od minimalne zarade</strong>. Zarada = osnovna cijena × procenat brenda / 100. Brendovi sa praznim poljem se ne provjeravaju. Prvo klikni Pregled.</p>';

	// Delete (confirmed after preview).
	if ( isset( $_POST['bpa_delete'] ) && check_admin_referer( 'bpa_cleanup_action', 'bpa_cleanup_nonce' ) ) {
		$items   = bpa_find_low_profit_items( $min_km );
		$parents = array();

		foreach ( $items as $it ) {
			wp_trash_post( $it['id'] );

			if ( $it['parent_id'] ) {
				$parents[ $it['parent_id'] ] = true;
			}
		}

		// If all variations are deleted, trash the parent product too.
		foreach ( array_keys( $parents ) as $pid ) {
			$left = get_posts(
				array(
					'post_type'      => 'product_variation',
					'post_parent'    => $pid,
					'post_status'    => array( 'publish', 'private' ),
					'fields'         => 'ids',
					'posts_per_page' => 1,
				)
			);

			if ( ! $left ) {
				wp_trash_post( $pid );
			}
		}

		if ( function_exists( 'wc_delete_product_transients' ) ) {
			foreach ( $items as $it ) {
				wc_delete_product_transients( $it['parent_id'] ? $it['parent_id'] : $it['id'] );
			}
		}

		echo '<div class="notice notice-success inline"><p>Prebačeno u smeće: <strong>' . count( $items ) . '</strong>.</p></div>';
	}
	?>

	<form method="post">
		<?php wp_nonce_field( 'bpa_cleanup_action', 'bpa_cleanup_nonce' ); ?>
		<p>
			<label>Minimalna zarada (KM):
				<input type="number" step="0.01" min="0" name="bpa_min_km" value="<?php echo esc_attr( $min_km ); ?>" style="width:100px;">
			</label>
			&nbsp;
			<button type="submit" name="bpa_preview" class="button">Pregled</button>
		</p>
	</form>
	<?php

	if ( isset( $_POST['bpa_preview'] ) && check_admin_referer( 'bpa_cleanup_action', 'bpa_cleanup_nonce' ) ) {
		$items = bpa_find_low_profit_items( $min_km );

		if ( ! $items ) {
			echo '<p><strong>Nema proizvoda za brisanje (minimalna zarada ' . esc_html( $min_km ) . ' KM).</strong></p>';
			return;
		}
		?>
		<h3>Biće prebačeno u smeće: <?php echo count( $items ); ?></h3>
		<table class="widefat striped" style="max-width:950px;">
			<thead>
				<tr>
					<th>Proizvod</th>
					<th style="width:140px;">Brend</th>
					<th style="width:90px;">Cijena</th>
					<th style="width:80px;">Procenat</th>
					<th style="width:100px;">Zarada (KM)</th>
					<th style="width:150px;">Razlog</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $items as $it ) : ?>
				<tr>
					<td><?php echo esc_html( $it['name'] ); ?></td>
					<td><?php echo esc_html( $it['brand'] ); ?></td>
					<td><?php echo esc_html( number_format( $it['price'], 2 ) ); ?></td>
					<td><?php echo esc_html( $it['percent'] ); ?>%</td>
					<td><?php echo esc_html( number_format( $it['profit'], 2 ) ); ?></td>
					<td><?php echo esc_html( $it['reason'] ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<form method="post" onsubmit="return confirm('Prebaciti u smeće proizvode navedene gore?');">
			<?php wp_nonce_field( 'bpa_cleanup_action', 'bpa_cleanup_nonce' ); ?>
			<input type="hidden" name="bpa_min_km" value="<?php echo esc_attr( $min_km ); ?>">
			<p><button type="submit" name="bpa_delete" class="button button-primary">Obriši navedene proizvode</button></p>
		</form>
		<?php
	}
}

/* -------------------------------------------------------------------------
 * Apply to prices
 * ---------------------------------------------------------------------- */

/**
 * Return the price multiplier for a product (1 = no change).
 *
 * @param WC_Product $product Product.
 * @return float
 */
function bpa_get_product_multiplier( $product ) {
	static $cache = array();

	if ( ! $product instanceof WC_Product ) {
		return 1;
	}

	$id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();

	if ( isset( $cache[ $id ] ) ) {
		return $cache[ $id ];
	}

	$multiplier = 1;
	$taxonomy   = bpa_get_brand_taxonomy();
	$map        = bpa_get_percentages();

	if ( $taxonomy && $map ) {
		$terms = get_the_terms( $id, $taxonomy );

		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( empty( $map[ $term->term_id ] ) ) {
					continue;
				}

				$cfg = $map[ $term->term_id ];

				if ( $cfg['empty'] ) {
					continue; // Empty field = brand is not changed.
				}

				if ( $cfg['percent'] || $cfg['vat'] ) {
					$multiplier = 1 + $cfg['percent'] / 100;

					if ( $cfg['vat'] ) {
						$multiplier *= 1 + bpa_get_vat_rate() / 100;
					}

					break;
				}
			}
		}
	}

	return $cache[ $id ] = $multiplier;
}

/**
 * Adjust a product price.
 *
 * @param string|float $price   Price.
 * @param WC_Product   $product Product.
 * @return string|float
 */
function bpa_adjust_price( $price, $product ) {
	// In admin (product editing) do not touch prices so the marked-up value is never saved to the database.
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $price;
	}

	if ( '' === $price || null === $price || ! is_numeric( $price ) ) {
		return $price;
	}

	// Proizvodi iz Trend Auto feeda već imaju finalnu cijenu (osnovna cijena +
	// procenat brenda + PDV). Nikada ne primjenjuj markup drugi put.
	if ( defined( 'TREND_AUTO_SYNCING' ) && TREND_AUTO_SYNCING ) {
		return $price;
	}

	if ( $product instanceof WC_Product ) {
		$guard_id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();

		if ( $guard_id ) {
			if ( '' !== (string) get_post_meta( $guard_id, '_trend_base_price', true ) ) {
				return $price;
			}

			if ( get_post_meta( $guard_id, '_trend_price_final', true ) ) {
				return $price;
			}
		}
	}

	$multiplier = bpa_get_product_multiplier( $product );

	if ( 1 == $multiplier ) {
		return $price;
	}

	return round( (float) $price * $multiplier, wc_get_price_decimals() );
}

// Simple products and variations.
foreach ( array(
	'woocommerce_product_get_price',
	'woocommerce_product_get_regular_price',
	'woocommerce_product_get_sale_price',
	'woocommerce_product_variation_get_price',
	'woocommerce_product_variation_get_regular_price',
	'woocommerce_product_variation_get_sale_price',
) as $hook ) {
	add_filter( $hook, 'bpa_adjust_price', 99, 2 );
}

// Price range for variable products.
foreach ( array(
	'woocommerce_variation_prices_price',
	'woocommerce_variation_prices_regular_price',
	'woocommerce_variation_prices_sale_price',
) as $hook ) {
	add_filter(
		$hook,
		function ( $price, $variation, $product ) {
			return bpa_adjust_price( $price, $variation );
		},
		99,
		3
	);
}

// When settings change, the variable price cache must be refreshed.
add_filter(
	'woocommerce_get_variation_prices_hash',
	function ( $hash ) {
		$hash[] = md5( wp_json_encode( bpa_get_percentages() ) . bpa_get_vat_rate() );
		return $hash;
	}
);
