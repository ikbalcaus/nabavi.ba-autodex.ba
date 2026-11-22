<?php
/**
 * Autodex.ba theme bootstrap.
 *
 * All functionality is split into small, focused files inside /inc/.
 *
 * @package Autodex
 */

defined( 'ABSPATH' ) || exit;

define( 'AUTODEX_VERSION', '1.0.0' );
define( 'AUTODEX_DIR', get_template_directory() );
define( 'AUTODEX_URI', get_template_directory_uri() );

/**
 * Load a theme include file.
 *
 * @param string $file File name inside /inc/.
 */
function autodex_require( $file ) {
	$path = AUTODEX_DIR . '/inc/' . ltrim( $file, '/' );

	if ( file_exists( $path ) ) {
		require_once $path;
	}
}

autodex_require( 'helpers.php' );
autodex_require( 'setup.php' );
autodex_require( 'customizer.php' );
autodex_require( 'assets.php' );
autodex_require( 'template-tags.php' );
autodex_require( 'translations.php' );
autodex_require( 'woocommerce.php' );
autodex_require( 'shortcodes.php' );
autodex_require( 'contact.php' );
autodex_require( 'inquiry.php' );
autodex_require( 'seo.php' );
autodex_require( 'sitemap.php' );

/**
 * Load optional code snippets from /snippets.
 */
function autodex_load_snippets() {
	$snippets = glob( AUTODEX_DIR . '/snippets/*.php' );

	if ( empty( $snippets ) ) {
		return;
	}

	sort( $snippets );

	foreach ( $snippets as $snippet ) {
		require_once $snippet;
	}
}
autodex_load_snippets();
