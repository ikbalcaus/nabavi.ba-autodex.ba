<?php
/**
 * Quantity input with plus/minus controls.
 *
 * @package Nabavi
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $args ) ) {
	$args = array();
}

$product = isset( $GLOBALS['product'] ) && $GLOBALS['product'] instanceof WC_Product ? $GLOBALS['product'] : null;

$args = wp_parse_args(
	$args,
	array(
		'input_id'     => uniqid( 'quantity_' ),
		'input_name'   => 'quantity',
		'input_value'  => '1',
		'max_value'    => apply_filters( 'woocommerce_quantity_input_max', -1, $product ),
		'min_value'    => apply_filters( 'woocommerce_quantity_input_min', 0, $product ),
		'step'         => apply_filters( 'woocommerce_quantity_input_step', 1, $product ),
		'pattern'      => apply_filters( 'woocommerce_quantity_input_pattern', has_filter( 'woocommerce_stock_amount', 'intval' ) ? '[0-9]*' : '', $product ),
		'inputmode'    => apply_filters( 'woocommerce_quantity_input_inputmode', has_filter( 'woocommerce_stock_amount', 'intval' ) ? 'numeric' : '', $product ),
		'product_name' => $product ? $product->get_title() : '',
	)
);

$max_value = ( '' === $args['max_value'] || (float) $args['max_value'] <= 0 ) ? '' : (float) $args['max_value'];
$min_value = '' === $args['min_value'] ? 0 : (float) $args['min_value'];
$step      = '' === $args['step'] ? 1 : (float) $args['step'];

if ( $max_value && $min_value === $max_value ) {
	?>
	<div class="quantity hidden">
		<input type="hidden" id="<?php echo esc_attr( $args['input_id'] ); ?>" class="qty" name="<?php echo esc_attr( $args['input_name'] ); ?>" value="<?php echo esc_attr( $min_value ); ?>">
	</div>
	<?php
} else {
	$label = $args['product_name'] ? sprintf( '%s količina', $args['product_name'] ) : 'Količina';
	?>
	<div class="quantity nabavi-quantity" data-min="<?php echo esc_attr( $min_value ); ?>" data-max="<?php echo esc_attr( $max_value ); ?>" data-step="<?php echo esc_attr( $step ); ?>">
		<button type="button" class="qty-btn qty-minus" aria-label="<?php esc_attr_e( 'Smanji količinu', 'nabavi' ); ?>">&minus;</button>
		<label class="screen-reader-text" for="<?php echo esc_attr( $args['input_id'] ); ?>"><?php echo esc_html( $label ); ?></label>
		<input
			type="number"
			id="<?php echo esc_attr( $args['input_id'] ); ?>"
			class="input-text qty text"
			name="<?php echo esc_attr( $args['input_name'] ); ?>"
			value="<?php echo esc_attr( $args['input_value'] ); ?>"
			aria-label="<?php echo esc_attr( $label ); ?>"
			min="<?php echo esc_attr( $min_value ); ?>"
			<?php echo $max_value ? 'max="' . esc_attr( $max_value ) . '"' : ''; ?>
			step="<?php echo esc_attr( $step ); ?>"
			<?php echo $args['pattern'] ? 'pattern="' . esc_attr( $args['pattern'] ) . '"' : ''; ?>
			<?php echo $args['inputmode'] ? 'inputmode="' . esc_attr( $args['inputmode'] ) . '"' : ''; ?>
			autocomplete="off"
		>
		<button type="button" class="qty-btn qty-plus" aria-label="<?php esc_attr_e( 'Povećaj količinu', 'nabavi' ); ?>">&plus;</button>
	</div>
	<?php
}
