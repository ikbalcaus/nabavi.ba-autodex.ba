<?php
/**
 * 404 template.
 *
 * @package Autodex
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="autodex-page-wrap">
	<div class="autodex-container autodex-container--narrow">
		<div class="autodex-404">
			<p class="autodex-404__code">404</p>
			<h1 class="autodex-404__title"><?php esc_html_e( 'Stranica nije pronađena', 'autodex' ); ?></h1>
			<p class="autodex-404__text"><?php esc_html_e( 'Stranica koju tražite ne postoji ili je premještena. Vratite se na početnu ili nastavite kupovinu.', 'autodex' ); ?></p>
			<div class="autodex-404__actions">
				<a class="autodex-button" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Početna', 'autodex' ); ?></a>
				<a class="autodex-button autodex-button--outline" href="<?php echo esc_url( autodex_get_shop_url() ); ?>"><?php esc_html_e( 'Shop', 'autodex' ); ?></a>
			</div>
			<div class="autodex-404__search"><?php get_search_form(); ?></div>
		</div>
	</div>
</div>
<?php
get_footer();
