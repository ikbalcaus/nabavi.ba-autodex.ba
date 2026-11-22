<?php
/**
 * Template tags used by header, footer and page templates.
 *
 * @package Autodex
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Logo
 * ---------------------------------------------------------------------- */

/**
 * Site logo.
 *
 * @param string $variant header|footer.
 * @param bool   $echo    Whether to print.
 * @return string
 */
function autodex_site_logo( $variant = 'header', $echo = true ) {
	$variant = 'footer' === $variant ? 'footer' : 'header';
	$file    = 'logo.webp';

	$html  = '<div class="autodex-logo autodex-logo--' . esc_attr( $variant ) . '">';
	$html .= '<a class="autodex-logo__link" href="' . esc_url( home_url( '/' ) ) . '" rel="home" aria-label="' . esc_attr( get_bloginfo( 'name' ) ) . '">';

	if ( 'header' === $variant && has_custom_logo() ) {
		$logo_id  = get_theme_mod( 'custom_logo' );
		$logo_img = wp_get_attachment_image(
			$logo_id,
			'full',
			false,
			array(
				'class'         => 'autodex-logo__img',
				'alt'           => get_bloginfo( 'name' ),
				'loading'       => 'eager',
				'fetchpriority' => 'high',
			)
		);

		if ( $logo_img ) {
			$html .= $logo_img;
		}
	} else {
		$image_path = autodex_image_path( $file );

		if ( file_exists( $image_path ) ) {
			$html .= sprintf(
				'<img class="autodex-logo__img" src="%1$s" srcset="%2$s" sizes="165px" alt="%3$s" width="360" height="181" %4$s>',
				esc_url( autodex_image_src( $file, 360 ) ),
				esc_attr( autodex_image_srcset( $file, 577, array( 240, 360 ) ) ),
				esc_attr( get_bloginfo( 'name' ) ),
				'footer' === $variant ? 'loading="lazy"' : 'loading="eager" fetchpriority="high"'
			);
		} else {
			$html .= '<span class="autodex-logo__text">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
		}
	}

	$html .= '</a></div>';

	if ( $echo ) {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is escaped above.
	}

	return $html;
}

/* -------------------------------------------------------------------------
 * Navigation
 * ---------------------------------------------------------------------- */

/**
 * Primary navigation items used by the fallback menu.
 *
 * @return array
 */
function autodex_get_primary_nav_items() {
	$items = array(
		array(
			'label' => 'Reparacija turbina',
			'type'  => 'page',
			'slug'  => 'reparacija-turbina',
		),
		array(
			'label' => 'Veliki servis',
			'type'  => 'page',
			'slug'  => 'veliki-servis',
		),
		array(
			'label' => 'Mali servis',
			'type'  => 'page',
			'slug'  => 'mali-servis',
		),
		array(
			'label' => 'Revenol ulja',
			'type'  => 'product_cat',
			'slug'  => 'revenol-ulja',
		),
		array(
			'label' => 'Ostalo',
			'type'  => 'shop',
			'slug'  => '',
		),
	);

	return apply_filters( 'autodex_primary_nav_items', $items );
}

/**
 * URL of a static page, with a safe fallback.
 *
 * @param string $slug Page slug.
 * @return string
 */
function autodex_get_page_url( $slug ) {
	static $urls = array();

	if ( isset( $urls[ $slug ] ) ) {
		return $urls[ $slug ];
	}

	$page = get_page_by_path( $slug );

	if ( $page ) {
		return $urls[ $slug ] = get_permalink( $page );
	}

	return $urls[ $slug ] = home_url( '/' . $slug . '/' );
}

/**
 * Resolve a fallback navigation item to a URL.
 *
 * @param array $item Navigation item.
 * @return string
 */
function autodex_get_nav_item_url( $item ) {
	$type = isset( $item['type'] ) ? $item['type'] : 'product_cat';
	$slug = isset( $item['slug'] ) ? $item['slug'] : '';

	if ( 'shop' === $type ) {
		return autodex_get_shop_url();
	}

	if ( 'page' === $type ) {
		return autodex_get_page_url( $slug );
	}

	return autodex_get_category_url( $slug );
}

/**
 * Get the URL for a product category slug.
 *
 * @param string $slug Category slug.
 * @return string
 */
function autodex_get_category_url( $slug ) {
	static $urls = array();

	if ( isset( $urls[ $slug ] ) ) {
		return $urls[ $slug ];
	}

	if ( taxonomy_exists( 'product_cat' ) ) {
		$term = get_term_by( 'slug', $slug, 'product_cat' );

		if ( $term && ! is_wp_error( $term ) ) {
			$link = get_term_link( $term );

			if ( ! is_wp_error( $link ) ) {
				return $urls[ $slug ] = $link;
			}
		}
	}

	return $urls[ $slug ] = home_url( '/shop/' . $slug . '/' );
}

/**
 * Fallback primary menu.
 *
 * @param array $args wp_nav_menu arguments.
 */
function autodex_nav_fallback( $args = array() ) {
	$class = isset( $args['menu_class'] ) ? $args['menu_class'] : 'autodex-menu';
	$id    = isset( $args['menu_id'] ) ? $args['menu_id'] : 'autodex-menu-fallback';

	echo '<ul id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . '">';

	foreach ( autodex_get_primary_nav_items() as $item ) {
		$current = '';
		$type    = isset( $item['type'] ) ? $item['type'] : 'product_cat';

		if ( 'product_cat' === $type && taxonomy_exists( 'product_cat' ) ) {
			$term = get_term_by( 'slug', $item['slug'], 'product_cat' );

			if ( $term && ! is_wp_error( $term ) && is_tax( 'product_cat', $term->term_id ) ) {
				$current = ' current-menu-item';
			}
		} elseif ( 'page' === $type && is_page( $item['slug'] ) ) {
			$current = ' current-menu-item';
		}

		printf(
			'<li class="menu-item%1$s"><a href="%2$s">%3$s</a></li>',
			esc_attr( $current ),
			esc_url( autodex_get_nav_item_url( $item ) ),
			esc_html( $item['label'] )
		);
	}

	echo '</ul>';
}

/**
 * Header navigation.
 *
 * @param string $location primary|mobile.
 */
function autodex_header_nav( $location = 'primary' ) {
	$mobile = 'mobile' === $location;
	$args   = array(
		'theme_location' => 'primary',
		'container'      => false,
		'menu_class'     => $mobile ? 'autodex-mobile-menu' : 'autodex-menu',
		'menu_id'        => $mobile ? 'autodex-mobile-menu' : 'autodex-primary-menu',
		'fallback_cb'    => 'autodex_nav_fallback',
		'depth'          => 2,
	);

	wp_nav_menu( $args );
}

/* -------------------------------------------------------------------------
 * Header actions
 * ---------------------------------------------------------------------- */

/**
 * Header action links (language, account, cart, mobile toggle).
 */
function autodex_header_actions() {
	$account_label = is_user_logged_in() ? __( 'Moj račun', 'autodex' ) : __( 'Prijava', 'autodex' );
	$cart_count    = autodex_get_cart_count();
	?>
	<div class="autodex-header__actions">
		<?php if ( autodex_option( 'autodex_show_language', true ) && shortcode_exists( 'gtranslate' ) ) : ?>
			<div class="autodex-lang" aria-label="<?php esc_attr_e( 'Jezik', 'autodex' ); ?>">
				<?php echo do_shortcode( '[gtranslate]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- GTranslate markup. ?>
			</div>
		<?php endif; ?>

		<a class="autodex-header__account" href="<?php echo esc_url( autodex_get_account_url() ); ?>" aria-label="<?php echo esc_attr( $account_label ); ?>">
			<?php echo autodex_get_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span class="autodex-header__account-label"><?php echo esc_html( $account_label ); ?></span>
		</a>

		<span class="autodex-header__separator" aria-hidden="true"></span>

		<a class="autodex-header__cart" href="<?php echo esc_url( autodex_get_cart_url() ); ?>" aria-label="<?php esc_attr_e( 'Korpa', 'autodex' ); ?>">
			<?php echo autodex_get_icon( 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span class="autodex-cart-count"><?php echo esc_html( $cart_count ); ?></span>
		</a>

		<button class="autodex-menu-toggle" type="button" aria-expanded="false" aria-controls="autodex-mobile-nav" aria-label="<?php esc_attr_e( 'Otvori meni', 'autodex' ); ?>">
			<?php echo autodex_get_icon( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * Homepage data
 * ---------------------------------------------------------------------- */

/**
 * Build the curated homepage categories from the shop terms.
 *
 * @return array
 */
function autodex_build_curated_categories() {
	$categories = array(
		array(
			'slug'  => 'motor-i-dijelovi-motora',
			'label' => 'Motor i dijelovi motora',
			'image' => 'cat-motor.webp',
		),
		array(
			'slug'  => 'kvacilo-i-zamajac',
			'label' => 'Kvačilo i zamajac',
			'image' => 'cat-kvacilo.webp',
		),
		array(
			'slug'  => 'ovijes-i-upravljanje',
			'label' => 'Ovjes i upravljanje',
			'image' => 'cat-ovijes.webp',
		),
		array(
			'slug'  => 'kocioni-sistem',
			'label' => 'Kočioni sistem',
			'image' => 'cat-kocioni.webp',
		),
		array(
			'slug'  => 'elektronika-i-senzori',
			'label' => 'Elektronika i senzori',
			'image' => 'cat-elektronika.webp',
		),
		array(
			'slug'  => 'izduvni-sistem',
			'label' => 'Izduvni sistem',
			'image' => 'cat-izduvni.webp',
		),
		array(
			'slug'  => 'revenol-ulja',
			'label' => 'Revenol ulja',
			'image' => 'cat-revenol.webp',
		),
		array(
			'slug'  => 'manol-ulja',
			'label' => 'Manol ulja',
			'image' => 'cat-manol.webp',
		),
		array(
			'slug'  => 'ostala-ulja-i-mazivi',
			'label' => 'Ostala ulja i mazivi',
			'image' => 'cat-ostala-ulja.webp',
		),
		array(
			'slug'  => 'karoserija',
			'label' => 'Karoserija',
			'image' => 'cat-karoserija.webp',
		),
		array(
			'slug'  => 'rasvjeta',
			'label' => 'Rasvjeta',
			'image' => 'cat-rasvjeta.webp',
		),
		array(
			'slug'  => 'retrovizori',
			'label' => 'Retrovizori',
			'image' => 'cat-retrovizori.webp',
		),
		array(
			'slug'  => 'stakla-retrovizora',
			'label' => 'Stakla retrovizora',
			'image' => 'cat-stakla-retrovizora.webp',
		),
		array(
			'slug'  => 'gume',
			'label' => 'Gume',
			'image' => 'cat-gume.webp',
		),
		array(
			'slug'  => 'metlice-brisaca',
			'label' => 'Metlice brisača',
			'image' => 'cat-metlice-brisaca.webp',
		),
		array(
			'slug'  => 'patosnice',
			'label' => 'Patosnice',
			'image' => 'cat-patosnice.webp',
		),
		array(
			'slug'  => 'autokozmetika',
			'label' => 'Autokozmetika',
			'image' => 'cat-autokozmetika.webp',
		),
	);

	foreach ( $categories as $index => $category ) {
		$categories[ $index ]['image_url'] = autodex_image_url( $category['image'] );
		$categories[ $index ]['url']       = autodex_get_category_url( $category['slug'] );
	}

	return $categories;
}

/**
 * Curated homepage categories (cached for 12 hours; flushed with catalog changes).
 *
 * @return array
 */
function autodex_get_curated_categories() {
	$categories = autodex_cache_get( 'curated_categories', 'autodex_build_curated_categories', 12 * HOUR_IN_SECONDS );

	return apply_filters( 'autodex_curated_categories', $categories );
}

/**
 * Build the workshop services highlighted on the homepage.
 *
 * @return array
 */
function autodex_build_services() {
	$services = array(
		array(
			'label'       => 'Veliki servis',
			'description' => 'Zamjena zupčastog kaiša, filtera, svjećica i svih potrošnih dijelova.',
			'slug'        => 'veliki-servis',
			'image'       => 'cat-veliki-servis.webp',
		),
		array(
			'label'       => 'Mali servis',
			'description' => 'Brza zamjena ulja, uljnog filtera i osnovna provjera vozila.',
			'slug'        => 'mali-servis',
			'image'       => 'cat-mali-servis.webp',
		),
		array(
			'label'       => 'Reparacija turbina',
			'description' => 'Stručna reparacija i provjera turbina uz garanciju na urađeni rad.',
			'slug'        => 'reparacija-turbina',
			'image'       => 'cat-reparacija-turbina.webp',
		),
	);

	foreach ( $services as $index => $service ) {
		$services[ $index ]['image_url'] = autodex_image_url( $service['image'] );
		$services[ $index ]['url']       = autodex_get_page_url( $service['slug'] );
	}

	return $services;
}

/**
 * Workshop services highlighted on the homepage (cached for 12 hours).
 *
 * @return array
 */
function autodex_get_services() {
	$services = autodex_cache_get( 'homepage_services', 'autodex_build_services', 12 * HOUR_IN_SECONDS );

	return apply_filters( 'autodex_services', $services );
}

/**
 * Product IDs for the "Najpopularnije" section.
 *
 * @param int $limit Number of products.
 * @return array
 */
function autodex_get_popular_product_ids( $limit = 50 ) {
	if ( ! autodex_is_woocommerce_active() || ! post_type_exists( 'product' ) ) {
		return array();
	}

	$limit = max( 1, (int) $limit );

	return autodex_cache_get(
		'popular_product_ids_' . $limit,
		static function () use ( $limit ) {
			$viewed = get_posts(
				array(
					'post_type'              => 'product',
					'post_status'            => 'publish',
					'posts_per_page'         => $limit,
					'meta_key'               => 'sk_pregledi_proizvoda', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'orderby'                => 'meta_value_num',
					'order'                  => 'DESC',
					'fields'                 => 'ids',
					'no_found_rows'          => true,
					'ignore_sticky_posts'    => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);

			$viewed = array_map( 'intval', (array) $viewed );

			if ( count( $viewed ) >= $limit ) {
				return $viewed;
			}

			$needed = $limit - count( $viewed );

			$fillers = get_posts(
				array(
					'post_type'              => 'product',
					'post_status'            => 'publish',
					'posts_per_page'         => $needed,
					'post__not_in'           => $viewed,
					'orderby'                => 'rand',
					'fields'                 => 'ids',
					'no_found_rows'          => true,
					'ignore_sticky_posts'    => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);

			return array_values( array_unique( array_merge( $viewed, array_map( 'intval', (array) $fillers ) ) ) );
		},
		10 * MINUTE_IN_SECONDS
	);
}

/* -------------------------------------------------------------------------
 * Breadcrumbs
 * ---------------------------------------------------------------------- */

/**
 * Breadcrumb separator.
 *
 * @return string
 */
function autodex_breadcrumb_separator() {
	return '<span class="autodex-breadcrumbs__sep" aria-hidden="true">&rsaquo;</span>';
}

/**
 * Render a breadcrumb link.
 *
 * @param string $label Label.
 * @param string $url   URL.
 */
function autodex_breadcrumb_item_link( $label, $url ) {
	printf(
		'<a class="autodex-breadcrumbs__link" href="%1$s">%2$s</a>%3$s',
		esc_url( $url ),
		esc_html( $label ),
		autodex_breadcrumb_separator() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
}

/**
 * Breadcrumbs for shop, products, pages and archives.
 *
 * @param array $args Optional arguments.
 */
function autodex_breadcrumbs( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'class'      => '',
			'home_label' => __( 'Početna', 'autodex' ),
			'shop_label' => __( 'Shop', 'autodex' ),
			'echo'       => true,
		)
	);

	$items = array();
	$items[] = array(
		'label' => $args['home_label'],
		'url'   => home_url( '/' ),
	);

	$is_shop_context = function_exists( 'is_shop' ) && ( is_shop() || is_product_category() || is_product_tag() || is_product() || is_tax( autodex_get_brand_taxonomy() ) );

	if ( $is_shop_context ) {
		$items[] = array(
			'label' => $args['shop_label'],
			'url'   => autodex_get_shop_url(),
		);
	}

	if ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$term      = get_queried_object();
		$ancestors = $term ? array_reverse( get_ancestors( $term->term_id, 'product_cat' ) ) : array();

		foreach ( $ancestors as $ancestor_id ) {
			$ancestor = get_term( $ancestor_id, 'product_cat' );

			if ( $ancestor && ! is_wp_error( $ancestor ) ) {
				$items[] = array(
					'label' => $ancestor->name,
					'url'   => get_term_link( $ancestor ),
				);
			}
		}

		if ( $term ) {
			$items[] = array(
				'label' => $term->name,
				'url'   => get_term_link( $term ),
			);
		}
	} elseif ( function_exists( 'is_product_tag' ) && is_product_tag() ) {
		$term = get_queried_object();

		if ( $term ) {
			$items[] = array(
				'label' => $term->name,
				'url'   => get_term_link( $term ),
			);
		}
	} elseif ( function_exists( 'is_tax' ) && is_tax( autodex_get_brand_taxonomy() ) ) {
		$term = get_queried_object();

		if ( $term ) {
			$items[] = array(
				'label' => $term->name,
				'url'   => get_term_link( $term ),
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
						'label' => $ancestor->name,
						'url'   => get_term_link( $ancestor ),
					);
				}
			}

			$items[] = array(
				'label' => $term->name,
				'url'   => get_term_link( $term ),
			);
		}

		$items[] = array(
			'label' => get_the_title( $product_id ),
			'url'   => '',
		);
	} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
		// The Shop link is already in the trail. Avoid WordPress's
		// "Archives: <span>Shop</span>" fallback title.
	} elseif ( is_search() ) {
		$items[] = array(
			'label' => sprintf( __( 'Pretraga: %s', 'autodex' ), get_search_query() ),
			'url'   => '',
		);
	} elseif ( is_404() ) {
		$items[] = array(
			'label' => __( 'Stranica nije pronađena', 'autodex' ),
			'url'   => '',
		);
	} elseif ( is_page() ) {
		$post_id   = get_queried_object_id();
		$ancestors = array_reverse( get_post_ancestors( $post_id ) );

		foreach ( $ancestors as $ancestor_id ) {
			$items[] = array(
				'label' => get_the_title( $ancestor_id ),
				'url'   => get_permalink( $ancestor_id ),
			);
		}

		$items[] = array(
			'label' => get_the_title( $post_id ),
			'url'   => '',
		);
	} elseif ( is_singular() ) {
		$items[] = array(
			'label' => get_the_title(),
			'url'   => '',
		);
	} elseif ( is_archive() ) {
		$items[] = array(
			'label' => wp_strip_all_tags( get_the_archive_title() ),
			'url'   => '',
		);
	}

	$class = trim( 'autodex-breadcrumbs ' . $args['class'] );
	$html  = '<nav class="' . esc_attr( $class ) . '" aria-label="' . esc_attr__( 'Breadcrumb', 'autodex' ) . '">';

	$last_index = count( $items ) - 1;

	foreach ( $items as $index => $item ) {
		$is_last = $index === $last_index;

		if ( $is_last ) {
			if ( ! empty( $item['url'] ) && ! is_singular() ) {
				$html .= '<a class="autodex-breadcrumbs__link" href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a>';
			} else {
				$html .= '<span class="autodex-breadcrumbs__current" aria-current="page">' . esc_html( $item['label'] ) . '</span>';
			}
		} elseif ( ! empty( $item['url'] ) ) {
			$html .= '<a class="autodex-breadcrumbs__link" href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a>';
			$html .= autodex_breadcrumb_separator();
		}
	}

	$html .= '</nav>';

	if ( $args['echo'] ) {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
	}

	return $html;
}

/* -------------------------------------------------------------------------
 * Pagination
 * ---------------------------------------------------------------------- */

/**
 * Custom pagination.
 *
 * @param WP_Query|null $query   Query object.
 * @param string        $context top|bottom.
 */
function autodex_pagination( $query = null, $context = 'bottom' ) {
	global $wp_query;

	$query = $query instanceof WP_Query ? $query : $wp_query;

	/**
	 * Paged URL that keeps active shop filters.
	 *
	 * @param int $page Page number.
	 * @return string
	 */
	$page_url = static function ( $page ) {
		$url       = get_pagenum_link( $page );
		$preserve  = array();
		$keys      = array( 'min_price', 'max_price', 's', 'post_type', 'orderby' );

		foreach ( $keys as $key ) {
			if ( isset( $_GET[ $key ] ) && is_scalar( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$preserve[ $key ] = sanitize_text_field( wp_unslash( $_GET[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
		}

		return $preserve ? add_query_arg( $preserve, $url ) : $url;
	};

	if ( ! ( $query instanceof WP_Query ) ) {
		return;
	}

	$total   = (int) $query->max_num_pages;
	$current = max( 1, (int) get_query_var( 'paged' ) );

	if ( $current < 1 ) {
		$current = 1;
	}

	if ( $total <= 1 ) {
		return;
	}

	$add_args = array();

	foreach ( array( 'min_price', 'max_price', 's', 'post_type', 'orderby' ) as $key ) {
		if ( isset( $_GET[ $key ] ) && is_scalar( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$add_args[ $key ] = sanitize_text_field( wp_unslash( $_GET[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
	}

	$links = paginate_links(
		array(
			'total'     => $total,
			'current'   => $current,
			'mid_size'  => 2,
			'end_size'  => 1,
			'prev_next' => false,
			'type'      => 'array',
			'add_args'  => $add_args,
		)
	);

	if ( ! is_array( $links ) ) {
		return;
	}

	$output = '<nav class="autodex-pagination__nav" aria-label="' . esc_attr__( 'Paginacija', 'autodex' ) . '"><div class="nav-links">';

	if ( 'bottom' === $context && $current > 1 ) {
		$output .= '<a class="page-numbers first-page" href="' . esc_url( $page_url( 1 ) ) . '" aria-label="' . esc_attr__( 'Prva stranica', 'autodex' ) . '">&laquo;</a>';
	}

	if ( $current > 1 ) {
		$output .= '<a class="page-numbers prev-page" href="' . esc_url( $page_url( $current - 1 ) ) . '" aria-label="' . esc_attr__( 'Prethodna stranica', 'autodex' ) . '">&lsaquo;</a>';
	}

	foreach ( $links as $link ) {
		$output .= $link;
	}

	if ( $current < $total ) {
		$output .= '<a class="page-numbers next-page" href="' . esc_url( $page_url( $current + 1 ) ) . '" aria-label="' . esc_attr__( 'Sljedeća stranica', 'autodex' ) . '">&rsaquo;</a>';
	}

	if ( 'bottom' === $context && $current < $total ) {
		$output .= '<a class="page-numbers last-page" href="' . esc_url( $page_url( $total ) ) . '" aria-label="' . esc_attr__( 'Zadnja stranica', 'autodex' ) . '">&raquo;</a>';
	}

	$output .= '</div></nav>';

	echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- paginate_links returns safe markup.
}

/* -------------------------------------------------------------------------
 * Small helpers
 * ---------------------------------------------------------------------- */

/**
 * First product category term.
 *
 * @param int $product_id Product ID.
 * @return WP_Term|null
 */
function autodex_get_primary_product_category( $product_id ) {
	$terms = get_the_terms( $product_id, 'product_cat' );

	if ( ! is_array( $terms ) || empty( $terms ) ) {
		return null;
	}

	return reset( $terms );
}

/**
 * Product card placeholder image.
 *
 * @return string
 */
function autodex_product_placeholder() {
	$placeholder = autodex_image_path( 'placeholder.webp' );

	if ( file_exists( $placeholder ) ) {
		return '<img src="' . esc_url( autodex_image_url( 'placeholder.webp' ) ) . '" alt="" class="autodex-product-card__image" loading="lazy" width="600" height="600">';
	}

	return function_exists( 'wc_placeholder_img' ) ? wc_placeholder_img( 'woocommerce_thumbnail' ) : '';
}

/**
 * Is the current request a WooCommerce account subpage?
 *
 * @return bool
 */
function autodex_is_account_endpoint() {
	return function_exists( 'is_account_page' ) && is_account_page() && function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url();
}
