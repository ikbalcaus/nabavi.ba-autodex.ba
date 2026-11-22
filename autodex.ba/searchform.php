<?php
/**
 * Search form.
 *
 * @package Autodex
 */

defined( 'ABSPATH' ) || exit;
?>
<form role="search" method="get" class="autodex-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="autodex-search-form-field"><?php esc_html_e( 'Pretraži', 'autodex' ); ?></label>
	<input id="autodex-search-form-field" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Pretraži...">
	<button type="submit" class="autodex-search-form__submit"><?php esc_html_e( 'Pretraži', 'autodex' ); ?></button>
</form>
