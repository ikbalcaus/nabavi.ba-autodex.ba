<?php
/**
 * Template Name: Kontakt
 *
 * @package Nabavi
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="nabavi-page-wrap">
	<div class="nabavi-container">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'nabavi-page nabavi-contact-page' ); ?>>
				<header class="nabavi-page-head">
					<span class="nabavi-page-head__eyebrow"><?php esc_html_e( 'Kontakt', 'nabavi' ); ?></span>
					<h1 class="nabavi-page__title"><?php the_title(); ?></h1>
					<p class="nabavi-page-head__lead"><?php esc_html_e( 'Tu smo za sva vaša pitanja o proizvodima, dostavi i narudžbama.', 'nabavi' ); ?></p>
				</header>

				<div class="nabavi-contact-layout">
					<div class="nabavi-contact-layout__form">
						<div class="nabavi-contact-cards">
							<a class="nabavi-contact-card" href="tel:<?php echo esc_attr( nabavi_get_phone_href() ); ?>">
								<span class="nabavi-contact-card__icon"><?php echo nabavi_get_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<span class="nabavi-contact-card__text">
									<small><?php esc_html_e( 'Nazovite nas', 'nabavi' ); ?></small>
									<strong><?php echo esc_html( nabavi_get_phone() ); ?></strong>
								</span>
							</a>
							<a class="nabavi-contact-card" href="mailto:<?php echo esc_attr( nabavi_get_email() ); ?>">
								<span class="nabavi-contact-card__icon"><?php echo nabavi_get_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<span class="nabavi-contact-card__text">
									<small><?php esc_html_e( 'Pišite nam', 'nabavi' ); ?></small>
									<strong><?php echo esc_html( nabavi_get_email() ); ?></strong>
								</span>
							</a>
						</div>

						<?php
						if ( trim( get_the_content() ) ) {
							echo '<div class="nabavi-prose nabavi-contact-intro">';
							the_content();
							echo '</div>';
						}

						echo nabavi_contact_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</div>

					<div class="nabavi-contact-layout__map">
						<iframe
							title="<?php esc_attr_e( 'Sarajevo — lokacija', 'nabavi' ); ?>"
							src="https://maps.google.com/maps?q=Sarajevo&amp;t=m&amp;z=14&amp;output=embed&amp;iwloc=near"
							loading="lazy"
							referrerpolicy="no-referrer-when-downgrade"
							allowfullscreen
						></iframe>
					</div>
				</div>
			</article>
			<?php
		endwhile;
		?>
	</div>
</div>
<?php
get_footer();
