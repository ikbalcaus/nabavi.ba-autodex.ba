<?php
/**
 * Theme footer.
 *
 * @package Nabavi
 */

defined( 'ABSPATH' ) || exit;
?>
	</main><!-- #main -->

	<footer id="colophon" class="nabavi-footer">
		<div class="nabavi-container nabavi-footer__inner">
			<div class="nabavi-footer__top">
				<div class="nabavi-footer__brand">
					<div class="nabavi-footer__logo">
						<?php nabavi_site_logo( 'header' ); ?>
					</div>
					<p class="nabavi-footer__text"><?php echo esc_html( nabavi_get_footer_text() ); ?></p>
				</div>

				<div class="nabavi-footer__links">
					<h2 class="nabavi-footer__title"><?php esc_html_e( 'Korisni linkovi', 'nabavi' ); ?></h2>
					<?php
					if ( has_nav_menu( 'footer' ) ) {
						wp_nav_menu(
							array(
								'theme_location' => 'footer',
								'container'      => false,
								'menu_class'     => 'nabavi-footer__menu',
								'menu_id'        => 'nabavi-footer-menu',
								'depth'          => 1,
							)
						);
					} else {
						?>
						<ul class="nabavi-footer__menu">
							<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Početna', 'nabavi' ); ?></a></li>
							<li><a href="<?php echo esc_url( nabavi_get_shop_url() ); ?>"><?php esc_html_e( 'Shop', 'nabavi' ); ?></a></li>
							<li><a href="<?php echo esc_url( home_url( '/o-nama/' ) ); ?>"><?php esc_html_e( 'O nama', 'nabavi' ); ?></a></li>
							<li><a href="<?php echo esc_url( home_url( '/kontakt/' ) ); ?>"><?php esc_html_e( 'Kontakt', 'nabavi' ); ?></a></li>
							<li><a href="<?php echo esc_url( nabavi_get_account_url() ); ?>"><?php esc_html_e( 'Moj račun', 'nabavi' ); ?></a></li>
							<li><a href="<?php echo esc_url( nabavi_get_cart_url() ); ?>"><?php esc_html_e( 'Korpa', 'nabavi' ); ?></a></li>
						</ul>
						<?php
					}
					?>
				</div>

				<div class="nabavi-footer__categories">
					<h2 class="nabavi-footer__title"><?php esc_html_e( 'Kategorije', 'nabavi' ); ?></h2>
					<ul class="nabavi-footer__cat-list">
						<?php
						$footer_categories   = nabavi_get_curated_categories();
						$footer_categories[] = array(
							'label' => 'Ostalo',
							'url'   => nabavi_get_category_url( 'ostalo' ),
						);

						foreach ( $footer_categories as $footer_category ) :
							$footer_external = ! empty( $footer_category['url'] ) && false !== strpos( $footer_category['url'], 'autodex.ba' );
							?>
							<li><a href="<?php echo esc_url( $footer_category['url'] ); ?>"<?php echo $footer_external ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html( $footer_category['label'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>

				<div class="nabavi-footer__contact">
					<h2 class="nabavi-footer__title"><?php esc_html_e( 'Kontakt informacije', 'nabavi' ); ?></h2>
					<ul class="nabavi-footer__contact-list">
						<li>
							<span class="nabavi-footer__contact-icon">
								<?php echo nabavi_get_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</span>
							<div>
								<strong><?php esc_html_e( 'Telefon', 'nabavi' ); ?></strong>
								<a href="tel:<?php echo esc_attr( nabavi_get_phone_href() ); ?>"><?php echo esc_html( nabavi_get_phone() ); ?></a>
							</div>
						</li>
						<li>
							<span class="nabavi-footer__contact-icon">
								<?php echo nabavi_get_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</span>
							<div>
								<strong><?php esc_html_e( 'Email', 'nabavi' ); ?></strong>
								<a href="mailto:<?php echo esc_attr( nabavi_get_email() ); ?>"><?php echo esc_html( nabavi_get_email() ); ?></a>
							</div>
						</li>
					</ul>
				</div>
			</div>

			<?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
				<div class="nabavi-footer__widgets">
					<?php dynamic_sidebar( 'footer-1' ); ?>
				</div>
			<?php endif; ?>

			<div class="nabavi-footer__divider" role="presentation"></div>

			<div class="nabavi-footer__bottom">
				<p><?php echo esc_html( nabavi_get_copyright() ); ?></p>
				<p class="nabavi-footer__credit">
					<a href="https://webcentar.ba/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Web dizajn i izrada: webcentar.ba', 'nabavi' ); ?></a>
				</p>
			</div>
		</div>
	</footer>
</div><!-- #page -->

<?php wp_footer(); ?>
</body>
</html>
