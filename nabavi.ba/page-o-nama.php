<?php
/**
 * Template Name: O nama
 *
 * @package Nabavi
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="nabavi-page-wrap">
	<div class="nabavi-container">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'nabavi-page nabavi-about' ); ?>>
				<h1 class="screen-reader-text"><?php the_title(); ?></h1>

				<div class="nabavi-prose">
					<?php if ( trim( get_the_content() ) ) : ?>
						<?php the_content(); ?>
					<?php else : ?>
						<p>Dobrodošli u <strong>Nabavi.ba</strong>, vašeg pouzdanog partnera za online kupovinu svih kategorija proizvoda. Naša misija je da vam ponudimo <strong>sve što vam treba na jednom mjestu</strong>, uz jednostavno i sigurno iskustvo kupovine. Bez obzira da li tražite elektroniku, kućanske aparate, modne dodatke, proizvode za dom ili kancelariju, kod nas ćete uvijek pronaći širok izbor proizvoda koji zadovoljavaju najviše standarde kvalitete.</p>

						<p>U Nabavi.ba vjerujemo da kupovina treba biti jednostavna, brza i ugodna. Posvećeni smo profesionalnoj korisničkoj podršci, brzoj i pouzdanoj isporuci, te transparentnim informacijama o svakom proizvodu, kako bismo vam omogućili da donesete najbolje odluke prilikom kupovine.</p>

						<p>Naša vizija je da postanemo prva online destinacija za kupovinu u regiji, poznata po raznovrsnom asortimanu, kvaliteti i povjerenju naših kupaca. Bilo da kupujete prvi put ili ste naš stalni kupac, Nabavi.ba je mjesto gdje se kvalitetni proizvodi susreću s izvrsnom uslugom.</p>

						<p>Odaberite Nabavi.ba i doživite online kupovinu na profesionalan i pouzdan način — jer vaše zadovoljstvo nam je na prvom mjestu.</p>
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
