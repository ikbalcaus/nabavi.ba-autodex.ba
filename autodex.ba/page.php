<?php
/**
 * Default page template.
 *
 * @package Autodex
 */

defined( 'ABSPATH' ) || exit;

get_header();

$is_woo_page = function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() );
?>
<div class="autodex-page-wrap">
	<div class="autodex-container">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'autodex-page' ); ?>>
				<?php if ( ! $is_woo_page ) : ?>
					<?php autodex_breadcrumbs( array( 'class' => 'autodex-breadcrumbs--page' ) ); ?>
					<h1 class="autodex-page__title"><?php the_title(); ?></h1>
				<?php endif; ?>

				<div class="autodex-prose">
					<?php
					the_content();

					wp_link_pages(
						array(
							'before' => '<div class="page-links">' . esc_html__( 'Stranice:', 'autodex' ),
							'after'  => '</div>',
						)
					);
					?>
				</div>
			</article>
			<?php

			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
		endwhile;
		?>
	</div>
</div>
<?php
get_footer();
