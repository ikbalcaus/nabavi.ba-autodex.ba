<?php
/**
 * Customizer options.
 *
 * @package Nabavi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sanitize a checkbox.
 *
 * @param mixed $value Value.
 * @return bool
 */
function nabavi_sanitize_checkbox( $value ) {
	return (bool) $value;
}

/**
 * Register theme options.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 */
function nabavi_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'nabavi_options',
		array(
			'title'       => __( 'Nabavi.ba opcije', 'nabavi' ),
			'priority'    => 30,
			'description' => __( 'Kontakt podaci i tekstovi u headeru/footeru.', 'nabavi' ),
		)
	);

	$fields = array(
		'nabavi_phone'             => array(
			'label'   => __( 'Telefon', 'nabavi' ),
			'default' => '+387 61 306-122',
			'type'    => 'text',
		),
		'nabavi_email'             => array(
			'label'   => __( 'Email', 'nabavi' ),
			'default' => 'info@nabavi.ba',
			'type'    => 'email',
		),
		'nabavi_contact_recipient' => array(
			'label'   => __( 'Email koji prima kontakt poruke', 'nabavi' ),
			'default' => 'info@nabavi.ba',
			'type'    => 'email',
		),
		'nabavi_footer_text'       => array(
			'label'   => __( 'Tekst u footeru', 'nabavi' ),
			'default' => 'Tvoja online destinacija za jednostavnu i sigurnu kupovinu, gdje najbolje ponude i kvalitet dolaze na dohvat ruke.',
			'type'    => 'textarea',
		),
		'nabavi_copyright'         => array(
			'label'   => __( 'Copyright tekst', 'nabavi' ),
			'default' => 'Sva prava zadržana | © ' . gmdate( 'Y' ) . ' Nabavi.ba',
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
				'section' => 'nabavi_options',
				'type'    => 'textarea' === $field['type'] ? 'textarea' : $field['type'],
			)
		);
	}

	$wp_customize->add_setting(
		'nabavi_show_language',
		array(
			'default'           => true,
			'sanitize_callback' => 'nabavi_sanitize_checkbox',
		)
	);

	$wp_customize->add_control(
		'nabavi_show_language',
		array(
			'label'   => __( 'Prikaži GTranslate izbornik jezika u headeru', 'nabavi' ),
			'section' => 'nabavi_options',
			'type'    => 'checkbox',
		)
	);
}
add_action( 'customize_register', 'nabavi_customize_register' );
