<?php
/**
 * Contact form (replaces the WPForms shortcode used on the original site).
 *
 * @package Autodex
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handle the contact form submission.
 */
function autodex_handle_contact_form() {
	$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/kontakt/' );

	if ( ! isset( $_POST['autodex_contact_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['autodex_contact_nonce'] ) ), 'autodex_contact_form' ) ) {
		wp_safe_redirect( add_query_arg( 'autodex_contact', 'error', $redirect ) );
		exit;
	}

	$honeypot = isset( $_POST['autodex_hp'] ) ? sanitize_text_field( wp_unslash( $_POST['autodex_hp'] ) ) : '';
	$time     = isset( $_POST['autodex_time'] ) ? (int) $_POST['autodex_time'] : 0;

	if ( '' !== $honeypot || ( $time && ( time() - $time ) < 2 ) ) {
		wp_safe_redirect( add_query_arg( 'autodex_contact', 'error', $redirect ) );
		exit;
	}

	$first_name = isset( $_POST['autodex_first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['autodex_first_name'] ) ) : '';
	$last_name  = isset( $_POST['autodex_last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['autodex_last_name'] ) ) : '';
	$email      = isset( $_POST['autodex_email'] ) ? sanitize_email( wp_unslash( $_POST['autodex_email'] ) ) : '';
	$phone      = isset( $_POST['autodex_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['autodex_phone'] ) ) : '';
	$message    = isset( $_POST['autodex_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['autodex_message'] ) ) : '';

	if ( '' === $first_name || '' === $last_name || '' === $message || ! is_email( $email ) || '' === $phone ) {
		wp_safe_redirect( add_query_arg( 'autodex_contact', 'error', $redirect ) );
		exit;
	}

	$recipient = apply_filters( 'autodex_contact_email_recipient', autodex_get_contact_recipient() );
	$subject   = apply_filters( 'autodex_contact_email_subject', 'Nova poruka sa Autodex.ba' );

	$body  = "Nova poruka sa kontakt forme:\n\n";
	$body .= "Ime: {$first_name}\n";
	$body .= "Prezime: {$last_name}\n";
	$body .= "Email: {$email}\n";
	$body .= "Telefon: {$phone}\n\n";
	$body .= "Poruka:\n{$message}\n";

	$headers = array(
		'Content-Type: text/plain; charset=UTF-8',
		'Reply-To: ' . $first_name . ' ' . $last_name . ' <' . $email . '>',
	);

	$sent = wp_mail( $recipient, $subject, $body, $headers );

	wp_safe_redirect(
		add_query_arg(
			'autodex_contact',
			$sent ? 'success' : 'error',
			$redirect
		)
	);
	exit;
}
add_action( 'admin_post_autodex_contact', 'autodex_handle_contact_form' );
add_action( 'admin_post_nopriv_autodex_contact', 'autodex_handle_contact_form' );

/**
 * Display a contact form notice.
 */
function autodex_contact_notice() {
	if ( ! isset( $_GET['autodex_contact'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	$status = sanitize_key( wp_unslash( $_GET['autodex_contact'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( 'success' === $status ) {
		echo '<div class="autodex-notice autodex-notice--success" role="status">' . esc_html__( 'Vaša poruka je uspješno poslana. Odgovorit ćemo vam u najkraćem roku.', 'autodex' ) . '</div>';
	} elseif ( 'error' === $status ) {
		echo '<div class="autodex-notice autodex-notice--error" role="alert">' . esc_html__( 'Molimo provjerite obavezna polja i pokušajte ponovo.', 'autodex' ) . '</div>';
	}
}

/**
 * Contact form markup.
 *
 * @return string
 */
function autodex_contact_form() {
	$first_name = '';
	$last_name  = '';
	$email      = '';
	$phone      = '';
	$message    = '';

	ob_start();
	autodex_contact_notice();
	?>
	<form class="autodex-contact-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="autodex_contact">
		<?php wp_nonce_field( 'autodex_contact_form', 'autodex_contact_nonce' ); ?>
		<input type="hidden" name="autodex_time" value="<?php echo esc_attr( time() ); ?>">
		<div class="autodex-hp" aria-hidden="true">
			<label for="autodex_hp">Website</label>
			<input type="text" id="autodex_hp" name="autodex_hp" value="" tabindex="-1" autocomplete="off">
		</div>

		<fieldset class="autodex-form-row autodex-form-row--two">
			<legend>Ime i prezime <span class="required">*</span></legend>
			<p>
				<label class="screen-reader-text" for="autodex_first_name">Ime</label>
				<input type="text" id="autodex_first_name" name="autodex_first_name" value="<?php echo esc_attr( $first_name ); ?>" placeholder="Ime" required>
			</p>
			<p>
				<label class="screen-reader-text" for="autodex_last_name">Prezime</label>
				<input type="text" id="autodex_last_name" name="autodex_last_name" value="<?php echo esc_attr( $last_name ); ?>" placeholder="Prezime" required>
			</p>
		</fieldset>

		<p class="autodex-form-row">
			<label for="autodex_email">Email adresa <span class="required">*</span></label>
			<input type="email" id="autodex_email" name="autodex_email" value="<?php echo esc_attr( $email ); ?>" placeholder="Email adresa" required>
		</p>

		<p class="autodex-form-row">
			<label for="autodex_phone">Broj Telefona <span class="required">*</span></label>
			<input type="tel" id="autodex_phone" name="autodex_phone" value="<?php echo esc_attr( $phone ); ?>" placeholder="Broj Telefona" required>
		</p>

		<p class="autodex-form-row">
			<label for="autodex_message">Poruka <span class="required">*</span></label>
			<textarea id="autodex_message" name="autodex_message" rows="8" placeholder="" required><?php echo esc_textarea( $message ); ?></textarea>
		</p>

		<p class="autodex-form-submit">
			<button type="submit" class="autodex-button">Pošalji</button>
		</p>
	</form>
	<?php
	return ob_get_clean();
}
