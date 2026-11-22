<?php
/**
 * Template tags used by header, footer and page templates.
 *
 * @package Nabavi
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
function nabavi_site_logo( $variant = 'header', $echo = true ) {
	$variant  = 'footer' === $variant ? 'footer' : 'header';
	$is_foot  = 'footer' === $variant;
	$file     = $is_foot ? 'logo-footer.webp' : 'logo.webp';
	$src_w    = $is_foot ? 340 : 360;
	$base_w   = $is_foot ? 1024 : 780;
	$img_h    = $is_foot ? 142 : 148;
	$widths   = $is_foot ? array( 240, 340 ) : array( 240, 360 );
	$sizes    = $is_foot ? '170px' : '120px';

	$html  = '<div class="nabavi-logo nabavi-logo--' . esc_attr( $variant ) . '">';
	$html .= '<a class="nabavi-logo__link" href="' . esc_url( home_url( '/' ) ) . '" rel="home" aria-label="' . esc_attr( get_bloginfo( 'name' ) ) . '">';

	if ( 'header' === $variant && has_custom_logo() ) {
		$logo_id  = get_theme_mod( 'custom_logo' );
		$logo_img = wp_get_attachment_image(
			$logo_id,
			'full',
			false,
			array(
				'class'         => 'nabavi-logo__img',
				'alt'           => get_bloginfo( 'name' ),
				'loading'       => 'eager',
				'fetchpriority' => 'high',
			)
		);

		if ( $logo_img ) {
			$html .= $logo_img;
		}
	} else {
		$image_path = nabavi_image_path( $file );

		if ( file_exists( $image_path ) ) {
			$html .= sprintf(
				'<img class="nabavi-logo__img" src="%1$s" srcset="%2$s" sizes="%3$s" alt="%4$s" width="%5$d" height="%6$d" %7$s>',
				esc_url( nabavi_image_src( $file, $src_w ) ),
				esc_attr( nabavi_image_srcset( $file, $base_w, $widths ) ),
				esc_attr( $sizes ),
				esc_attr( get_bloginfo( 'name' ) ),
				$src_w,
				$img_h,
				$is_foot ? 'loading="lazy"' : 'loading="eager" fetchpriority="high"'
			);
		} else {
			$html .= '<span class="nabavi-logo__text">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
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
function nabavi_get_primary_nav_items() {
	$items = array(
		array( 'label' => 'Bijela Tehnika', 'slug' => 'bijela-tehnika' ),
		array( 'label' => 'Kućanski aparati', 'slug' => 'kucanski-aparati' ),
		array( 'label' => 'Televizori', 'slug' => 'televizori' ),
		array( 'label' => 'Računari', 'slug' => 'racunari' ),
		array( 'label' => 'Računarska oprema', 'slug' => 'racunarska-oprema' ),
		array( 'label' => 'Foto oprema', 'slug' => 'foto-oprema' ),
		array( 'label' => 'Ostalo', 'slug' => 'ostalo' ),
	);

	return apply_filters( 'nabavi_primary_nav_items', $items );
}

/**
 * Get the URL for a product category slug.
 *
 * @param string $slug Category slug.
 * @return string
 */
function nabavi_get_category_url( $slug ) {
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
function nabavi_nav_fallback( $args = array() ) {
	$class = isset( $args['menu_class'] ) ? $args['menu_class'] : 'nabavi-menu';
	$id    = isset( $args['menu_id'] ) ? $args['menu_id'] : 'nabavi-menu-fallback';

	echo '<ul id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . '">';

	foreach ( nabavi_get_primary_nav_items() as $item ) {
		$current = '';
		$term    = taxonomy_exists( 'product_cat' ) ? get_term_by( 'slug', $item['slug'], 'product_cat' ) : false;

		if ( $term && ! is_wp_error( $term ) && is_tax( 'product_cat', $term->term_id ) ) {
			$current = ' current-menu-item';
		}

		printf(
			'<li class="menu-item%1$s"><a href="%2$s">%3$s</a></li>',
			esc_attr( $current ),
			esc_url( nabavi_get_category_url( $item['slug'] ) ),
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
function nabavi_header_nav( $location = 'primary' ) {
	$mobile = 'mobile' === $location;
	$args   = array(
		'theme_location' => 'primary',
		'container'      => false,
		'menu_class'     => $mobile ? 'nabavi-mobile-menu' : 'nabavi-menu',
		'menu_id'        => $mobile ? 'nabavi-mobile-menu' : 'nabavi-primary-menu',
		'fallback_cb'    => 'nabavi_nav_fallback',
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
function nabavi_header_actions() {
	$account_label = is_user_logged_in() ? __( 'Moj račun', 'nabavi' ) : __( 'Prijava', 'nabavi' );
	$cart_count    = nabavi_get_cart_count();
	?>
	<div class="nabavi-header__actions">
		<?php if ( nabavi_option( 'nabavi_show_language', true ) && shortcode_exists( 'gtranslate' ) ) : ?>
			<div class="nabavi-lang" aria-label="<?php esc_attr_e( 'Jezik', 'nabavi' ); ?>">
				<?php echo do_shortcode( '[gtranslate]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- GTranslate markup. ?>
			</div>
		<?php endif; ?>

		<a class="nabavi-header__account" href="<?php echo esc_url( nabavi_get_account_url() ); ?>">
			<?php echo nabavi_get_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span class="nabavi-header__account-label"><?php echo esc_html( $account_label ); ?></span>
		</a>

		<span class="nabavi-header__separator" aria-hidden="true"></span>

		<a class="nabavi-header__cart" href="<?php echo esc_url( nabavi_get_cart_url() ); ?>" aria-label="<?php esc_attr_e( 'Korpa', 'nabavi' ); ?>">
			<?php echo nabavi_get_icon( 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span class="nabavi-cart-count"><?php echo esc_html( $cart_count ); ?></span>
		</a>

		<button class="nabavi-menu-toggle" type="button" aria-expanded="false" aria-controls="nabavi-mobile-nav" aria-label="<?php esc_attr_e( 'Otvori meni', 'nabavi' ); ?>">
			<?php echo nabavi_get_icon( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * Homepage data
 * ---------------------------------------------------------------------- */

/**
 * Build the curated homepage categories.
 *
 * @return array
 */
function nabavi_build_curated_categories() {
	$categories = array(
		array(
			'slug'  => 'bijela-tehnika',
			'label' => 'Bijela tehnika',
			'image' => 'cat-bijela-tehnika.webp',
		),
		array(
			'slug'  => 'kucanski-aparati',
			'label' => 'Kućanski aparati',
			'image' => 'cat-kucanski-aparati.webp',
		),
		array(
			'slug'  => 'mobiteli',
			'label' => 'Mobiteli',
			'image' => 'cat-mobiteli.webp',
		),
		array(
			'slug'  => 'auto-dijelovi',
			'label' => 'Auto dijelovi',
			'image' => 'cat-auto-dijelovi.webp',
			'url'   => 'https://autodex.ba',
		),
		array(
			'slug'  => 'televizori',
			'label' => 'Televizori',
			'image' => 'cat-televizori.webp',
		),
		array(
			'slug'  => 'racunari',
			'label' => 'Računari',
			'image' => 'cat-racunari.webp',
		),
		array(
			'slug'  => 'racunarska-oprema',
			'label' => 'Računarska oprema',
			'image' => 'cat-racunarska-oprema.webp',
		),
		array(
			'slug'  => 'foto-oprema',
			'label' => 'Foto oprema',
			'image' => 'cat-foto-oprema.webp',
		),
		array(
			'slug'  => 'klima-uredaji',
			'label' => 'Klima uređaji',
			'image' => 'cat-klima-uredaji.webp',
		),
		array(
			'slug'  => 'toplotne-pumpe',
			'label' => 'Toplotne pumpe',
			'image' => 'cat-toplotne-pumpe.webp',
		),
		array(
			'slug'  => 'elektricna-bicikla-i-romobili',
			'label' => 'Elek. bicikla i romobili',
			'image' => 'cat-elek-bicikla.webp',
		),
		array(
			'slug'  => 'igrace-konzole',
			'label' => 'Igraće konzole',
			'image' => 'cat-igrace-konzole.webp',
		),
	);

	foreach ( $categories as $index => $category ) {
		$categories[ $index ]['image_url'] = nabavi_image_url( $category['image'] );
		$categories[ $index ]['url']       = ! empty( $category['url'] ) ? $category['url'] : nabavi_get_category_url( $category['slug'] );
	}

	return $categories;
}

/**
 * Curated homepage categories (cached for 12 hours; flushed with catalog changes).
 *
 * @return array
 */
function nabavi_get_curated_categories() {
	$categories = nabavi_cache_get( 'curated_categories', 'nabavi_build_curated_categories', 12 * HOUR_IN_SECONDS );

	return apply_filters( 'nabavi_curated_categories', $categories );
}

/**
 * Product IDs for the "Najpopularnije" section.
 *
 * @param int $limit Number of products.
 * @return array
 */
function nabavi_get_popular_product_ids( $limit = 50 ) {
	if ( ! nabavi_is_woocommerce_active() || ! post_type_exists( 'product' ) ) {
		return array();
	}

	$limit = max( 1, (int) $limit );

	return nabavi_cache_get(
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
function nabavi_breadcrumb_separator() {
	return '<span class="nabavi-breadcrumbs__sep" aria-hidden="true">&rsaquo;</span>';
}

/**
 * Render a breadcrumb link.
 *
 * @param string $label Label.
 * @param string $url   URL.
 */
function nabavi_breadcrumb_item_link( $label, $url ) {
	printf(
		'<a class="nabavi-breadcrumbs__link" href="%1$s">%2$s</a>%3$s',
		esc_url( $url ),
		esc_html( $label ),
		nabavi_breadcrumb_separator() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
}

/**
 * Breadcrumbs for shop, products, pages and archives.
 *
 * @param array $args Optional arguments.
 */
function nabavi_breadcrumbs( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'class'      => '',
			'home_label' => __( 'Početna', 'nabavi' ),
			'shop_label' => __( 'Shop', 'nabavi' ),
			'echo'       => true,
		)
	);

	$items = array();
	$items[] = array(
		'label' => $args['home_label'],
		'url'   => home_url( '/' ),
	);

	$is_shop_context = function_exists( 'is_shop' ) && ( is_shop() || is_product_category() || is_product_tag() || is_product() || is_tax( nabavi_get_brand_taxonomy() ) );

	if ( $is_shop_context ) {
		$items[] = array(
			'label' => $args['shop_label'],
			'url'   => nabavi_get_shop_url(),
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
	} elseif ( function_exists( 'is_tax' ) && is_tax( nabavi_get_brand_taxonomy() ) ) {
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
			'label' => sprintf( __( 'Pretraga: %s', 'nabavi' ), get_search_query() ),
			'url'   => '',
		);
	} elseif ( is_404() ) {
		$items[] = array(
			'label' => __( 'Stranica nije pronađena', 'nabavi' ),
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

	$class = trim( 'nabavi-breadcrumbs ' . $args['class'] );
	$html  = '<nav class="' . esc_attr( $class ) . '" aria-label="' . esc_attr__( 'Breadcrumb', 'nabavi' ) . '">';

	$last_index = count( $items ) - 1;

	foreach ( $items as $index => $item ) {
		$is_last = $index === $last_index;

		if ( $is_last ) {
			if ( ! empty( $item['url'] ) && ! is_singular() ) {
				$html .= '<a class="nabavi-breadcrumbs__link" href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a>';
			} else {
				$html .= '<span class="nabavi-breadcrumbs__current" aria-current="page">' . esc_html( $item['label'] ) . '</span>';
			}
		} elseif ( ! empty( $item['url'] ) ) {
			$html .= '<a class="nabavi-breadcrumbs__link" href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a>';
			$html .= nabavi_breadcrumb_separator();
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
function nabavi_pagination( $query = null, $context = 'bottom' ) {
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

	$output = '<nav class="nabavi-pagination__nav" aria-label="' . esc_attr__( 'Paginacija', 'nabavi' ) . '"><div class="nav-links">';

	if ( 'bottom' === $context && $current > 1 ) {
		$output .= '<a class="page-numbers first-page" href="' . esc_url( $page_url( 1 ) ) . '" aria-label="' . esc_attr__( 'Prva stranica', 'nabavi' ) . '">&laquo;</a>';
	}

	if ( $current > 1 ) {
		$output .= '<a class="page-numbers prev-page" href="' . esc_url( $page_url( $current - 1 ) ) . '" aria-label="' . esc_attr__( 'Prethodna stranica', 'nabavi' ) . '">&lsaquo;</a>';
	}

	foreach ( $links as $link ) {
		$output .= $link;
	}

	if ( $current < $total ) {
		$output .= '<a class="page-numbers next-page" href="' . esc_url( $page_url( $current + 1 ) ) . '" aria-label="' . esc_attr__( 'Sljedeća stranica', 'nabavi' ) . '">&rsaquo;</a>';
	}

	if ( 'bottom' === $context && $current < $total ) {
		$output .= '<a class="page-numbers last-page" href="' . esc_url( $page_url( $total ) ) . '" aria-label="' . esc_attr__( 'Zadnja stranica', 'nabavi' ) . '">&raquo;</a>';
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
function nabavi_get_primary_product_category( $product_id ) {
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
function nabavi_product_placeholder() {
	$placeholder = nabavi_image_path( 'placeholder.webp' );

	if ( file_exists( $placeholder ) ) {
		return '<img src="' . esc_url( nabavi_image_url( 'placeholder.webp' ) ) . '" alt="" class="nabavi-product-card__image" loading="lazy" width="600" height="600">';
	}

	return function_exists( 'wc_placeholder_img' ) ? wc_placeholder_img( 'woocommerce_thumbnail' ) : '';
}

/**
 * Is the current request a WooCommerce account subpage?
 *
 * @return bool
 */
function nabavi_is_account_endpoint() {
	return function_exists( 'is_account_page' ) && is_account_page() && function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url();
}
