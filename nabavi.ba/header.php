<?php
/**
 * Theme header.
 *
 * @package Nabavi
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#main"><?php esc_html_e( 'Preskoči na sadržaj', 'nabavi' ); ?></a>

<div id="page" class="site">
	<?php if ( nabavi_get_phone() || nabavi_get_email() ) : ?>
		<div class="nabavi-topbar">
			<div class="nabavi-container nabavi-topbar__inner">
				<div class="nabavi-topbar__contact">
					<?php if ( nabavi_get_phone() ) : ?>
						<a href="tel:<?php echo esc_attr( nabavi_get_phone_href() ); ?>">
							<?php echo nabavi_get_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span><?php echo esc_html( nabavi_get_phone() ); ?></span>
						</a>
					<?php endif; ?>

					<?php if ( nabavi_get_phone() && nabavi_get_email() ) : ?>
						<span class="nabavi-topbar__sep" aria-hidden="true"></span>
					<?php endif; ?>

					<?php if ( nabavi_get_email() ) : ?>
						<a href="mailto:<?php echo esc_attr( nabavi_get_email() ); ?>">
							<?php echo nabavi_get_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span><?php echo esc_html( nabavi_get_email() ); ?></span>
						</a>
					<?php endif; ?>
				</div>

				<div class="nabavi-topbar__links">
					<a href="<?php echo esc_url( home_url( '/o-nama/' ) ); ?>"><?php esc_html_e( 'O nama', 'nabavi' ); ?></a>
					<a href="<?php echo esc_url( home_url( '/kontakt/' ) ); ?>"><?php esc_html_e( 'Kontakt', 'nabavi' ); ?></a>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<header id="masthead" class="nabavi-header">
		<div class="nabavi-container nabavi-header__inner">
			<div class="nabavi-header__top">
				<?php nabavi_site_logo( 'header' ); ?>

				<form role="search" method="get" class="nabavi-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<label class="screen-reader-text" for="nabavi-search-field"><?php esc_html_e( 'Pretraži', 'nabavi' ); ?></label>
					<input id="nabavi-search-field" class="nabavi-search__input" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Pretraži..." autocomplete="off">
					<button class="nabavi-search__submit" type="submit" aria-label="<?php esc_attr_e( 'Pretraži', 'nabavi' ); ?>">
						<?php echo nabavi_get_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</button>
				</form>

				<?php nabavi_header_actions(); ?>
			</div>

			<div class="nabavi-header__nav-row">
				<nav class="nabavi-nav" aria-label="<?php esc_attr_e( 'Glavna navigacija', 'nabavi' ); ?>">
					<?php nabavi_header_nav( 'primary' ); ?>
				</nav>
			</div>
		</div>
	</header>

	<div id="nabavi-mobile-nav" class="nabavi-mobile-nav" hidden>
		<button class="nabavi-mobile-nav__overlay" type="button" aria-label="<?php esc_attr_e( 'Zatvori meni', 'nabavi' ); ?>"></button>
		<div class="nabavi-mobile-nav__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Meni', 'nabavi' ); ?>">
			<div class="nabavi-mobile-nav__head">
				<span class="nabavi-mobile-nav__title"><?php esc_html_e( 'Meni', 'nabavi' ); ?></span>
				<button class="nabavi-mobile-nav__close" type="button" aria-label="<?php esc_attr_e( 'Zatvori meni', 'nabavi' ); ?>">
					<?php echo nabavi_get_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
			</div>

			<nav class="nabavi-mobile-nav__nav" aria-label="<?php esc_attr_e( 'Mobilna navigacija', 'nabavi' ); ?>">
				<?php nabavi_header_nav( 'mobile' ); ?>
			</nav>

			<div class="nabavi-mobile-nav__actions">
				<a href="<?php echo esc_url( nabavi_get_account_url() ); ?>">
					<?php echo nabavi_get_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php echo esc_html( is_user_logged_in() ? __( 'Moj račun', 'nabavi' ) : __( 'Prijava', 'nabavi' ) ); ?></span>
				</a>
				<a href="<?php echo esc_url( nabavi_get_cart_url() ); ?>">
					<?php echo nabavi_get_icon( 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Korpa', 'nabavi' ); ?> (<span class="nabavi-cart-count"><?php echo esc_html( nabavi_get_cart_count() ); ?></span>)</span>
				</a>
			</div>
		</div>
	</div>

	<main id="main" class="site-main">
