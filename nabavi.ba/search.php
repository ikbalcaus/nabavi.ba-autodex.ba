<?php
/**
 * Search fallback template.
 *
 * @package Nabavi
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="nabavi-page-wrap">
	<div class="nabavi-container">
		<header class="nabavi-archive-header">
			<h1 class="nabavi-archive-header__title">
				<?php printf( esc_html__( 'Rezultati pretrage za: %s', 'nabavi' ), '<span>' . esc_html( get_search_query() ) . '</span>' ); ?>
			</h1>
		</header>

		<?php if ( have_posts() ) : ?>
			<div class="nabavi-post-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<article id="post-<?php the_ID(); ?>" <?php post_class( 'nabavi-post-card' ); ?>>
						<div class="nabavi-post-card__body">
							<h2 class="nabavi-post-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<div class="nabavi-post-card__excerpt"><?php the_excerpt(); ?></div>
						</div>
					</article>
					<?php
				endwhile;
				?>
			</div>
			<div class="nabavi-pagination"><?php nabavi_pagination( null, 'bottom' ); ?></div>
		<?php else : ?>
			<div class="nabavi-empty">
				<p><?php esc_html_e( 'Nema rezultata. Pokušajte sa drugim pojmom.', 'nabavi' ); ?></p>
				<?php get_search_form(); ?>
			</div>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
