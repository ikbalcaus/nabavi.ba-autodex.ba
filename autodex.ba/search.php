<?php
/**
 * Search fallback template.
 *
 * @package Autodex
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="autodex-page-wrap">
	<div class="autodex-container">
		<header class="autodex-archive-header">
			<h1 class="autodex-archive-header__title">
				<?php printf( esc_html__( 'Rezultati pretrage za: %s', 'autodex' ), '<span>' . esc_html( get_search_query() ) . '</span>' ); ?>
			</h1>
		</header>

		<?php if ( have_posts() ) : ?>
			<div class="autodex-post-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<article id="post-<?php the_ID(); ?>" <?php post_class( 'autodex-post-card' ); ?>>
						<div class="autodex-post-card__body">
							<h2 class="autodex-post-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<div class="autodex-post-card__excerpt"><?php the_excerpt(); ?></div>
						</div>
					</article>
					<?php
				endwhile;
				?>
			</div>
			<div class="autodex-pagination"><?php autodex_pagination( null, 'bottom' ); ?></div>
		<?php else : ?>
			<div class="autodex-empty">
				<p><?php esc_html_e( 'Nema rezultata. Pokušajte sa drugim pojmom.', 'autodex' ); ?></p>
				<?php get_search_form(); ?>
			</div>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
