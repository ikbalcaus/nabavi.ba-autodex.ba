<?php
/**
 * Theme header.
 *
 * @package Autodex
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

<a class="skip-link screen-reader-text" href="#main"><?php esc_html_e( 'Preskoči na sadržaj', 'autodex' ); ?></a>

<div id="page" class="site">
	<header id="masthead" class="autodex-header">
		<div class="autodex-topbar">
			<div class="autodex-container autodex-topbar__inner">
				<div class="autodex-topbar__contact">
					<a href="tel:<?php echo esc_attr( autodex_get_phone_href() ); ?>">
						<?php echo autodex_get_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( autodex_get_phone() ); ?></span>
					</a>

					<span class="autodex-topbar__divider" aria-hidden="true"></span>

					<a href="mailto:<?php echo esc_attr( autodex_get_email() ); ?>">
						<?php echo autodex_get_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( autodex_get_email() ); ?></span>
					</a>
				</div>

				<div class="autodex-topbar__meta">
					<span class="autodex-topbar__hours">
						<?php esc_html_e( 'Radno vrijeme:', 'autodex' ); ?>
						<strong><?php esc_html_e( 'Pon–Pet 08:00–17:00', 'autodex' ); ?></strong>
					</span>
					<span class="autodex-topbar__divider" aria-hidden="true"></span>
					<a class="autodex-topbar__link" href="<?php echo esc_url( autodex_get_inquiry_url() ); ?>"><?php esc_html_e( 'Pošaljite upit za vaše vozilo', 'autodex' ); ?></a>
				</div>
			</div>
		</div>

		<div class="autodex-header__main">
			<div class="autodex-container autodex-header__top">
				<?php autodex_site_logo( 'header' ); ?>

				<form role="search" method="get" class="autodex-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<label class="screen-reader-text" for="autodex-search-field"><?php esc_html_e( 'Pretraži', 'autodex' ); ?></label>
					<input id="autodex-search-field" class="autodex-search__input" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Pretraži dijelove, ulja, broj šasije…', 'autodex' ); ?>" autocomplete="off">
					<button class="autodex-search__submit" type="submit" aria-label="<?php esc_attr_e( 'Pretraži', 'autodex' ); ?>">
						<?php echo autodex_get_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</button>
				</form>

				<?php autodex_header_actions(); ?>
			</div>
		</div>

		<div class="autodex-header__nav-row">
			<div class="autodex-container autodex-header__nav-inner">
				<nav class="autodex-nav" aria-label="<?php esc_attr_e( 'Glavna navigacija', 'autodex' ); ?>">
					<?php autodex_header_nav( 'primary' ); ?>
				</nav>

				<a class="autodex-header__cta" href="<?php echo esc_url( autodex_get_inquiry_url() ); ?>">
					<?php esc_html_e( 'Pošaljite upit', 'autodex' ); ?>
					<?php echo autodex_get_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
			</div>
		</div>
	</header>

	<div id="autodex-mobile-nav" class="autodex-mobile-nav" hidden>
		<button class="autodex-mobile-nav__overlay" type="button" aria-label="<?php esc_attr_e( 'Zatvori meni', 'autodex' ); ?>"></button>
		<div class="autodex-mobile-nav__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Meni', 'autodex' ); ?>">
			<div class="autodex-mobile-nav__head">
				<span class="autodex-mobile-nav__title"><?php esc_html_e( 'Meni', 'autodex' ); ?></span>
				<button class="autodex-mobile-nav__close" type="button" aria-label="<?php esc_attr_e( 'Zatvori meni', 'autodex' ); ?>">
					<?php echo autodex_get_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
			</div>

			<nav class="autodex-mobile-nav__nav" aria-label="<?php esc_attr_e( 'Mobilna navigacija', 'autodex' ); ?>">
				<?php autodex_header_nav( 'mobile' ); ?>
			</nav>

			<div class="autodex-mobile-nav__actions">
				<a href="<?php echo esc_url( autodex_get_account_url() ); ?>">
					<?php echo autodex_get_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php echo esc_html( is_user_logged_in() ? __( 'Moj račun', 'autodex' ) : __( 'Prijava', 'autodex' ) ); ?></span>
				</a>
				<a href="<?php echo esc_url( autodex_get_cart_url() ); ?>">
					<?php echo autodex_get_icon( 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Korpa', 'autodex' ); ?> (<span class="autodex-cart-count"><?php echo esc_html( autodex_get_cart_count() ); ?></span>)</span>
				</a>
			</div>
		</div>
	</div>

	<main id="main" class="site-main">
