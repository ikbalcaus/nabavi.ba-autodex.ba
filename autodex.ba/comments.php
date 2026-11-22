<?php
/**
 * Comments template.
 *
 * @package Autodex
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}
?>
<div id="comments" class="autodex-comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="autodex-comments__title">
			<?php
			$count = get_comments_number();
			printf(
				esc_html( _n( '%s komentar', '%s komentara', $count, 'autodex' ) ),
				esc_html( number_format_i18n( $count ) )
			);
			?>
		</h2>

		<ol class="autodex-comments__list">
			<?php
			wp_list_comments(
				array(
					'style'      => 'ol',
					'short_ping' => true,
					'avatar_size' => 48,
				)
			);
			?>
		</ol>

		<?php the_comments_navigation(); ?>
	<?php endif; ?>

	<?php
	comment_form(
		array(
			'title_reply'        => esc_html__( 'Ostavi komentar', 'autodex' ),
			'label_submit'       => esc_html__( 'Pošalji', 'autodex' ),
			'comment_notes_before' => '',
			'comment_field'      => '<p class="comment-form-comment"><label for="comment">' . esc_html__( 'Komentar', 'autodex' ) . '</label><textarea id="comment" name="comment" cols="45" rows="6" required></textarea></p>',
		)
	);
	?>
</div>
