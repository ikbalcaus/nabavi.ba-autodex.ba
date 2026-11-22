<?php
/**
 * Nabavi.ba theme bootstrap.
 *
 * All functionality is split into small, focused files inside /inc/.
 *
 * @package Nabavi
 */

defined( 'ABSPATH' ) || exit;

define( 'NABAVI_VERSION', '1.0.0' );
define( 'NABAVI_DIR', get_template_directory() );
define( 'NABAVI_URI', get_template_directory_uri() );

/**
 * Load a theme include file.
 *
 * @param string $file File name inside /inc/.
 */
function nabavi_require( $file ) {
	$path = NABAVI_DIR . '/inc/' . ltrim( $file, '/' );

	if ( file_exists( $path ) ) {
		require_once $path;
	}
}

nabavi_require( 'helpers.php' );
nabavi_require( 'setup.php' );
nabavi_require( 'customizer.php' );
nabavi_require( 'assets.php' );
nabavi_require( 'template-tags.php' );
nabavi_require( 'translations.php' );
nabavi_require( 'woocommerce.php' );
nabavi_require( 'shortcodes.php' );
nabavi_require( 'contact.php' );
nabavi_require( 'seo.php' );
nabavi_require( 'sitemap.php' );
