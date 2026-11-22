<?php
/**
 * Template Name: O nama
 *
 * @package Autodex
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="autodex-page-wrap">
	<div class="autodex-container">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'autodex-page autodex-about' ); ?>>
				<h1 class="screen-reader-text"><?php the_title(); ?></h1>

				<div class="autodex-prose">
					<?php if ( trim( get_the_content() ) ) : ?>
						<?php the_content(); ?>
					<?php else : ?>
						<p>Dobrodošli u <strong>Autodex.ba</strong>, vašu online destinaciju za auto dijelove, motorna ulja i servisna rješenja. Na jednom mjestu nudimo <strong>širok izbor dijelova za putnička i dostavna vozila</strong> — od motora, kvačila i kočionog sistema do rasvjete, karoserije, guma i autokozmetike.</p>

						<p>Uz prodaju dijelova, Autodex.ba nudi i <strong>servisne usluge</strong>: veliki i mali servis te reparaciju turbina. Ako niste sigurni koji dio odgovara vašem vozilu, pošaljite nam broj šasije i naš tim će provjeriti tačnu kompatibilnost te vam pripremiti ponudu.</p>

						<p>Vjerujemo da kupovina auto dijelova treba biti jednostavna i sigurna. Zato kombinujemo <strong>provjeren kvalitet, stručnu podršku i brzu dostavu</strong>, uz jasne informacije o svakom proizvodu i transparentne cijene.</p>

						<p>Bilo da održavate svoje vozilo ili tražite pouzdanog partnera za servis, <strong>Autodex.ba</strong> je mjesto gdje se kvalitetni dijelovi susreću s iskrenom uslugom. Hvala vam na povjerenju.</p>
					<?php endif; ?>
				</div>
			</article>
			<?php
		endwhile;
		?>
	</div>
</div>
<?php
get_footer();
