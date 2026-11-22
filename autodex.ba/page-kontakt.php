<?php
/**
 * Template Name: Kontakt
 *
 * @package Autodex
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="autodex-page-wrap">
	<div class="autodex-container">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'autodex-page autodex-contact-page' ); ?>>
				<h1 class="screen-reader-text"><?php the_title(); ?></h1>

				<div class="autodex-contact-layout">
					<div class="autodex-contact-layout__form">
						<?php
						if ( trim( get_the_content() ) ) {
							echo '<div class="autodex-prose autodex-contact-intro">';
							the_content();
							echo '</div>';
						}

						echo autodex_contact_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</div>

					<div class="autodex-contact-layout__map">
						<iframe
							title="<?php esc_attr_e( 'Sarajevo — lokacija', 'autodex' ); ?>"
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
