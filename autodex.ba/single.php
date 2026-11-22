<?php
/**
 * Single post template.
 *
 * @package Autodex
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="autodex-page-wrap">
	<div class="autodex-container autodex-container--narrow">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'autodex-single-post' ); ?>>
				<?php autodex_breadcrumbs( array( 'class' => 'autodex-breadcrumbs--page' ) ); ?>
				<h1 class="autodex-single-post__title"><?php the_title(); ?></h1>
				<div class="autodex-single-post__meta">
					<span><?php echo esc_html( get_the_date() ); ?></span>
					<span><?php echo esc_html( get_the_author() ); ?></span>
				</div>

				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="autodex-single-post__media"><?php the_post_thumbnail( 'large' ); ?></figure>
				<?php endif; ?>

				<div class="autodex-prose"><?php the_content(); ?></div>

				<?php
				wp_link_pages(
					array(
						'before' => '<div class="page-links">' . esc_html__( 'Stranice:', 'autodex' ),
						'after'  => '</div>',
					)
				);
				?>
			</article>

			<?php
			the_post_navigation(
				array(
					'prev_text' => '<span class="nav-label">' . esc_html__( 'Prethodno', 'autodex' ) . '</span> %title',
					'next_text' => '<span class="nav-label">' . esc_html__( 'Sljedeće', 'autodex' ) . '</span> %title',
				)
			);

			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
		endwhile;
		?>
	</div>
</div>
<?php
get_footer();
