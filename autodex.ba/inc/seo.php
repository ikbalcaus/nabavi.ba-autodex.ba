<?php
/**
 * SEO titles, meta descriptions and robots directives.
 *
 * @package Autodex
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is a known SEO plugin already managing titles and descriptions?
 *
 * @return bool
 */
function autodex_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' );
}

/**
 * Site name with a safe theme fallback.
 *
 * @return string
 */
function autodex_seo_site_name() {
	$name = get_bloginfo( 'name' );

	return $name ? $name : 'Autodex.ba';
}

/**
 * Improve the document title for key pages.
 *
 * @param array $parts Title parts.
 * @return array
 */
function autodex_seo_title_parts( $parts ) {
	if ( autodex_seo_plugin_active() ) {
		return $parts;
	}

	$site = autodex_seo_site_name();

	if ( is_front_page() ) {
		$parts['title']   = 'Auto dijelovi, motorna ulja i servis';
		$parts['tagline'] = '';
		$parts['site']    = $site;
	} elseif ( is_home() ) {
		$parts['title']   = 'Novosti';
		$parts['tagline'] = '';
		$parts['site']    = $site;
	} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
		$parts['title'] = 'Shop auto dijelova i ulja';
		$parts['site']  = $site;
	} elseif ( is_search() ) {
		$parts['title'] = sprintf( 'Pretraga: %s', get_search_query() );
		$parts['site']  = $site;
	} elseif ( is_404() ) {
		$parts['title'] = 'Stranica nije pronađena';
		$parts['site']  = $site;
	}

	return $parts;
}
add_filter( 'document_title_parts', 'autodex_seo_title_parts' );

/**
 * Trim a description to a good meta length.
 *
 * @param string $text   Description.
 * @param int    $length Maximum length.
 * @return string
 */
function autodex_seo_trim( $text, $length = 160 ) {
	$text = wp_strip_all_tags( strip_shortcodes( (string) $text ) );
	$text = preg_replace( '/\s+/', ' ', $text );
	$text = trim( $text );

	if ( '' === $text ) {
		return '';
	}

	if ( function_exists( 'mb_strlen' ) && mb_strlen( $text ) > $length ) {
		return mb_substr( $text, 0, $length - 1 ) . '…';
	}

	if ( strlen( $text ) > $length ) {
		return substr( $text, 0, $length - 1 ) . '…';
	}

	return $text;
}

/**
 * Build the meta description for the current request.
 *
 * @return string
 */
function autodex_seo_meta_description() {
	if ( autodex_seo_plugin_active() ) {
		return '';
	}

	$description = '';

	if ( is_front_page() ) {
		$description = 'Autodex.ba — auto dijelovi, motorna ulja, veliki i mali servis te reparacija turbina. Provjeren kvalitet, stručna podrška i brza dostava.';
	} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
		$description = 'Pregledajte auto dijelove, motorna ulja i opremu u Autodex.ba online shopu. Provjeren kvalitet, stručna podrška i brza dostava.';
	} elseif ( function_exists( 'is_product' ) && is_product() ) {
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( get_queried_object_id() ) : null;

		if ( $product ) {
			$description = $product->get_short_description();

			if ( '' === trim( wp_strip_all_tags( $description ) ) ) {
				$description = $product->get_description();
			}

			if ( '' === trim( wp_strip_all_tags( $description ) ) ) {
				$description = $product->get_name() . ' — kupite online u Autodex.ba. Provjeren kvalitet, stručna podrška i brza dostava.';
			}
		}
	} elseif ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$description = term_description( get_queried_object_id(), 'product_cat' );

		if ( '' === trim( wp_strip_all_tags( $description ) ) ) {
			$description = single_term_title( '', false ) . ' — pogledajte ponudu auto dijelova i opreme u Autodex.ba shopu. Brza dostava i provjeren kvalitet.';
		}
	} elseif ( function_exists( 'is_product_tag' ) && is_product_tag() ) {
		$description = term_description( get_queried_object_id(), 'product_tag' );

		if ( '' === trim( wp_strip_all_tags( $description ) ) ) {
			$description = single_term_title( '', false ) . ' — pogledajte proizvode u Autodex.ba shopu.';
		}
	} elseif ( is_tax() || is_category() || is_tag() ) {
		$description = term_description();

		if ( '' === trim( wp_strip_all_tags( $description ) ) ) {
			$description = single_term_title( '', false ) . ' — pogledajte ponudu u Autodex.ba shopu. Provjeren kvalitet i brza dostava.';
		}
	} elseif ( is_page( 'veliki-servis' ) ) {
		$description = 'Veliki servis u Autodex.ba: zamjena zupčastog kaiša, filtera, svjećica i potrošnih dijelova. Pošaljite broj šasije i zatražite ponudu.';
	} elseif ( is_page( 'mali-servis' ) ) {
		$description = 'Mali servis u Autodex.ba: brza zamjena motornog ulja i filtera, osnovna provjera vozila i stručna podrška.';
	} elseif ( is_page( 'reparacija-turbina' ) ) {
		$description = 'Reparacija turbina u Autodex.ba: stručna provjera i reparacija turbina uz garanciju na urađeni rad. Pošaljite upit.';
	} elseif ( is_page( 'posaljite-upit-za-vase-vozilo' ) ) {
		$description = 'Pošaljite broj šasije i broj telefona — Autodex.ba pronalazi tačan dio za vaše vozilo i šalje vam ponudu.';
	} elseif ( is_page( 'kontakt' ) ) {
		$description = 'Kontaktirajte Autodex.ba — telefon ' . autodex_get_phone() . ', email ' . autodex_get_email() . '. Tu smo za pitanja o dijelovima, uljima i servisima.';
	} elseif ( is_page() || is_singular() ) {
		$post_id = get_queried_object_id();
		$excerpt = get_post_field( 'post_excerpt', $post_id );

		if ( '' === trim( $excerpt ) ) {
			$excerpt = get_post_field( 'post_content', $post_id );
		}

		$description = $excerpt;
	} elseif ( is_search() ) {
		$description = 'Rezultati pretrage za „' . get_search_query() . '“ — Autodex.ba. Pronađite auto dijelove, ulja i opremu.';
	} elseif ( is_404() ) {
		$description = 'Stranica nije pronađena. Vratite se na Autodex.ba početnu stranicu ili pregledajte shop auto dijelova i ulja.';
	}

	if ( '' === trim( (string) $description ) ) {
		$description = get_bloginfo( 'description' );
	}

	if ( '' === trim( (string) $description ) ) {
		$description = 'Autodex.ba — auto dijelovi, motorna ulja i servisna rješenja. Provjeren kvalitet, stručna podrška i brza dostava.';
	}

	return autodex_seo_trim( $description );
}

/**
 * Print the meta description.
 */
function autodex_seo_meta_tag() {
	if ( is_admin() || is_feed() ) {
		return;
	}

	$description = autodex_seo_meta_description();

	if ( '' !== $description ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
	}
}
add_action( 'wp_head', 'autodex_seo_meta_tag', 1 );

/**
 * Keep search, 404 and account utility pages out of the index.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function autodex_seo_robots( $robots ) {
	if ( autodex_seo_plugin_active() ) {
		return $robots;
	}

	$noindex = is_search() || is_404();

	if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) {
		$noindex = true;
	}

	if ( $noindex ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}

	return $robots;
}
add_filter( 'wp_robots', 'autodex_seo_robots' );

/**
 * Return a term URL, or an empty string on error.
 *
 * @param WP_Term $term Term.
 * @return string
 */
function autodex_seo_term_url( $term ) {
	if ( ! $term instanceof WP_Term ) {
		return '';
	}

	$link = get_term_link( $term );

	return is_wp_error( $link ) ? '' : $link;
}

/**
 * Organization schema.
 *
 * @return array
 */
function autodex_seo_organization_schema() {
	$url = home_url( '/' );

	return array(
		'@type'       => 'Organization',
		'@id'         => $url . '#organization',
		'name'        => autodex_seo_site_name(),
		'url'         => $url,
		'logo'        => array(
			'@type' => 'ImageObject',
			'url'   => autodex_image_url( 'logo.webp' ),
		),
		'description' => 'Auto dijelovi, motorna ulja, veliki i mali servis te reparacija turbina.',
		'contactPoint' => array(
			'@type'             => 'ContactPoint',
			'telephone'         => autodex_get_phone_href(),
			'email'             => autodex_get_email(),
			'contactType'       => 'customer service',
			'areaServed'        => 'BA',
			'availableLanguage' => array( 'bs', 'en' ),
		),
	);
}

/**
 * WebSite schema with the product search action.
 *
 * @return array
 */
function autodex_seo_website_schema() {
	$url = home_url( '/' );

	return array(
		'@type'      => 'WebSite',
		'@id'        => $url . '#website',
		'url'        => $url,
		'name'       => autodex_seo_site_name(),
		'inLanguage' => 'bs-BA',
		'publisher'  => array( '@id' => $url . '#organization' ),
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => array(
				'@type'       => 'EntryPoint',
				'urlTemplate' => $url . '?s={search_term_string}&post_type=product',
			),
			'query-input' => 'required name=search_term_string',
		),
	);
}

/**
 * BreadcrumbList schema for the current request.
 *
 * @return array|null
 */
function autodex_seo_breadcrumb_schema() {
	if ( is_front_page() || is_search() || is_404() ) {
		return null;
	}

	$items = array(
		array(
			'name' => 'Početna',
			'url'  => home_url( '/' ),
		),
	);

	$brand_taxonomy = autodex_get_brand_taxonomy();
	$is_brand       = $brand_taxonomy && taxonomy_exists( $brand_taxonomy ) && is_tax( $brand_taxonomy );
	$is_shop        = function_exists( 'is_shop' ) && ( is_shop() || is_product_category() || is_product_tag() || is_product() || $is_brand );

	if ( $is_shop ) {
		$items[] = array(
			'name' => 'Shop',
			'url'  => autodex_get_shop_url(),
		);
	}

	if ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$term = get_queried_object();

		if ( $term instanceof WP_Term ) {
			$ancestors = array_reverse( get_ancestors( $term->term_id, 'product_cat' ) );

			foreach ( $ancestors as $ancestor_id ) {
				$ancestor = get_term( $ancestor_id, 'product_cat' );

				if ( $ancestor && ! is_wp_error( $ancestor ) ) {
					$items[] = array(
						'name' => $ancestor->name,
						'url'  => autodex_seo_term_url( $ancestor ),
					);
				}
			}

			$items[] = array(
				'name' => $term->name,
				'url'  => autodex_seo_term_url( $term ),
			);
		}
	} elseif ( function_exists( 'is_product_tag' ) && is_product_tag() ) {
		$term = get_queried_object();

		if ( $term instanceof WP_Term ) {
			$items[] = array(
				'name' => $term->name,
				'url'  => autodex_seo_term_url( $term ),
			);
		}
	} elseif ( $is_brand ) {
		$term = get_queried_object();

		if ( $term instanceof WP_Term ) {
			$items[] = array(
				'name' => $term->name,
				'url'  => autodex_seo_term_url( $term ),
			);
		}
	} elseif ( function_exists( 'is_product' ) && is_product() ) {
		$product_id = get_queried_object_id();
		$terms      = get_the_terms( $product_id, 'product_cat' );

		if ( is_array( $terms ) && ! empty( $terms ) ) {
			$term      = reset( $terms );
			$ancestors = array_reverse( get_ancestors( $term->term_id, 'product_cat' ) );

			foreach ( $ancestors as $ancestor_id ) {
				$ancestor = get_term( $ancestor_id, 'product_cat' );

				if ( $ancestor && ! is_wp_error( $ancestor ) ) {
					$items[] = array(
						'name' => $ancestor->name,
						'url'  => autodex_seo_term_url( $ancestor ),
					);
				}
			}

			$items[] = array(
				'name' => $term->name,
				'url'  => autodex_seo_term_url( $term ),
			);
		}

		$items[] = array(
			'name' => get_the_title( $product_id ),
			'url'  => get_permalink( $product_id ),
		);
	} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
		// The Shop item is already in the trail.
	} elseif ( is_page() ) {
		$post_id   = get_queried_object_id();
		$ancestors = array_reverse( get_post_ancestors( $post_id ) );

		foreach ( $ancestors as $ancestor_id ) {
			$items[] = array(
				'name' => get_the_title( $ancestor_id ),
				'url'  => get_permalink( $ancestor_id ),
			);
		}

		$items[] = array(
			'name' => get_the_title( $post_id ),
			'url'  => get_permalink( $post_id ),
		);
	} elseif ( is_singular() ) {
		$items[] = array(
			'name' => get_the_title(),
			'url'  => get_permalink(),
		);
	} elseif ( is_archive() ) {
		$items[] = array(
			'name' => wp_strip_all_tags( get_the_archive_title() ),
			'url'  => '',
		);
	} else {
		return null;
	}

	$list     = array();
	$position = 1;

	foreach ( $items as $item ) {
		if ( empty( $item['name'] ) ) {
			continue;
		}

		$node = array(
			'@type'    => 'ListItem',
			'position' => $position,
			'name'     => $item['name'],
		);

		if ( ! empty( $item['url'] ) ) {
			$node['item'] = $item['url'];
		}

		$list[] = $node;
		$position++;
	}

	if ( count( $list ) < 2 ) {
		return null;
	}

	return array(
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $list,
	);
}

/**
 * Product schema for single products.
 *
 * @return array|null
 */
function autodex_seo_product_schema() {
	if ( ! function_exists( 'is_product' ) || ! is_product() || ! function_exists( 'wc_get_product' ) ) {
		return null;
	}

	$product = wc_get_product( get_queried_object_id() );

	if ( ! $product ) {
		return null;
	}

	$url         = get_permalink( $product->get_id() );
	$description = $product->get_short_description();

	if ( '' === trim( wp_strip_all_tags( $description ) ) ) {
		$description = $product->get_description();
	}

	if ( '' === trim( wp_strip_all_tags( $description ) ) ) {
		$description = $product->get_name() . ' — kupite online u Autodex.ba.';
	}

	$images   = array();
	$image_id = $product->get_image_id();

	if ( $image_id ) {
		$image_url = wp_get_attachment_image_url( $image_id, 'full' );

		if ( $image_url ) {
			$images[] = $image_url;
		}
	}

	foreach ( $product->get_gallery_image_ids() as $gallery_id ) {
		$gallery_url = wp_get_attachment_image_url( $gallery_id, 'full' );

		if ( $gallery_url && ! in_array( $gallery_url, $images, true ) ) {
			$images[] = $gallery_url;
		}
	}

	if ( empty( $images ) ) {
		$images[] = autodex_image_url( 'placeholder.webp' );
	}

	$schema = array(
		'@type'         => 'Product',
		'@id'           => $url . '#product',
		'name'          => $product->get_name(),
		'url'           => $url,
		'description'   => autodex_seo_trim( $description, 300 ),
		'image'         => $images,
		'itemCondition' => 'https://schema.org/NewCondition',
	);

	if ( $product->get_sku() ) {
		$schema['sku'] = $product->get_sku();
	}

	$category = autodex_get_primary_product_category( $product->get_id() );

	if ( $category instanceof WP_Term ) {
		$schema['category'] = $category->name;
	}

	$brands = autodex_get_product_brands( $product->get_id() );

	if ( ! empty( $brands ) ) {
		$brand = reset( $brands );

		if ( $brand instanceof WP_Term ) {
			$schema['brand'] = array(
				'@type' => 'Brand',
				'name'  => $brand->name,
			);
		}
	}

	$availability = $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';
	$currency     = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'BAM';
	$seller       = array( '@id' => home_url( '/' ) . '#organization' );

	if ( $product->is_type( 'variable' ) ) {
		$prices = $product->get_variation_prices( true );
		$values = array_filter( array_map( 'floatval', (array) ( $prices['price'] ?? array() ) ) );

		if ( ! empty( $values ) ) {
			$schema['offers'] = array(
				'@type'         => 'AggregateOffer',
				'url'           => $url,
				'lowPrice'      => wc_format_decimal( min( $values ), wc_get_price_decimals() ),
				'highPrice'     => wc_format_decimal( max( $values ), wc_get_price_decimals() ),
				'priceCurrency' => $currency,
				'offerCount'    => count( $values ),
				'availability'  => $availability,
				'itemCondition' => 'https://schema.org/NewCondition',
				'seller'        => $seller,
			);
		}
	} else {
		$price = $product->get_price();

		if ( '' !== $price && is_numeric( $price ) ) {
			$schema['offers'] = array(
				'@type'         => 'Offer',
				'url'           => $url,
				'price'         => wc_format_decimal( $price, wc_get_price_decimals() ),
				'priceCurrency' => $currency,
				'availability'  => $availability,
				'itemCondition' => 'https://schema.org/NewCondition',
				'seller'        => $seller,
			);
		}
	}

	if ( $product->get_review_count() > 0 && $product->get_average_rating() > 0 ) {
		$schema['aggregateRating'] = array(
			'@type'       => 'AggregateRating',
			'ratingValue' => wc_format_decimal( $product->get_average_rating(), 1 ),
			'reviewCount' => (int) $product->get_review_count(),
		);
	}

	return $schema;
}

/**
 * Service schema for the workshop pages.
 *
 * @return array|null
 */
function autodex_seo_service_schema() {
	if ( ! is_page() ) {
		return null;
	}

	$services = array(
		'veliki-servis'      => array(
			'name'        => 'Veliki servis',
			'description' => 'Zamjena zupčastog kaiša, filtera, svjećica i potrošnih dijelova.',
		),
		'mali-servis'        => array(
			'name'        => 'Mali servis',
			'description' => 'Brza zamjena motornog ulja i filtera, osnovna provjera vozila.',
		),
		'reparacija-turbina' => array(
			'name'        => 'Reparacija turbina',
			'description' => 'Stručna provjera i reparacija turbina uz garanciju na urađeni rad.',
		),
	);

	$slug = get_post_field( 'post_name', get_queried_object_id() );

	if ( ! isset( $services[ $slug ] ) ) {
		return null;
	}

	return array(
		'@type'       => 'Service',
		'@id'         => get_permalink() . '#service',
		'name'        => $services[ $slug ]['name'],
		'serviceType' => $services[ $slug ]['name'],
		'description' => $services[ $slug ]['description'],
		'url'         => get_permalink(),
		'provider'    => array( '@id' => home_url( '/' ) . '#organization' ),
		'areaServed'  => array(
			'@type' => 'Country',
			'name'  => 'Bosna i Hercegovina',
		),
	);
}

/**
 * BlogPosting schema for single posts.
 *
 * @return array|null
 */
function autodex_seo_article_schema() {
	if ( ! is_singular( 'post' ) ) {
		return null;
	}

	$post_id = get_queried_object_id();
	$url     = get_permalink( $post_id );
	$image   = get_the_post_thumbnail_url( $post_id, 'full' );
	$author  = get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $post_id ) );

	$schema = array(
		'@type'            => 'BlogPosting',
		'@id'              => $url . '#article',
		'headline'         => get_the_title( $post_id ),
		'url'              => $url,
		'mainEntityOfPage' => array(
			'@type' => 'WebPage',
			'@id'   => $url,
		),
		'datePublished'    => get_the_date( 'c', $post_id ),
		'dateModified'     => get_the_modified_date( 'c', $post_id ),
		'description'      => autodex_seo_trim( get_the_excerpt( $post_id ), 300 ),
		'publisher'        => array( '@id' => home_url( '/' ) . '#organization' ),
	);

	if ( $image ) {
		$schema['image'] = $image;
	}

	if ( $author ) {
		$schema['author'] = array(
			'@type' => 'Person',
			'name'  => $author,
		);
	}

	return $schema;
}

/**
 * Output the JSON-LD graph.
 */
function autodex_seo_schema() {
	if ( is_admin() || is_feed() || autodex_seo_plugin_active() ) {
		return;
	}

	$graph = array(
		autodex_seo_organization_schema(),
		autodex_seo_website_schema(),
		autodex_seo_breadcrumb_schema(),
		autodex_seo_product_schema(),
		autodex_seo_service_schema(),
		autodex_seo_article_schema(),
	);

	$graph = array_values( array_filter( $graph ) );

	if ( empty( $graph ) ) {
		return;
	}

	$flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

	printf(
		'<script type="application/ld+json">%s</script>' . "\n",
		wp_json_encode(
			array(
				'@context' => 'https://schema.org',
				'@graph'   => $graph,
			),
			$flags
		)
	);
}
add_action( 'wp_head', 'autodex_seo_schema', 20 );
