<?php
/**
 * 404 template.
 *
 * @package Nabavi
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="nabavi-page-wrap">
	<div class="nabavi-container nabavi-container--narrow">
		<div class="nabavi-404">
			<p class="nabavi-404__code">404</p>
			<h1 class="nabavi-404__title"><?php esc_html_e( 'Stranica nije pronađena', 'nabavi' ); ?></h1>
			<p class="nabavi-404__text"><?php esc_html_e( 'Stranica koju tražite ne postoji ili je premještena. Vratite se na početnu ili nastavite kupovinu.', 'nabavi' ); ?></p>
			<div class="nabavi-404__actions">
				<a class="nabavi-button" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Početna', 'nabavi' ); ?></a>
				<a class="nabavi-button nabavi-button--outline" href="<?php echo esc_url( nabavi_get_shop_url() ); ?>"><?php esc_html_e( 'Shop', 'nabavi' ); ?></a>
			</div>
			<div class="nabavi-404__search"><?php get_search_form(); ?></div>
		</div>
	</div>
</div>
<?php
get_footer();
