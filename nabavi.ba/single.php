<?php
/**
 * Single post template.
 *
 * @package Nabavi
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="nabavi-page-wrap">
	<div class="nabavi-container nabavi-container--narrow">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'nabavi-single-post' ); ?>>
				<?php nabavi_breadcrumbs( array( 'class' => 'nabavi-breadcrumbs--page' ) ); ?>
				<h1 class="nabavi-single-post__title"><?php the_title(); ?></h1>
				<div class="nabavi-single-post__meta">
					<span><?php echo esc_html( get_the_date() ); ?></span>
					<span><?php echo esc_html( get_the_author() ); ?></span>
				</div>

				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="nabavi-single-post__media"><?php the_post_thumbnail( 'large' ); ?></figure>
				<?php endif; ?>

				<div class="nabavi-prose"><?php the_content(); ?></div>

				<?php
				wp_link_pages(
					array(
						'before' => '<div class="page-links">' . esc_html__( 'Stranice:', 'nabavi' ),
						'after'  => '</div>',
					)
				);
				?>
			</article>

			<?php
			the_post_navigation(
				array(
					'prev_text' => '<span class="nav-label">' . esc_html__( 'Prethodno', 'nabavi' ) . '</span> %title',
					'next_text' => '<span class="nav-label">' . esc_html__( 'Sljedeće', 'nabavi' ) . '</span> %title',
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
