<?php
/**
 * Service and vehicle inquiry pages: forms, product grids and page setup.
 *
 * @package Autodex
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Page definitions and setup
 * ---------------------------------------------------------------------- */

/**
 * Pages and default content used by the inquiry forms.
 *
 * @return array
 */
function autodex_inquiry_page_definitions() {
	return array(
		'veliki-servis'                  => array(
			'title'   => 'Veliki servis',
			'content' => '[autodex_inquiry_form service="veliki-servis"]' . "\n\n" . '[autodex_service_products category="veliki-servis"]',
		),
		'mali-servis'                    => array(
			'title'   => 'Mali servis',
			'content' => '[autodex_inquiry_form service="mali-servis"]' . "\n\n" . '[autodex_service_products category="mali-servis"]',
		),
		'reparacija-turbina'             => array(
			'title'   => 'Reparacija turbina',
			'content' => '[autodex_inquiry_form service="reparacija-turbina"]',
		),
		'posaljite-upit-za-vase-vozilo'  => array(
			'title'   => 'Pošaljite upit za vaše vozilo',
			'content' => '[autodex_inquiry_form service="vase-vozilo" show_title="false"]',
		),
	);
}

/**
 * Create, publish or update one inquiry page without destroying custom content.
 *
 * @param string $slug    Page slug.
 * @param string $title   Page title.
 * @param string $content Shortcode content.
 * @return int
 */
function autodex_ensure_inquiry_page( $slug, $title, $content ) {
	$statuses = array( 'publish', 'draft', 'pending', 'private', 'trash' );

	$marked = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => $statuses,
			'meta_key'       => '_autodex_inquiry_page', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $slug, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'posts_per_page' => 1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	$page_id = ! empty( $marked ) ? (int) $marked[0] : 0;

	if ( ! $page_id ) {
		$pages = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => $statuses,
				'name'           => $slug,
				'posts_per_page' => 1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		$page_id = ! empty( $pages ) ? (int) $pages[0] : 0;
	}

	if ( ! $page_id ) {
		$page_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => $content,
			)
		);

		if ( ! $page_id || is_wp_error( $page_id ) ) {
			return 0;
		}

		update_post_meta( $page_id, '_autodex_inquiry_page', $slug );

		return (int) $page_id;
	}

	$page = get_post( $page_id );

	if ( ! $page || 'page' !== $page->post_type ) {
		return 0;
	}

	if ( 'trash' === $page->post_status ) {
		wp_untrash_post( $page_id );
		$page = get_post( $page_id );
	}

	$update = array( 'ID' => $page_id );

	if ( 'publish' !== $page->post_status ) {
		$update['post_status'] = 'publish';
	}

	if ( '' === trim( $page->post_title ) ) {
		$update['post_title'] = $title;
	}

	$has_our_form = false !== strpos( $page->post_content, 'autodex_inquiry_form' );
	$old_markers  = array( 'wpforms', 'elementor', 'et_pb_', 'wp:woocommerce/', 'gform', 'contact-form-7', '[contact-form', '[products' );
	$has_old      = false;

	foreach ( $old_markers as $marker ) {
		if ( false !== stripos( $page->post_content, $marker ) ) {
			$has_old = true;
			break;
		}
	}

	if ( ! $has_our_form ) {
		if ( '' === trim( $page->post_content ) || $has_old ) {
			$update['post_content'] = $content;

			// Remove the old builder data so it cannot override the new content.
			delete_post_meta( $page_id, '_elementor_data' );
			delete_post_meta( $page_id, '_elementor_edit_mode' );
			delete_post_meta( $page_id, '_elementor_template_type' );
			delete_post_meta( $page_id, '_elementor_version' );
			delete_post_meta( $page_id, '_wp_page_template' );
		} else {
			// Keep custom text and append the new form/products below it.
			$update['post_content'] = $page->post_content . "\n\n" . $content;
		}
	}

	if ( count( $update ) > 1 ) {
		wp_update_post( $update );
	}

	update_post_meta( $page_id, '_autodex_inquiry_page', $slug );

	return $page_id;
}

/**
 * Ensure all service and inquiry pages exist and are published.
 */
function autodex_ensure_inquiry_pages() {
	foreach ( autodex_inquiry_page_definitions() as $slug => $definition ) {
		autodex_ensure_inquiry_page( $slug, $definition['title'], $definition['content'] );
	}
}

/**
 * Repair the inquiry pages if one is missing or unpublished. Runs every 12 hours.
 */
function autodex_maybe_ensure_inquiry_pages() {
	if ( wp_installing() || get_transient( 'autodex_inquiry_pages_ok' ) ) {
		return;
	}

	$valid = true;

	foreach ( autodex_inquiry_page_definitions() as $slug => $definition ) {
		$pages = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'meta_key'       => '_autodex_inquiry_page', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $slug, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		if ( empty( $pages ) ) {
			$pages = get_posts(
				array(
					'post_type'      => 'page',
					'post_status'    => 'publish',
					'name'           => $slug,
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'no_found_rows'  => true,
				)
			);
		}

		if ( empty( $pages ) ) {
			$valid = false;
			break;
		}

		$page = get_post( (int) $pages[0] );

		if ( ! $page || false === strpos( $page->post_content, 'autodex_inquiry_form' ) ) {
			$valid = false;
			break;
		}
	}

	if ( $valid ) {
		set_transient( 'autodex_inquiry_pages_ok', 1, 12 * HOUR_IN_SECONDS );
		return;
	}

	autodex_ensure_inquiry_pages();
	flush_rewrite_rules( false );
	set_transient( 'autodex_inquiry_pages_ok', 1, 12 * HOUR_IN_SECONDS );
}
add_action( 'init', 'autodex_maybe_ensure_inquiry_pages', 22 );

/* -------------------------------------------------------------------------
 * Inquiry form
 * ---------------------------------------------------------------------- */

/**
 * Return the label/title map for the inquiry services.
 *
 * @return array
 */
function autodex_inquiry_services() {
	return array(
		'veliki-servis'      => array(
			'label' => 'Veliki servis',
			'title' => 'Pošaljite upit za veliki servis',
		),
		'mali-servis'        => array(
			'label' => 'Mali servis',
			'title' => 'Pošaljite upit za mali servis',
		),
		'reparacija-turbina' => array(
			'label' => 'Reparacija turbina',
			'title' => 'Pošaljite upit za reparaciju turbine',
		),
		'vase-vozilo'        => array(
			'label' => 'Vaše vozilo',
			'title' => 'Pošaljite upit za vaše vozilo',
		),
	);
}

/**
 * Display the result notice after the inquiry form redirect.
 */
function autodex_inquiry_notice() {
	if ( ! isset( $_GET['autodex_inquiry'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	$status = sanitize_key( wp_unslash( $_GET['autodex_inquiry'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	$messages = array(
		'success' => array( 'success', 'Vaš upit je uspješno poslan. Kontaktirat ćemo vas u najkraćem roku.' ),
		'missing' => array( 'error', 'Molimo unesite broj telefona, broj šasije i poruku.' ),
		'file'    => array( 'error', 'Datoteka nije ispravna ili je prevelika. Dozvoljene su slike (JPG, PNG, WebP) i PDF do 5 MB.' ),
		'mail'    => array( 'error', 'Upit trenutno nije moguće poslati. Pokušajte ponovo ili nas pozovite.' ),
		'error'   => array( 'error', 'Došlo je do greške. Pokušajte ponovo.' ),
	);

	if ( ! isset( $messages[ $status ] ) ) {
		return;
	}

	$type = $messages[ $status ][0];
	$text = $messages[ $status ][1];

	printf(
		'<div class="autodex-notice autodex-notice--%1$s" role="%2$s">%3$s</div>',
		esc_attr( $type ),
		'success' === $type ? 'status' : 'alert',
		esc_html( $text )
	);
}

/**
 * Inquiry form shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function autodex_inquiry_form_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'service'    => 'vase-vozilo',
			'title'      => '',
			'show_title' => 'true',
		),
		$atts,
		'autodex_inquiry_form'
	);

	$services = autodex_inquiry_services();
	$service  = sanitize_key( $atts['service'] );

	if ( ! isset( $services[ $service ] ) ) {
		$service = 'vase-vozilo';
	}

	$data       = $services[ $service ];
	$title      = '' !== $atts['title'] ? sanitize_text_field( $atts['title'] ) : $data['title'];
	$show_title = ! in_array( strtolower( (string) $atts['show_title'] ), array( '0', 'false', 'no' ), true );
	$form_url   = get_permalink();

	ob_start();
	?>
	<section class="autodex-inquiry" aria-labelledby="autodex-inquiry-title">
		<div class="autodex-inquiry__head">
			<p class="autodex-inquiry__eyebrow"><?php esc_html_e( 'Upit', 'autodex' ); ?></p>
			<h2 id="autodex-inquiry-title" class="autodex-inquiry__title<?php echo $show_title ? '' : ' screen-reader-text'; ?>"><?php echo esc_html( $title ); ?></h2>
			<p class="autodex-inquiry__text"><?php esc_html_e( 'Popunite podatke ispod i poslat ćemo vam ponudu u najkraćem roku.', 'autodex' ); ?></p>
		</div>

		<?php autodex_inquiry_notice(); ?>

		<form class="autodex-inquiry__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
			<input type="hidden" name="action" value="autodex_inquiry">
			<input type="hidden" name="redirect_to" value="<?php echo esc_url( $form_url ); ?>">
			<input type="hidden" name="autodex_inquiry_service" value="<?php echo esc_attr( $service ); ?>">
			<input type="hidden" name="autodex_inquiry_time" value="<?php echo esc_attr( time() ); ?>">
			<?php wp_nonce_field( 'autodex_inquiry_form', 'autodex_inquiry_nonce' ); ?>

			<div class="autodex-hp" aria-hidden="true">
				<label for="autodex_inquiry_hp">Website</label>
				<input type="text" id="autodex_inquiry_hp" name="autodex_hp" value="" tabindex="-1" autocomplete="off">
			</div>

			<div class="autodex-inquiry__grid">
				<p class="autodex-inquiry__field">
					<label for="autodex_inquiry_phone"><?php esc_html_e( 'Unesite broj telefona', 'autodex' ); ?> <span class="autodex-required">*</span></label>
					<input type="tel" id="autodex_inquiry_phone" name="autodex_inquiry_phone" autocomplete="tel" placeholder="npr. 061 123 456" required>
				</p>

				<p class="autodex-inquiry__field">
					<label for="autodex_inquiry_vin"><?php esc_html_e( 'Unesite broj šasije', 'autodex' ); ?> <span class="autodex-required">*</span></label>
					<input type="text" id="autodex_inquiry_vin" name="autodex_inquiry_vin" maxlength="32" placeholder="npr. WVWZZZ..." required>
				</p>
			</div>

			<p class="autodex-inquiry__field">
				<label for="autodex_inquiry_file"><?php esc_html_e( 'Slika saobraćajne dozvole (opcionalno)', 'autodex' ); ?></label>
				<input type="file" id="autodex_inquiry_file" name="autodex_inquiry_file" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf">
				<span class="autodex-inquiry__hint"><?php esc_html_e( 'Dozvoljene su slike (JPG, PNG, WebP) ili PDF do 5 MB.', 'autodex' ); ?></span>
			</p>

			<p class="autodex-inquiry__field">
				<label for="autodex_inquiry_message"><?php esc_html_e( 'Poruka', 'autodex' ); ?> <span class="autodex-required">*</span></label>
				<textarea id="autodex_inquiry_message" name="autodex_inquiry_message" rows="6" required></textarea>
			</p>

			<p class="autodex-inquiry__submit">
				<button type="submit" class="autodex-button"><?php esc_html_e( 'Pošaljite upit', 'autodex' ); ?></button>
			</p>
		</form>
	</section>
	<?php
	return ob_get_clean();
}
add_shortcode( 'autodex_inquiry_form', 'autodex_inquiry_form_shortcode' );

/* -------------------------------------------------------------------------
 * Service category products
 * ---------------------------------------------------------------------- */

/**
 * Product grid for a service category.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function autodex_service_products_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'category' => '',
			'limit'    => 20,
		),
		$atts,
		'autodex_service_products'
	);

	if ( ! autodex_is_woocommerce_active() || ! taxonomy_exists( 'product_cat' ) ) {
		return '';
	}

	$category = sanitize_title( $atts['category'] );

	if ( '' === $category ) {
		return '';
	}

	$limit = max( 1, min( 40, (int) $atts['limit'] ) );

	$ids = autodex_cache_get(
		'service_products_' . $category . '_' . $limit,
		static function () use ( $category, $limit ) {
			$products = get_posts(
				array(
					'post_type'      => 'product',
					'post_status'    => 'publish',
					'posts_per_page' => $limit,
					'fields'         => 'ids',
					'no_found_rows'  => true,
					'orderby'        => 'menu_order',
					'order'          => 'ASC',
					'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						array(
							'taxonomy' => 'product_cat',
							'field'    => 'slug',
							'terms'    => $category,
						),
					),
				)
			);

			return array_map( 'intval', $products );
		},
		10 * MINUTE_IN_SECONDS
	);

	if ( empty( $ids ) ) {
		return '';
	}

	ob_start();
	?>
	<section class="autodex-service-products">
		<h2 class="autodex-service-products__title"><?php esc_html_e( 'Proizvodi', 'autodex' ); ?></h2>
		<?php echo autodex_render_product_grid( $ids, 5, 'autodex-service-products-grid' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php
		$term = get_term_by( 'slug', $category, 'product_cat' );

		if ( $term && ! is_wp_error( $term ) ) {
			$term_link = get_term_link( $term );

			if ( ! is_wp_error( $term_link ) ) {
				?>
				<p class="autodex-service-products__all">
					<a href="<?php echo esc_url( $term_link ); ?>"><?php esc_html_e( 'Pogledaj sve proizvode', 'autodex' ); ?> &rarr;</a>
				</p>
				<?php
			}
		}
		?>
	</section>
	<?php
	return ob_get_clean();
}
add_shortcode( 'autodex_service_products', 'autodex_service_products_shortcode' );

/* -------------------------------------------------------------------------
 * Submission handler
 * ---------------------------------------------------------------------- */

/**
 * Handle the inquiry form submission, including the optional registration image.
 */
function autodex_handle_inquiry() {
	$redirect = isset( $_POST['redirect_to'] ) ? wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ), home_url( '/' ) ) : home_url( '/' );

	$nonce = isset( $_POST['autodex_inquiry_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['autodex_inquiry_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'autodex_inquiry_form' ) ) {
		wp_safe_redirect( add_query_arg( 'autodex_inquiry', 'error', $redirect ) );
		exit;
	}

	$honeypot = isset( $_POST['autodex_hp'] ) ? sanitize_text_field( wp_unslash( $_POST['autodex_hp'] ) ) : '';
	$time     = isset( $_POST['autodex_inquiry_time'] ) ? (int) $_POST['autodex_inquiry_time'] : 0;

	if ( '' !== $honeypot || ( $time && ( time() - $time ) < 2 ) ) {
		// Pretend success for bots.
		wp_safe_redirect( add_query_arg( 'autodex_inquiry', 'success', $redirect ) );
		exit;
	}

	$services = autodex_inquiry_services();
	$service  = isset( $_POST['autodex_inquiry_service'] ) ? sanitize_key( wp_unslash( $_POST['autodex_inquiry_service'] ) ) : 'vase-vozilo';

	if ( ! isset( $services[ $service ] ) ) {
		$service = 'vase-vozilo';
	}

	$label   = $services[ $service ]['label'];
	$phone   = isset( $_POST['autodex_inquiry_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['autodex_inquiry_phone'] ) ) : '';
	$vin     = isset( $_POST['autodex_inquiry_vin'] ) ? sanitize_text_field( wp_unslash( $_POST['autodex_inquiry_vin'] ) ) : '';
	$message = isset( $_POST['autodex_inquiry_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['autodex_inquiry_message'] ) ) : '';

	if ( '' === $phone || '' === $vin || '' === $message ) {
		wp_safe_redirect( add_query_arg( 'autodex_inquiry', 'missing', $redirect ) );
		exit;
	}

	if ( strlen( $phone ) > 40 || strlen( $vin ) > 64 || strlen( $message ) > 5000 ) {
		wp_safe_redirect( add_query_arg( 'autodex_inquiry', 'error', $redirect ) );
		exit;
	}

	$attachment  = '';
	$upload_name = '';

	if ( isset( $_FILES['autodex_inquiry_file'] ) && is_array( $_FILES['autodex_inquiry_file'] ) && UPLOAD_ERR_NO_FILE !== (int) $_FILES['autodex_inquiry_file']['error'] ) {
		$file = $_FILES['autodex_inquiry_file'];

		if ( UPLOAD_ERR_OK !== (int) $file['error'] || ! isset( $file['size'] ) || $file['size'] > 5 * 1024 * 1024 ) {
			wp_safe_redirect( add_query_arg( 'autodex_inquiry', 'file', $redirect ) );
			exit;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';

		$upload = wp_handle_upload(
			$file,
			array(
				'test_form' => false,
				'mimes'     => array(
					'jpg|jpeg|jpe' => 'image/jpeg',
					'png'          => 'image/png',
					'webp'         => 'image/webp',
					'pdf'          => 'application/pdf',
				),
			)
		);

		if ( ! is_array( $upload ) || isset( $upload['error'] ) || empty( $upload['file'] ) ) {
			wp_safe_redirect( add_query_arg( 'autodex_inquiry', 'file', $redirect ) );
			exit;
		}

		$attachment  = $upload['file'];
		$upload_name = basename( $attachment );
	}

	$recipient = apply_filters( 'autodex_inquiry_email_recipient', autodex_get_contact_recipient() );
	$subject   = apply_filters( 'autodex_inquiry_email_subject', 'Novi upit: ' . $label );

	$body  = "Novi upit sa Autodex.ba\n\n";
	$body .= "Usluga: {$label}\n";
	$body .= "Telefon: {$phone}\n";
	$body .= "Broj šasije: {$vin}\n\n";
	$body .= "Poruka:\n{$message}\n\n";
	$body .= 'Stranica: ' . $redirect . "\n";

	if ( '' !== $upload_name ) {
		$body .= "Prilog: {$upload_name}\n";
	}

	$sent = wp_mail(
		$recipient,
		$subject,
		$body,
		array( 'Content-Type: text/plain; charset=UTF-8' ),
		'' !== $attachment ? array( $attachment ) : array()
	);

	if ( '' !== $attachment ) {
		wp_delete_file( $attachment );
	}

	wp_safe_redirect( add_query_arg( 'autodex_inquiry', $sent ? 'success' : 'mail', $redirect ) );
	exit;
}
add_action( 'admin_post_autodex_inquiry', 'autodex_handle_inquiry' );
add_action( 'admin_post_nopriv_autodex_inquiry', 'autodex_handle_inquiry' );
