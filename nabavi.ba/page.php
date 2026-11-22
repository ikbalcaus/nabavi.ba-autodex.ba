<?php
/**
 * Default page template.
 *
 * @package Nabavi
 */

defined( 'ABSPATH' ) || exit;

get_header();

$is_woo_page = function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() );
?>
<div class="nabavi-page-wrap">
	<div class="nabavi-container">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'nabavi-page' ); ?>>
				<?php nabavi_breadcrumbs( array( 'class' => 'nabavi-breadcrumbs--page' ) ); ?>
				<header class="nabavi-page-head">
					<h1 class="nabavi-page__title"><?php the_title(); ?></h1>
				</header>

				<div class="<?php echo $is_woo_page ? 'nabavi-woo-content' : 'nabavi-prose'; ?>">
					<?php
					if ( $is_woo_page ) {
						the_content();
					} else {
						the_content();

						wp_link_pages(
							array(
								'before' => '<div class="page-links">' . esc_html__( 'Stranice:', 'nabavi' ),
								'after'  => '</div>',
							)
						);
					}
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
