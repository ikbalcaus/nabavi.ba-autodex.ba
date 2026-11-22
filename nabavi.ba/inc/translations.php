<?php
/**
 * Bosnian translations for WooCommerce strings that are not always covered by
 * the active translation files.
 *
 * @package Nabavi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Translation map.
 *
 * @return array
 */
function nabavi_get_translations() {
	static $translations = null;

	if ( null !== $translations ) {
		return $translations;
	}

	$translations = array(
		// Actions.
		'Add to cart'                                 => 'Dodaj u korpu',
		'Read more'                                   => 'Detalji',
		'Place order'                                 => 'Potvrdi kupovinu',
		'Update cart'                                 => 'Sačuvaj izmjenu',
		'Save changes'                                => 'Sačuvaj promjene',
		'Save address'                                => 'Sačuvaj adresu',
		'Reset password'                              => 'Resetuj lozinku',
		'View cart'                                   => 'Pogledaj korpu',
		'Proceed to checkout'                         => 'Nastavi sa kupovinom',
		'Browse products'                             => 'Pregledaj proizvode',
		'Continue shopping'                           => 'Nastavi kupovinu',
		'Apply coupon'                                => 'Primijeni kupon',

		// Navigation.
		'Home'                                        => 'Početna',
		'Log out'                                     => 'Odjavi se',
		'Log in'                                      => 'Prijavi se',
		'Login'                                       => 'Prijava',
		'Register'                                    => 'Registracija',
		'My account'                                  => 'Moj račun',
		'Orders'                                      => 'Narudžbe',
		'Downloads'                                   => 'Preuzimanja',
		'Addresses'                                   => 'Adrese',
		'Account details'                             => 'Detalji naloga',
		'Dashboard'                                   => 'Kontrolna tabla',

		// Form fields.
		'First name *'                                => 'Ime *',
		'Last name *'                                 => 'Prezime *',
		'Display name *'                              => 'Ime za prikaz *',
		'Email address *'                             => 'Email adresa *',
		'Password *'                                  => 'Lozinka *',
		'Username or email address *'                 => 'Korisničko ime ili email adresa *',
		'Username or email *'                         => 'Korisničko ime ili email *',
		'Company name (optional)'                     => 'Naziv firme (opcionalno)',
		'Country / Region *'                          => 'Država / Region *',
		'Country / Region'                            => 'Država / Region',
		'Select a country / region…'                  => 'Odaberite državu / region…',
		'Select a country / region&hellip;'           => 'Odaberite državu / region…',
		'optional'                                    => 'opcionalno',
		'Street address *'                            => 'Ulica i broj *',
		'Street address'                              => 'Ulica i broj',
		'House number and street name'                => 'Broj i naziv ulice',
		'Apartment, suite, unit, etc. (optional)'     => 'Stan, apartman, jedinica, itd. (opcionalno)',
		'Apartment, suite, unit, etc.'                => 'Stan, apartman, jedinica, itd.',
		'Postcode / ZIP *'                            => 'Poštanski broj *',
		'Postcode / ZIP'                              => 'Poštanski broj',
		'Town / City *'                               => 'Grad *',
		'Town / City'                                 => 'Grad',
		'Canton (optional)'                           => 'Kanton (opcionalno)',
		'Phone *'                                     => 'Telefon *',
		'Phone'                                       => 'Telefon',
		'(optional)'                                  => '(opcionalno)',
		'Remember me'                                 => 'Zapamti me',
		'Password'                                    => 'Lozinka',
		'Username or email'                           => 'Korisničko ime ili email',

		// Checkout.
		'Billing details'                             => 'Podaci za naplatu',
		'Billing address'                             => 'Adresa za naplatu',
		'Shipping address'                            => 'Adresa za dostavu',
		'Additional information'                      => 'Dodatne informacije',
		'Order notes (optional)'                      => 'Bilješke o narudžbi (opcionalno)',
		'Notes about your order, e.g. special notes for delivery.' => 'Bilješke o vašoj narudžbi, npr. posebne napomene za dostavu.',
		'Your order'                                  => 'Vaša narudžba',
		'Product'                                     => 'Proizvod',
		'Brand'                                       => 'Brend',
		'Description'                                 => 'Opis',
		'Reviews (%d)'                                => 'Recenzije (%d)',
		'Related products'                            => 'Povezani proizvodi',
		'Showing the single result'                   => 'Prikazan 1 proizvod',
		'Showing all %1$d result'                     => 'Prikazano %1$d proizvoda',
		'Showing %1$d&ndash;%2$d of %3$d result'      => 'Prikazano %1$d–%2$d od %3$d proizvoda',
		'Your email address will not be published. Required fields are marked *' => 'Vaša email adresa neće biti objavljena. Obavezna polja su označena *',
		'Reviews'                                     => 'Recenzije',
		'Customer reviews'                            => 'Recenzije kupaca',
		'There are no reviews yet.'                   => 'Još nema recenzija.',
		'Be the first to review &ldquo;%s&rdquo;'     => 'Budi prvi koji će recenzirati &ldquo;%s&rdquo;',
		'Your rating'                                 => 'Vaša ocjena',
		'Your review'                                 => 'Vaša recenzija',
		'Name'                                        => 'Ime',
		'Email'                                       => 'Email',
		'Submit'                                      => 'Pošalji',
		'Rate&hellip;'                                => 'Ocijeni&hellip;',
		'Rate…'                                       => 'Ocijeni…',
		'Perfect'                                     => 'Odlično',
		'Good'                                        => 'Dobar',
		'Average'                                     => 'Prosječan',
		'Not that bad'                                => 'Zadovoljavajući',
		'Very poor'                                   => 'Jako loš',
		'Save my name, email, and website in this browser for the next time I comment.' => 'Sačuvaj moje ime, email i web stranicu u ovom browseru za buduće komentare.',
		'You may also like&hellip;'                   => 'Možda će vam se svidjeti&hellip;',
		'Additional information'                      => 'Dodatne informacije',
		'Subtotal'                                    => 'Cijena proizvoda',
		'Total'                                       => 'Ukupno',
		'Cart totals'                                 => 'Ukupan račun',
		'Price'                                       => 'Cijena',
		'Quantity'                                    => 'Količina',
		'Required'                                    => 'Obavezno',
		'Shipping'                                    => 'Dostava',
		'Free shipping'                               => 'Besplatna dostava',
		'Cash on delivery'                            => 'Plaćanje pouzećem',
		'Pay with cash upon delivery.'                => 'Plati gotovinom prilikom dostave.',
		'Direct bank transfer'                        => 'Direktna bankovna transakcija',
		'Check payments'                              => 'Plaćanje čekovima',

		// Account.
		'Password change'                             => 'Promjena lozinke',
		'Current password (leave blank to leave unchanged)' => 'Trenutna lozinka (ostavite prazno ako ne mijenjate)',
		'New password (leave blank to leave unchanged)' => 'Nova lozinka (ostavite prazno ako ne mijenjate)',
		'Confirm new password'                        => 'Potvrdite novu lozinku',
		'Lost your password?'                         => 'Zaboravljena lozinka?',
		'Add billing address'                         => 'Dodaj adresu za naplatu',
		'Add shipping address'                        => 'Dodaj adresu za dostavu',
		'Address'                                     => 'Adresa',

		// Messages.
		'Your cart is currently empty.'               => 'Vaša korpa je prazna.',
		'%s has been added to your cart.'             => '%s je dodan u korpu.',
		'Please choose product options by visiting %s.' => 'Odaberite opcije proizvoda na %s.',
		'No products were found matching your selection.' => 'Žao nam je, trenutno nemamo proizvoda.',
		'No order has been made yet.'                 => 'Još nema napravljenih narudžbi.',
		'No downloads available yet.'                 => 'Još nema dostupnih preuzimanja.',
		'You have not set up this type of address yet.' => 'Još niste postavili ovu vrstu adrese.',
		'An item which is no longer available was removed from your cart.' => 'Proizvod koji ste odabrali trenutno nije dostupan.',
		'Password reset email has been sent.'         => 'Email za reset lozinke je poslan.',
		'A password reset email has been sent to the email address on file for your account, but may take several minutes to show up in your inbox. Please wait at least 10 minutes before attempting another reset.' => 'Email za reset lozinke je poslan na adresu povezanu sa vašim nalogom, ali može potrajati nekoliko minuta da stigne u inbox. Molimo sačekajte barem 10 minuta prije nego što pokušate ponovo.',
		'Lost your password? Please enter your username or email address. You will receive a link to create a new password via email.' => 'Zaboravili ste lozinku? Unesite vaše korisničko ime ili email adresu. Dobit ćete link za kreiranje nove lozinke putem email-a.',
		'This will be how your name will be displayed in the account section and in reviews' => 'Ovo će biti način na koji će se vaše ime prikazivati u sekciji naloga i u recenzijama.',
		'The following addresses will be used on the checkout page by default.' => 'Sljedeće adrese će se koristiti na stranici za naplatu po defaultu.',
		'Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our privacy policy.' => 'Vaši lični podaci će biti korišteni za obradu vaše narudžbe, podršku vašeg iskustva na ovom sajtu i u druge svrhe opisane u našoj politici privatnosti.',
		'Your personal data will be used to support your experience throughout this website, to manage access to your account, and for other purposes described in our privacy policy.' => 'Vaši lični podaci će biti korišteni za podršku vašeg iskustva na ovom sajtu, za upravljanje pristupom vašem nalogu i u druge svrhe opisane u našoj politici privatnosti.',
		'Sorry, it seems that there are no available payment methods. Please contact us if you require assistance or wish to make alternate arrangements.' => 'Nažalost, izgleda da trenutno nema dostupnih metoda plaćanja. Molimo kontaktirajte nas ako trebate pomoć ili želite dogovoriti alternativnu metodu.',
		'Your order has been received. Thank you!'     => 'Vaša narudžba je zaprimljena. Hvala!',
		'Thank you. Your order has been received.'    => 'Hvala. Vaša narudžba je zaprimljena.',
		'Order received'                              => 'Narudžba zaprimljena',
		'Order details'                               => 'Detalji narudžbe',
		'Order number:'                               => 'Broj narudžbe:',
		'Date:'                                       => 'Datum:',
		'Email:'                                      => 'Email:',
		'Total:'                                      => 'Ukupno:',
		'Payment method:'                             => 'Način plaćanja:',
		'Note:'                                       => 'Napomena:',
		'Billing address'                             => 'Adresa za naplatu',
		'Shipping address'                            => 'Adresa za dostavu',
		'Qty'                                         => 'Kol.',
	);

	$translations = apply_filters( 'nabavi_woocommerce_translations', $translations );

	return $translations;
}

/**
 * Translate WooCommerce strings.
 *
 * @param string $translated Translated text.
 * @param string $text       Original text.
 * @param string $domain     Text domain.
 * @return string
 */
function nabavi_filter_gettext( $translated, $text, $domain ) {
	if ( 'woocommerce' !== $domain && 'default' !== $domain ) {
		return $translated;
	}

	$translations = nabavi_get_translations();

	if ( isset( $translations[ $text ] ) ) {
		return $translations[ $text ];
	}

	return $translated;
}
add_filter( 'gettext', 'nabavi_filter_gettext', 20, 3 );
add_filter( 'gettext_with_context', 'nabavi_filter_gettext_with_context', 20, 4 );

/**
 * Translate contextual WooCommerce strings.
 *
 * @param string $translation Translated text.
 * @param string $text        Original text.
 * @param string $context     Context.
 * @param string $domain      Text domain.
 * @return string
 */
function nabavi_filter_gettext_with_context( $translation, $text, $context, $domain ) {
	if ( 'woocommerce' !== $domain && 'default' !== $domain ) {
		return $translation;
	}

	$translations = nabavi_get_translations();

	return isset( $translations[ $text ] ) ? $translations[ $text ] : $translation;
}

/**
 * Translate plural WooCommerce strings.
 *
 * @param string $translation Translated text.
 * @param string $single      Single form.
 * @param string $plural      Plural form.
 * @param int    $number      Number.
 * @param string $domain      Text domain.
 * @return string
 */
function nabavi_filter_ngettext( $translation, $single, $plural, $number, $domain ) {
	if ( 'woocommerce' !== $domain ) {
		return $translation;
	}

	$translations = nabavi_get_translations();

	if ( isset( $translations[ $single ] ) ) {
		return $translations[ $single ];
	}

	return $translation;
}
add_filter( 'ngettext', 'nabavi_filter_ngettext', 20, 5 );
