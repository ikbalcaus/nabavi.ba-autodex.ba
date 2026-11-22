# Nabavi.ba — custom WordPress/WooCommerce theme

A complete, Elementor-free rebuild of the Nabavi.ba storefront. The theme uses
the original visual language (orange `#EB6921`, 5-column product grids,
Montserrat navigation, dark footer) and replaces all Elementor/Blocksy
templates with plain PHP templates and CSS.

## Requirements

- WordPress 6.2+ (tested on 7.1)
- WooCommerce 11.x (tested with 11.2.1)
- PHP 7.4+ (tested on PHP 8.2)
- No page builder required

## Installation

1. Copy this folder to `wp-content/themes/nabavi.ba/`.
2. Activate **Nabavi.ba** in *Appearance → Themes*.
3. Install and activate **WooCommerce** if it is not active yet.
4. On activation the theme automatically:
   - creates the `Shop`, `Korpa`, `Naplata` and `Moj račun` pages and assigns
     the WooCommerce page options;
   - sets the currency to **BAM**, decimal comma and thousands dot;
   - sets product permalinks to `/proizvod/...` and category base to `/shop`;
   - creates the 13 curated top-level product categories used by the homepage;
   - enables Cash on Delivery (`Plaćanje pouzećem`);
   - creates the `O nama` and `Kontakt` pages with their page templates.
5. Import products through *Products → Import* (or copy the live database).
   Product images and descriptions are intentionally not bundled.
6. Set the menu under *Appearance → Menus* if you want a custom menu. Without a
   menu the theme uses the exact seven-item category fallback (Bijela tehnika,
   Kućanski aparati, Televizori, Računari, Računarska oprema, Foto oprema,
   Ostalo).
7. Contact details and footer text can be edited in
   *Appearance → Customize → Nabavi.ba opcije*.

## Structure

```
nabavi.ba/
├── header.php / footer.php / functions.php
├── front-page.php                  Homepage: hero, category grid, popular products
├── page.php / page-o-nama.php / page-kontakt.php
├── index.php / archive.php / single.php / search.php / 404.php / comments.php
├── inc/
│   ├── helpers.php                 Cache, icons, options, WooCommerce guards
│   ├── setup.php                   Theme supports, menus, pages, categories
│   ├── assets.php                  CSS/JS enqueueing and versioning
│   ├── customizer.php              Contact/footer options
│   ├── template-tags.php           Logo, nav, breadcrumbs, pagination, popular IDs
│   ├── translations.php            Bosnian WooCommerce strings
│   ├── woocommerce.php             Store setup, loops, filters, account endpoints
│   ├── shortcodes.php              [custom_breadcrumb], [korpa_button], popular products
│   └── contact.php                 Native contact form (replaces WPForms)
├── woocommerce/
│   ├── archive-product.php         Shop/category/brand/search layout + filters
│   ├── content-product.php         Product card
│   ├── single-product.php          Product page with gallery, summary, tabs
│   ├── loop/orderby.php
│   ├── loop/no-products-found.php
│   ├── global/quantity-input.php
│   ├── cart/cart-empty.php
│   └── myaccount/form-login.php
├── assets/
│   ├── css/                        global, product-card, home, archive, single product,
│   │                               woocommerce, account, pages
│   ├── js/                         main, archive-product, single-product, account, contact
│   └── images/                     logo, footer logo, hero and category images
└── screenshot.png
```

## What was ported from the old custom code

| Old code | New location |
| --- | --- |
| Caching helpers | `inc/helpers.php` (`nabavi_cache_get`, versioned cache) |
| Asset enqueue + filemtime versions | `inc/assets.php` |
| 5 columns / 20 per page / sorting | `inc/woocommerce.php` |
| Search redirect to shop | `inc/woocommerce.php` |
| Account endpoint localization + redirects | `inc/woocommerce.php` |
| Price filter SQL + query | `inc/woocommerce.php` |
| Shop sidebar (price/categories/brands) | `inc/woocommerce.php` + `woocommerce/archive-product.php` |
| Custom breadcrumbs | `inc/template-tags.php`, `[custom_breadcrumb]` |
| Product view counter + popular products | `inc/woocommerce.php`, `inc/template-tags.php` |
| `[najpopularniji_proizvodi]` | `inc/shortcodes.php` |
| `[korpa_button]`, `[korpa_badge]` | `inc/shortcodes.php` |
| WooCommerce Bosnian strings | `inc/translations.php` |
| Hide tags / account privacy CSS | `inc/woocommerce.php` |
| Mobile filter slide-in + price slider JS | `assets/js/archive-product.js` |
| Cart quantity +/- | `assets/js/main.js` + `woocommerce/global/quantity-input.php` |
| WPForms contact form | `inc/contact.php` + `page-kontakt.php` (native, emails the site) |

## Performance notes

- Product loops are limited to 20 items; the homepage "Najpopularnije" section
  requests 20 IDs and renders them in one query.
- Price ranges, brand lists, category lists, popular IDs and translations are
  cached with transients (or the object cache when a persistent cache exists).
- Cache is invalidated automatically when a product, category, brand or menu is
  changed.
- Montserrat/Roboto are self-hosted in `assets/fonts/` (no Google Fonts request).
- Category cards use responsive `-480`/`-640` WebP variants with `srcset`.
- WordPress emoji/embed scripts and block CSS are removed where unused; the hero
  is preloaded on the homepage.
- Assets use `filemtime()` cache busting.
- All WooCommerce code is guarded, so the theme does not fatal if WooCommerce is
  temporarily disabled.

## Assets

The logo, hero banner, category images and placeholder were downloaded from the
original site into `assets/images/`. Replace them with higher-resolution files if
needed; the paths are referenced from `inc/template-tags.php` and
`front-page.php`.

## Notes

- Deactivate **Elementor**, **Header Footer Elementor**, **Blocksy Companion**
  and **WPForms** after switching to this theme. They are no longer needed and
  an active HFE header/footer can otherwise duplicate the theme header/footer.
- The theme creates pages/categories only once, guarded by versioned options
  (`nabavi_store_setup_version`, `nabavi_categories_setup_version`).
- If the store already has custom page slugs, the setup does not overwrite them.
- The contact form uses `wp_mail()` and sends to `info@nabavi.ba` by default.
  Change the recipient in the Customizer.
- The header language switcher renders the GTranslate shortcode `[gtranslate]`.
  It is hidden automatically when the GTranslate plugin is inactive.
- Product tags are hidden from non-admins, matching the original store.
