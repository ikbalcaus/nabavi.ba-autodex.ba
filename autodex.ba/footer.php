<?php
/**
 * Theme footer.
 *
 * @package Autodex
 */

defined( 'ABSPATH' ) || exit;
?>
	</main><!-- #main -->

	<footer id="colophon" class="autodex-footer">
		<div class="autodex-container autodex-footer__inner">
			<div class="autodex-footer__top">
				<div class="autodex-footer__brand">
					<?php autodex_site_logo( 'footer' ); ?>
					<p class="autodex-footer__text"><?php echo esc_html( autodex_get_footer_text() ); ?></p>
				</div>

				<div class="autodex-footer__links">
					<h2 class="autodex-footer__title"><?php esc_html_e( 'Korisni linkovi', 'autodex' ); ?></h2>
					<?php
					if ( has_nav_menu( 'footer' ) ) {
						wp_nav_menu(
							array(
								'theme_location' => 'footer',
								'container'      => false,
								'menu_class'     => 'autodex-footer__menu',
								'menu_id'        => 'autodex-footer-menu',
								'depth'          => 1,
							)
						);
					} else {
						?>
						<ul class="autodex-footer__menu">
							<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Početna', 'autodex' ); ?></a></li>
							<li><a href="<?php echo esc_url( autodex_get_shop_url() ); ?>"><?php esc_html_e( 'Shop', 'autodex' ); ?></a></li>
							<li><a href="<?php echo esc_url( home_url( '/o-nama/' ) ); ?>"><?php esc_html_e( 'O nama', 'autodex' ); ?></a></li>
							<li><a href="<?php echo esc_url( home_url( '/kontakt/' ) ); ?>"><?php esc_html_e( 'Kontakt', 'autodex' ); ?></a></li>
							<li><a href="<?php echo esc_url( autodex_get_account_url() ); ?>"><?php esc_html_e( 'Moj račun', 'autodex' ); ?></a></li>
							<li><a href="<?php echo esc_url( autodex_get_cart_url() ); ?>"><?php esc_html_e( 'Korpa', 'autodex' ); ?></a></li>
						</ul>
						<?php
					}
					?>
				</div>

				<div class="autodex-footer__categories">
					<h2 class="autodex-footer__title"><?php esc_html_e( 'Kategorije', 'autodex' ); ?></h2>
					<ul class="autodex-footer__cat-list">
						<?php foreach ( autodex_get_curated_categories() as $footer_category ) : ?>
							<li><a href="<?php echo esc_url( $footer_category['url'] ); ?>"><?php echo esc_html( $footer_category['label'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>

				<div class="autodex-footer__contact">
					<h2 class="autodex-footer__title"><?php esc_html_e( 'Kontakt informacije', 'autodex' ); ?></h2>
					<ul class="autodex-footer__contact-list">
						<li>
							<a href="tel:<?php echo esc_attr( autodex_get_phone_href() ); ?>">
								<strong><?php esc_html_e( 'Telefon:', 'autodex' ); ?></strong>
								<?php echo esc_html( autodex_get_phone() ); ?>
							</a>
						</li>
						<li>
							<a href="mailto:<?php echo esc_attr( autodex_get_email() ); ?>">
								<strong><?php esc_html_e( 'Email:', 'autodex' ); ?></strong>
								<?php echo esc_html( autodex_get_email() ); ?>
							</a>
						</li>
					</ul>
				</div>
			</div>

			<?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
				<div class="autodex-footer__widgets">
					<?php dynamic_sidebar( 'footer-1' ); ?>
				</div>
			<?php endif; ?>

			<div class="autodex-footer__divider" role="presentation"></div>

			<div class="autodex-footer__bottom">
				<p><?php echo esc_html( autodex_get_copyright() ); ?></p>
				<p class="autodex-footer__credit">
					<a href="https://webcentar.ba/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Web dizajn i izrada: webcentar.ba', 'autodex' ); ?></a>
				</p>
			</div>
		</div>
	</footer>
</div><!-- #page -->

<?php wp_footer(); ?>
</body>
</html>
