<?php
/**
 * Contact form (replaces the WPForms shortcode used on the original site).
 *
 * @package Nabavi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handle the contact form submission.
 */
function nabavi_handle_contact_form() {
	$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/kontakt/' );

	if ( ! isset( $_POST['nabavi_contact_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nabavi_contact_nonce'] ) ), 'nabavi_contact_form' ) ) {
		wp_safe_redirect( add_query_arg( 'nabavi_contact', 'error', $redirect ) );
		exit;
	}

	$honeypot = isset( $_POST['nabavi_hp'] ) ? sanitize_text_field( wp_unslash( $_POST['nabavi_hp'] ) ) : '';
	$time     = isset( $_POST['nabavi_time'] ) ? (int) $_POST['nabavi_time'] : 0;

	if ( '' !== $honeypot || ( $time && ( time() - $time ) < 2 ) ) {
		wp_safe_redirect( add_query_arg( 'nabavi_contact', 'error', $redirect ) );
		exit;
	}

	$first_name = isset( $_POST['nabavi_first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['nabavi_first_name'] ) ) : '';
	$last_name  = isset( $_POST['nabavi_last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['nabavi_last_name'] ) ) : '';
	$email      = isset( $_POST['nabavi_email'] ) ? sanitize_email( wp_unslash( $_POST['nabavi_email'] ) ) : '';
	$phone      = isset( $_POST['nabavi_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['nabavi_phone'] ) ) : '';
	$message    = isset( $_POST['nabavi_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['nabavi_message'] ) ) : '';

	if ( '' === $first_name || '' === $last_name || '' === $message || ! is_email( $email ) || '' === $phone ) {
		wp_safe_redirect( add_query_arg( 'nabavi_contact', 'error', $redirect ) );
		exit;
	}

	$recipient = apply_filters( 'nabavi_contact_email_recipient', nabavi_get_contact_recipient() );
	$subject   = apply_filters( 'nabavi_contact_email_subject', 'Nova poruka sa Nabavi.ba' );

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
			'nabavi_contact',
			$sent ? 'success' : 'error',
			$redirect
		)
	);
	exit;
}
add_action( 'admin_post_nabavi_contact', 'nabavi_handle_contact_form' );
add_action( 'admin_post_nopriv_nabavi_contact', 'nabavi_handle_contact_form' );

/**
 * Display a contact form notice.
 */
function nabavi_contact_notice() {
	if ( ! isset( $_GET['nabavi_contact'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	$status = sanitize_key( wp_unslash( $_GET['nabavi_contact'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( 'success' === $status ) {
		echo '<div class="nabavi-notice nabavi-notice--success" role="status">' . esc_html__( 'Vaša poruka je uspješno poslana. Odgovorit ćemo vam u najkraćem roku.', 'nabavi' ) . '</div>';
	} elseif ( 'error' === $status ) {
		echo '<div class="nabavi-notice nabavi-notice--error" role="alert">' . esc_html__( 'Molimo provjerite obavezna polja i pokušajte ponovo.', 'nabavi' ) . '</div>';
	}
}

/**
 * Contact form markup.
 *
 * @return string
 */
function nabavi_contact_form() {
	$first_name = '';
	$last_name  = '';
	$email      = '';
	$phone      = '';
	$message    = '';

	ob_start();
	nabavi_contact_notice();
	?>
	<form class="nabavi-contact-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="nabavi_contact">
		<?php wp_nonce_field( 'nabavi_contact_form', 'nabavi_contact_nonce' ); ?>
		<input type="hidden" name="nabavi_time" value="<?php echo esc_attr( time() ); ?>">
		<div class="nabavi-hp" aria-hidden="true">
			<label for="nabavi_hp">Website</label>
			<input type="text" id="nabavi_hp" name="nabavi_hp" value="" tabindex="-1" autocomplete="off">
		</div>

		<fieldset class="nabavi-form-row nabavi-form-row--two">
			<legend>Ime i prezime <span class="required">*</span></legend>
			<p>
				<label class="screen-reader-text" for="nabavi_first_name">Ime</label>
				<input type="text" id="nabavi_first_name" name="nabavi_first_name" value="<?php echo esc_attr( $first_name ); ?>" placeholder="Ime" required>
			</p>
			<p>
				<label class="screen-reader-text" for="nabavi_last_name">Prezime</label>
				<input type="text" id="nabavi_last_name" name="nabavi_last_name" value="<?php echo esc_attr( $last_name ); ?>" placeholder="Prezime" required>
			</p>
		</fieldset>

		<p class="nabavi-form-row">
			<label for="nabavi_email">Email adresa <span class="required">*</span></label>
			<input type="email" id="nabavi_email" name="nabavi_email" value="<?php echo esc_attr( $email ); ?>" placeholder="Email adresa" required>
		</p>

		<p class="nabavi-form-row">
			<label for="nabavi_phone">Broj Telefona <span class="required">*</span></label>
			<input type="tel" id="nabavi_phone" name="nabavi_phone" value="<?php echo esc_attr( $phone ); ?>" placeholder="Broj Telefona" required>
		</p>

		<p class="nabavi-form-row">
			<label for="nabavi_message">Poruka <span class="required">*</span></label>
			<textarea id="nabavi_message" name="nabavi_message" rows="8" placeholder="" required><?php echo esc_textarea( $message ); ?></textarea>
		</p>

		<p class="nabavi-form-submit">
			<button type="submit" class="nabavi-button">Pošalji</button>
		</p>
	</form>
	<?php
	return ob_get_clean();
}
