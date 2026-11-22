<?php
/**
 * Customizer options.
 *
 * @package Autodex
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sanitize a checkbox.
 *
 * @param mixed $value Value.
 * @return bool
 */
function autodex_sanitize_checkbox( $value ) {
	return (bool) $value;
}

/**
 * Register theme options.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 */
function autodex_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'autodex_options',
		array(
			'title'       => __( 'Autodex.ba opcije', 'autodex' ),
			'priority'    => 30,
			'description' => __( 'Kontakt podaci i tekstovi u headeru/footeru.', 'autodex' ),
		)
	);

	$fields = array(
		'autodex_phone'             => array(
			'label'   => __( 'Telefon', 'autodex' ),
			'default' => '+387 61 306-122',
			'type'    => 'text',
		),
		'autodex_email'             => array(
			'label'   => __( 'Email', 'autodex' ),
			'default' => 'info@autodex.ba',
			'type'    => 'email',
		),
		'autodex_contact_recipient' => array(
			'label'   => __( 'Email koji prima kontakt poruke', 'autodex' ),
			'default' => 'info@autodex.ba',
			'type'    => 'email',
		),
		'autodex_footer_text'       => array(
			'label'   => __( 'Tekst u footeru', 'autodex' ),
			'default' => 'Auto dijelovi, motorna ulja i servisna rješenja na jednom mjestu. Autodex.ba spaja provjeren kvalitet, stručnu podršku i brzu dostavu.',
			'type'    => 'textarea',
		),
		'autodex_copyright'         => array(
			'label'   => __( 'Copyright tekst', 'autodex' ),
			'default' => 'Sva prava zadržana | © ' . gmdate( 'Y' ) . ' Autodex.ba',
			'type'    => 'text',
		),
	);

	foreach ( $fields as $key => $field ) {
		$sanitize = 'sanitize_text_field';

		if ( 'email' === $field['type'] ) {
			$sanitize = 'sanitize_email';
		} elseif ( 'textarea' === $field['type'] ) {
			$sanitize = 'sanitize_textarea_field';
		}

		$wp_customize->add_setting(
			$key,
			array(
				'default'           => $field['default'],
				'sanitize_callback' => $sanitize,
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			$key,
			array(
				'label'   => $field['label'],
				'section' => 'autodex_options',
				'type'    => 'textarea' === $field['type'] ? 'textarea' : $field['type'],
			)
		);
	}

	$wp_customize->add_setting(
		'autodex_show_language',
		array(
			'default'           => true,
			'sanitize_callback' => 'autodex_sanitize_checkbox',
		)
	);

	$wp_customize->add_control(
		'autodex_show_language',
		array(
			'label'   => __( 'Prikaži GTranslate izbornik jezika u headeru', 'autodex' ),
			'section' => 'autodex_options',
			'type'    => 'checkbox',
		)
	);
}
add_action( 'customize_register', 'autodex_customize_register' );
