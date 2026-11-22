<?php
/**
 * Front page — exact replica of the original Elementor homepage.
 *
 * @package Nabavi
 */

defined( 'ABSPATH' ) || exit;

get_header();

$categories = nabavi_get_curated_categories();
$hero_path  = nabavi_image_path( 'hero.png' );
?>
<div class="nabavi-home">
	<section class="nabavi-home__hero" aria-label="<?php esc_attr_e( 'Istaknuta ponuda', 'nabavi' ); ?>">
		<div class="nabavi-container">
			<a class="nabavi-home__hero-link" href="<?php echo esc_url( nabavi_get_shop_url() ); ?>">
				<?php if ( file_exists( nabavi_image_path( 'hero-1200.webp' ) ) ) : ?>
					<picture>
						<source type="image/webp" srcset="<?php echo esc_url( nabavi_image_url( 'hero-800.webp' ) ); ?> 800w, <?php echo esc_url( nabavi_image_url( 'hero-1200.webp' ) ); ?> 1200w, <?php echo esc_url( nabavi_image_url( 'hero-1584.webp' ) ); ?> 1584w" sizes="(max-width: 1220px) 100vw, 1180px">
						<img
							class="nabavi-home__hero-image"
							src="<?php echo esc_url( nabavi_image_url( 'hero.png' ) ); ?>"
							alt="<?php esc_attr_e( 'Shopping nikad nije bio lakši — Nabavi.ba', 'nabavi' ); ?>"
							width="1584"
							height="396"
							fetchpriority="high"
							decoding="async"
						>
					</picture>
				<?php elseif ( file_exists( $hero_path ) ) : ?>
					<img
						class="nabavi-home__hero-image"
						src="<?php echo esc_url( nabavi_image_url( 'hero.png' ) ); ?>"
						alt="<?php esc_attr_e( 'Shopping nikad nije bio lakši — Nabavi.ba', 'nabavi' ); ?>"
						width="1584"
						height="396"
						fetchpriority="high"
						decoding="async"
					>
				<?php else : ?>
					<span class="nabavi-home__hero-fallback"><?php esc_html_e( 'Shopping nikad nije bio lakši', 'nabavi' ); ?></span>
				<?php endif; ?>
			</a>
		</div>
	</section>

	<?php if ( ! empty( $categories ) ) : ?>
		<section class="nabavi-home__categories" aria-labelledby="nabavi-categories-title">
			<div class="nabavi-container">
				<h1 id="nabavi-categories-title" class="screen-reader-text"><?php esc_html_e( 'Kategorije proizvoda', 'nabavi' ); ?></h1>
				<div class="nabavi-category-grid">
					<?php foreach ( $categories as $category ) : ?>
						<?php $is_external = ! empty( $category['url'] ) && false !== strpos( $category['url'], 'autodex.ba' ); ?>
						<a
							class="nabavi-category-card"
							href="<?php echo esc_url( $category['url'] ); ?>"
							<?php echo $is_external ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>
						>
							<span class="nabavi-category-card__media">
								<img
									src="<?php echo esc_url( nabavi_image_src( $category['image'], 640 ) ); ?>"
									srcset="<?php echo esc_attr( nabavi_image_srcset( $category['image'], 1600, array( 480, 640, 960 ) ) ); ?>"
									sizes="(max-width: 767px) 50vw, (max-width: 1024px) 33vw, 25vw"
									alt="<?php echo esc_attr( $category['label'] ); ?>"
									width="640"
									height="360"
									loading="lazy"
									decoding="async"
								>
							</span>
							<span class="nabavi-category-card__title"><?php echo esc_html( $category['label'] ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php
	$popular_ids = nabavi_get_popular_product_ids( 20 );

	if ( ! empty( $popular_ids ) ) :
		?>
		<section class="nabavi-home__popular" aria-labelledby="nabavi-popular-title">
			<div class="nabavi-container">
				<div class="nabavi-home__popular-inner">
					<h2 id="nabavi-popular-title" class="nabavi-home__section-title"><?php esc_html_e( 'Najpopularnije', 'nabavi' ); ?></h2>
					<?php echo nabavi_render_product_grid( $popular_ids, 5, 'nabavi-popular-grid' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		</section>
	<?php endif; ?>
</div>
<?php
get_footer();
