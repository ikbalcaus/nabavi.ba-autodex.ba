<?php
/**
 * Login and registration forms.
 *
 * @package Nabavi
 */

defined( 'ABSPATH' ) || exit;

if ( is_user_logged_in() ) {
	return;
}

if ( function_exists( 'wc_print_notices' ) ) {
	wc_print_notices();
}
?>
<div class="nabavi-login">
	<?php do_action( 'woocommerce_before_customer_login_form' ); ?>

	<header class="nabavi-login__head">
		<h2 class="nabavi-login__title"><?php esc_html_e( 'Prijava', 'nabavi' ); ?></h2>
	</header>

	<form class="woocommerce-form woocommerce-form-login login" method="post" <?php do_action( 'woocommerce_login_form_tag' ); ?>>
		<?php do_action( 'woocommerce_login_form_start' ); ?>

		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
			<label for="username"><?php esc_html_e( 'Korisničko ime ili email', 'nabavi' ); ?></label>
			<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) && is_string( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing ?>" />
		</p>

		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
			<label for="password"><?php esc_html_e( 'Lozinka', 'nabavi' ); ?></label>
			<span class="password-input">
				<input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" />
				<button type="button" class="show-password-input" aria-label="<?php esc_attr_e( 'Prikaži lozinku', 'nabavi' ); ?>" aria-pressed="false">
					<?php echo nabavi_get_icon( 'eye' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
			</span>
		</p>

		<p class="form-row nabavi-login__row">
			<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
				<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" />
				<span><?php esc_html_e( 'Zapamti me', 'nabavi' ); ?></span>
			</label>

			<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
			<input type="hidden" name="redirect" value="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">

			<button type="submit" class="woocommerce-button button woocommerce-form-login__submit" name="login" value="<?php esc_attr_e( 'Prijavi se', 'nabavi' ); ?>"><?php esc_html_e( 'Prijavi se', 'nabavi' ); ?></button>
		</p>

		<p class="woocommerce-LostPassword lost_password">
			<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'lost-password' ) ); ?>"><?php esc_html_e( 'Zaboravljena lozinka?', 'nabavi' ); ?></a>
		</p>

		<?php do_action( 'woocommerce_login_form_end' ); ?>
	</form>

	<?php if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) : ?>
		<div class="nabavi-register">
			<h2 class="nabavi-register__title"><?php esc_html_e( 'Registracija', 'nabavi' ); ?></h2>

			<form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action( 'woocommerce_register_form_tag' ); ?>>
				<?php do_action( 'woocommerce_register_form_start' ); ?>

				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<label for="reg_email"><?php esc_html_e( 'Email adresa', 'nabavi' ); ?> <span class="required">*</span></label>
					<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) && is_string( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing ?>" />
				</p>

				<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>
					<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
						<label for="reg_password"><?php esc_html_e( 'Lozinka', 'nabavi' ); ?> <span class="required">*</span></label>
						<span class="password-input">
							<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" />
							<button type="button" class="show-password-input" aria-label="<?php esc_attr_e( 'Prikaži lozinku', 'nabavi' ); ?>" aria-pressed="false">
								<?php echo nabavi_get_icon( 'eye' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</button>
						</span>
					</p>
				<?php endif; ?>

				<?php do_action( 'woocommerce_register_form' ); ?>

				<p class="woocommerce-form-row form-row">
					<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
					<button type="submit" class="woocommerce-button button woocommerce-form-register__submit" name="register" value="<?php esc_attr_e( 'Registracija', 'nabavi' ); ?>"><?php esc_html_e( 'Registracija', 'nabavi' ); ?></button>
				</p>

				<?php do_action( 'woocommerce_register_form_end' ); ?>
			</form>
		</div>
	<?php endif; ?>
</div>
