<?php
/**
 * Product archive: shop, categories, tags, brands and search results.
 *
 * @package Nabavi
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div id="page-wrapper" class="nabavi-shop-wrap">
	<div id="shop-sidebar-overlay" class="sidebar-overlay"></div>

	<div class="custom-shop-wrapper nabavi-shop">
		<aside id="custom-shop-sidebar" class="shop-sidebar" aria-label="<?php esc_attr_e( 'Filteri', 'nabavi' ); ?>">
			<div class="sidebar-close-wrapper">
				<button id="close-mobile-filters" class="sidebar-close-btn" type="button" aria-label="<?php esc_attr_e( 'Zatvori filtere', 'nabavi' ); ?>">&times;</button>
			</div>

			<?php nabavi_breadcrumbs( array( 'class' => 'nabavi-breadcrumbs--shop' ) ); ?>

			<?php
			nabavi_render_price_filter();
			nabavi_render_shop_categories();
			nabavi_render_brand_filter();
			?>
		</aside>

		<div class="shop-products">
			<?php if ( function_exists( 'woocommerce_output_all_notices' ) ) : ?>
				<div class="nabavi-shop-notices"><?php woocommerce_output_all_notices(); ?></div>
			<?php endif; ?>

			<?php if ( is_search() ) : ?>
				<p class="shop-search-summary">
					<?php
					printf(
						esc_html__( 'Rezultati pretrage za: %s', 'nabavi' ),
						'<strong>' . esc_html( get_search_query() ) . '</strong>'
					);
					?>
				</p>
			<?php endif; ?>

			<div class="shop-top-bar">
				<div class="shop-sorting">
					<span class="sort-label"><?php esc_html_e( 'Sortiraj po:', 'nabavi' ); ?></span>
					<?php woocommerce_catalog_ordering(); ?>
				</div>

				<div class="shop-pagination top">
					<?php nabavi_pagination( null, 'top' ); ?>
				</div>
			</div>

			<div class="mobile-filter-trigger-wrapper">
				<button id="open-mobile-filters" class="mobile-filter-btn" type="button" aria-controls="custom-shop-sidebar" aria-expanded="false">
					<?php echo nabavi_get_icon( 'filter' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php esc_html_e( 'Odaberite Filtere', 'nabavi' ); ?>
				</button>
			</div>

			<?php if ( woocommerce_product_loop() ) : ?>
				<?php
				woocommerce_product_loop_start();

				while ( have_posts() ) :
					the_post();
					wc_get_template_part( 'content', 'product' );
				endwhile;

				woocommerce_product_loop_end();
				?>

				<div id="bottom-pagination" class="shop-pagination bottom">
					<?php nabavi_pagination( null, 'bottom' ); ?>
				</div>
			<?php else : ?>
				<?php wc_get_template( 'loop/no-products-found.php' ); ?>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php
get_footer();
