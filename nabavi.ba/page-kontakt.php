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
				<h1 class="screen-reader-text"><?php the_title(); ?></h1>

				<div class="nabavi-contact-layout">
					<div class="nabavi-contact-layout__form">
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
