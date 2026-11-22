<?php
/**
 * Main fallback template.
 *
 * @package Autodex
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="autodex-page-wrap">
	<div class="autodex-container">
		<?php if ( is_home() && ! is_front_page() ) : ?>
			<header class="autodex-archive-header">
				<h1 class="autodex-archive-header__title"><?php single_post_title(); ?></h1>
			</header>
		<?php endif; ?>

		<?php if ( have_posts() ) : ?>
			<div class="autodex-post-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<article id="post-<?php the_ID(); ?>" <?php post_class( 'autodex-post-card' ); ?>>
						<?php if ( has_post_thumbnail() ) : ?>
							<a class="autodex-post-card__media" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
								<?php the_post_thumbnail( 'large' ); ?>
							</a>
						<?php endif; ?>
						<div class="autodex-post-card__body">
							<h2 class="autodex-post-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<div class="autodex-post-card__excerpt"><?php the_excerpt(); ?></div>
							<a class="autodex-post-card__more" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Pročitaj više', 'autodex' ); ?></a>
						</div>
					</article>
					<?php
				endwhile;
				?>
			</div>

			<div class="autodex-pagination">
				<?php autodex_pagination( null, 'bottom' ); ?>
			</div>
		<?php else : ?>
			<div class="autodex-empty">
				<p><?php esc_html_e( 'Nema objavljenog sadržaja.', 'autodex' ); ?></p>
			</div>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
