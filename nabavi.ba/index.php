<?php
/**
 * Main fallback template.
 *
 * @package Nabavi
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="nabavi-page-wrap">
	<div class="nabavi-container">
		<?php if ( is_home() && ! is_front_page() ) : ?>
			<header class="nabavi-archive-header">
				<h1 class="nabavi-archive-header__title"><?php single_post_title(); ?></h1>
			</header>
		<?php endif; ?>

		<?php if ( have_posts() ) : ?>
			<div class="nabavi-post-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<article id="post-<?php the_ID(); ?>" <?php post_class( 'nabavi-post-card' ); ?>>
						<?php if ( has_post_thumbnail() ) : ?>
							<a class="nabavi-post-card__media" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
								<?php the_post_thumbnail( 'large' ); ?>
							</a>
						<?php endif; ?>
						<div class="nabavi-post-card__body">
							<h2 class="nabavi-post-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<div class="nabavi-post-card__excerpt"><?php the_excerpt(); ?></div>
							<a class="nabavi-post-card__more" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Pročitaj više', 'nabavi' ); ?></a>
						</div>
					</article>
					<?php
				endwhile;
				?>
			</div>

			<div class="nabavi-pagination">
				<?php nabavi_pagination( null, 'bottom' ); ?>
			</div>
		<?php else : ?>
			<div class="nabavi-empty">
				<p><?php esc_html_e( 'Nema objavljenog sadržaja.', 'nabavi' ); ?></p>
			</div>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
