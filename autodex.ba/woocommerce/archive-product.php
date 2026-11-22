<?php
/**
 * Product archive: shop, categories, tags, brands and search results.
 *
 * @package Autodex
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div id="page-wrapper" class="autodex-shop-wrap">
	<div id="shop-sidebar-overlay" class="sidebar-overlay"></div>

	<div class="custom-shop-wrapper autodex-shop">
		<aside id="custom-shop-sidebar" class="shop-sidebar" aria-label="<?php esc_attr_e( 'Filteri', 'autodex' ); ?>">
			<div class="sidebar-close-wrapper">
				<button id="close-mobile-filters" class="sidebar-close-btn" type="button" aria-label="<?php esc_attr_e( 'Zatvori filtere', 'autodex' ); ?>">&times;</button>
			</div>

			<?php autodex_breadcrumbs( array( 'class' => 'autodex-breadcrumbs--shop' ) ); ?>

			<?php
			autodex_render_price_filter();
			autodex_render_shop_categories();
			autodex_render_brand_filter();
			?>
		</aside>

		<div class="shop-products">
			<?php if ( function_exists( 'woocommerce_output_all_notices' ) ) : ?>
				<div class="autodex-shop-notices"><?php woocommerce_output_all_notices(); ?></div>
			<?php endif; ?>

			<?php if ( is_search() ) : ?>
				<p class="shop-search-summary">
					<?php
					printf(
						esc_html__( 'Rezultati pretrage za: %s', 'autodex' ),
						'<strong>' . esc_html( get_search_query() ) . '</strong>'
					);
					?>
				</p>
			<?php endif; ?>

			<div class="shop-top-bar">
				<div class="shop-sorting">
					<span class="sort-label"><?php esc_html_e( 'Sortiraj po:', 'autodex' ); ?></span>
					<?php woocommerce_catalog_ordering(); ?>
				</div>

				<div class="shop-pagination top">
					<?php autodex_pagination( null, 'top' ); ?>
				</div>
			</div>

			<div class="mobile-filter-trigger-wrapper">
				<button id="open-mobile-filters" class="mobile-filter-btn" type="button" aria-controls="custom-shop-sidebar" aria-expanded="false">
					<?php echo autodex_get_icon( 'filter' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php esc_html_e( 'Odaberite Filtere', 'autodex' ); ?>
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
					<?php autodex_pagination( null, 'bottom' ); ?>
				</div>
			<?php else : ?>
				<?php wc_get_template( 'loop/no-products-found.php' ); ?>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php
get_footer();
