<?php
/**
 * Search form.
 *
 * @package Nabavi
 */

defined( 'ABSPATH' ) || exit;
?>
<form role="search" method="get" class="nabavi-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="nabavi-search-form-field"><?php esc_html_e( 'Pretraži', 'nabavi' ); ?></label>
	<input id="nabavi-search-form-field" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Pretraži...">
	<button type="submit" class="nabavi-search-form__submit"><?php esc_html_e( 'Pretraži', 'nabavi' ); ?></button>
</form>
