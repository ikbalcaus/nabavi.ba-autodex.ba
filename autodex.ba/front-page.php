<?php
/**
 * Front page — Autodex.ba homepage.
 *
 * @package Autodex
 */

defined( 'ABSPATH' ) || exit;

get_header();

$categories      = autodex_get_curated_categories();
$services        = autodex_get_services();
$hero_image      = autodex_image_path( 'hero-turbo.webp' );
$inquiry_url     = autodex_get_inquiry_url();
$shop_url        = autodex_get_shop_url();
$popular_ids     = autodex_get_popular_product_ids( 20 );
$how_it_works    = array(
	array(
		'title' => 'Pošalješ broj šasije',
		'text'  => 'Popuniš kratku formu i pošalješ nam upit za svoje vozilo.',
	),
	array(
		'title' => 'Mi pronađemo tačan dio',
		'text'  => 'Provjeravamo po broju šasije i nalazimo dio koji odgovara tvom vozilu.',
	),
	array(
		'title' => 'Dobijaš ponudu i cijenu',
		'text'  => 'Šaljemo ti ponudu i cijenu bez čekanja, a ti odlučuješ o kupovini.',
	),
);
?>
<div class="autodex-home">
	<section class="autodex-hero" aria-label="<?php esc_attr_e( 'Auto dijelovi i servis', 'autodex' ); ?>">
		<div class="autodex-hero__inner">
			<div class="autodex-hero__content">
				<p class="autodex-eyebrow autodex-eyebrow--light"><?php esc_html_e( 'Auto dijelovi · Ulja · Servis', 'autodex' ); ?></p>

				<h1 class="autodex-hero__title">
					<?php esc_html_e( 'Pravi dio za tvoje vozilo,', 'autodex' ); ?>
					<em><?php esc_html_e( 'bez čekanja.', 'autodex' ); ?></em>
				</h1>

				<p class="autodex-hero__text"><?php esc_html_e( 'Pošalji broj šasije i naš tim pronalazi tačan dio, provjerava dostupnost i šalje ti ponudu — brzo i jednostavno.', 'autodex' ); ?></p>

				<div class="autodex-hero__actions">
					<a class="autodex-button" href="<?php echo esc_url( $inquiry_url ); ?>">
						<?php esc_html_e( 'Pošaljite upit', 'autodex' ); ?>
						<?php echo autodex_get_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</a>
					<a class="autodex-hero__link" href="<?php echo esc_url( $shop_url ); ?>">
						<?php esc_html_e( 'Pregledaj katalog', 'autodex' ); ?>
						<?php echo autodex_get_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</a>
				</div>

				<ul class="autodex-hero__points">
					<li><?php echo autodex_get_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Provjeren kvalitet', 'autodex' ); ?></li>
					<li><?php echo autodex_get_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Stručna podrška', 'autodex' ); ?></li>
					<li><?php echo autodex_get_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Brza dostava', 'autodex' ); ?></li>
				</ul>
			</div>

			<div class="autodex-hero__media">
				<?php if ( file_exists( $hero_image ) ) : ?>
					<img
						class="autodex-hero__image"
						src="<?php echo esc_url( autodex_image_src( 'hero-turbo.webp', 1200 ) ); ?>"
						srcset="<?php echo esc_attr( autodex_image_srcset( 'hero-turbo.webp', 1600, array( 800, 1200 ) ) ); ?>"
						sizes="(max-width: 767px) 100vw, 45vw"
						alt="<?php esc_attr_e( 'Turbo punjač s garancijom — Autodex.ba', 'autodex' ); ?>"
						width="1200"
						height="675"
						fetchpriority="high"
						decoding="async"
					>
				<?php else : ?>
					<span class="autodex-hero__image-fallback"><?php esc_html_e( 'Autodex.ba', 'autodex' ); ?></span>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<?php if ( ! empty( $services ) ) : ?>
		<section class="autodex-services" aria-labelledby="autodex-services-title">
			<div class="autodex-container">
				<div class="autodex-section-head">
					<p class="autodex-eyebrow"><?php esc_html_e( 'Naše usluge', 'autodex' ); ?></p>
					<h2 id="autodex-services-title" class="autodex-section-title"><?php esc_html_e( 'Servis koji ide u korak s tvojim autom', 'autodex' ); ?></h2>
				</div>

				<div class="autodex-services__grid">
					<?php foreach ( $services as $service ) : ?>
						<a class="autodex-service-card" href="<?php echo esc_url( $service['url'] ); ?>">
							<span class="autodex-service-card__media">
								<img
									src="<?php echo esc_url( autodex_image_src( $service['image'], 640 ) ); ?>"
									srcset="<?php echo esc_attr( autodex_image_srcset( $service['image'], 1024, array( 480, 640 ) ) ); ?>"
									sizes="(max-width: 767px) 100vw, (max-width: 1024px) 33vw, 30vw"
									alt="<?php echo esc_attr( $service['label'] ); ?>"
									width="640"
									height="360"
									loading="lazy"
									decoding="async"
								>
							</span>
							<span class="autodex-service-card__body">
								<span class="autodex-service-card__title"><?php echo esc_html( $service['label'] ); ?></span>
								<span class="autodex-service-card__text"><?php echo esc_html( $service['description'] ); ?></span>
								<span class="autodex-service-card__more">
									<?php esc_html_e( 'Saznaj više', 'autodex' ); ?>
									<?php echo autodex_get_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</span>
							</span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $categories ) ) : ?>
		<section class="autodex-home__categories" aria-labelledby="autodex-categories-title">
			<div class="autodex-container">
				<div class="autodex-section-head autodex-section-head--between">
					<div>
						<p class="autodex-eyebrow"><?php esc_html_e( 'Katalog', 'autodex' ); ?></p>
						<h2 id="autodex-categories-title" class="autodex-section-title"><?php esc_html_e( 'Pronađi dio po kategoriji', 'autodex' ); ?></h2>
					</div>
					<a class="autodex-section-head__link" href="<?php echo esc_url( $shop_url ); ?>">
						<?php esc_html_e( 'Pogledaj sve', 'autodex' ); ?>
						<?php echo autodex_get_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</a>
				</div>

				<div class="autodex-category-grid">
					<?php foreach ( $categories as $category ) : ?>
						<a class="autodex-category-card" href="<?php echo esc_url( $category['url'] ); ?>">
							<span class="autodex-category-card__media">
								<img
									src="<?php echo esc_url( autodex_image_src( $category['image'], 640 ) ); ?>"
									srcset="<?php echo esc_attr( autodex_image_srcset( $category['image'], 1024, array( 480, 640 ) ) ); ?>"
									sizes="(max-width: 767px) 50vw, (max-width: 1024px) 33vw, 25vw"
									alt="<?php echo esc_attr( $category['label'] ); ?>"
									width="640"
									height="360"
									loading="lazy"
									decoding="async"
								>
							</span>
							<span class="autodex-category-card__body">
								<span class="autodex-category-card__title"><?php echo esc_html( $category['label'] ); ?></span>
								<span class="autodex-category-card__more"><?php echo autodex_get_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							</span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<section class="autodex-steps" aria-labelledby="autodex-steps-title">
		<div class="autodex-container autodex-steps__inner">
			<div class="autodex-steps__intro">
				<p class="autodex-eyebrow autodex-eyebrow--light"><?php esc_html_e( 'Jednostavno', 'autodex' ); ?></p>
				<h2 id="autodex-steps-title" class="autodex-steps__title"><?php esc_html_e( 'Kako funkcioniše?', 'autodex' ); ?></h2>
				<p class="autodex-steps__text"><?php esc_html_e( 'Tri koraka do tačnog dijela. Bez dugog traženja i bez nagađanja.', 'autodex' ); ?></p>
				<a class="autodex-button" href="<?php echo esc_url( $inquiry_url ); ?>">
					<?php esc_html_e( 'Pošalji broj šasije', 'autodex' ); ?>
					<?php echo autodex_get_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
			</div>

			<ol class="autodex-steps__list">
				<?php foreach ( $how_it_works as $index => $step ) : ?>
					<li class="autodex-step">
						<span class="autodex-step__num"><?php echo esc_html( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
						<h3 class="autodex-step__title"><?php echo esc_html( $step['title'] ); ?></h3>
						<p class="autodex-step__text"><?php echo esc_html( $step['text'] ); ?></p>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</section>

	<?php if ( ! empty( $popular_ids ) ) : ?>
		<section class="autodex-home__popular" aria-labelledby="autodex-popular-title">
			<div class="autodex-container">
				<div class="autodex-section-head autodex-section-head--between">
					<div>
						<p class="autodex-eyebrow"><?php esc_html_e( 'Izdvojeno', 'autodex' ); ?></p>
						<h2 id="autodex-popular-title" class="autodex-section-title"><?php esc_html_e( 'Najpopularnije', 'autodex' ); ?></h2>
					</div>
					<a class="autodex-section-head__link" href="<?php echo esc_url( $shop_url ); ?>">
						<?php esc_html_e( 'Svi proizvodi', 'autodex' ); ?>
						<?php echo autodex_get_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</a>
				</div>

				<?php echo autodex_render_product_grid( $popular_ids, 5, 'autodex-popular-grid' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</section>
	<?php endif; ?>
</div>
<?php
get_footer();
