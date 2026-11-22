# Autodex.ba — custom WordPress/WooCommerce theme

A complete, Elementor-free WooCommerce theme built for the Autodex.ba automotive
store. It shares the architecture of the Nabavi.ba theme (plain PHP templates,
native WooCommerce templates, no page builder) but uses a completely different
visual language:

- dark graphite header and footer with the Autodex logo red `#E11D2E`;
- Barlow Condensed headings/navigation and Barlow body text;
- a split "workshop" hero with vehicle-inquiry call to action;
- workshop service cards (major service, minor service, turbo repair);
- a photographic category grid with dark overlay titles;
- bordered product cards with full-width add-to-cart buttons.

## Requirements

- WordPress 6.2+ (tested on 7.1)
- WooCommerce 11.x (tested with 11.2.1)
- PHP 7.4+ (tested on PHP 8.2)
- No page builder required

## Installation

1. Copy this folder to `wp-content/themes/autodex.ba/`.
2. Activate **Autodex.ba** in *Appearance → Themes*.
3. Install and activate **WooCommerce** if it is not active yet.
4. On activation the theme automatically:
   - creates the `Shop`, `Korpa`, `Naplata` and `Moj račun` pages and assigns
     the WooCommerce page options;
   - sets the currency to **BAM**, decimal comma and thousands dot;
   - sets product permalinks to `/proizvod/...` and category base to `/shop`;
   - creates the 17 curated homepage product categories plus the `veliki-servis`
     and `mali-servis` service categories;
   - enables Cash on Delivery (`Plaćanje pouzećem`);
   - creates the `O nama` and `Kontakt` pages with their page templates.
5. Import products through *Products → Import* (or copy the live database).
   Product images and descriptions are intentionally not bundled.
6. Set the menu under *Appearance → Menus* if you want a custom menu. Without a
   menu the theme uses the five-item fallback from the live site (Reparacija
   turbina, Veliki servis, Mali servis, Revenol ulja, Ostalo).
7. Contact details, footer text and the BA language badge can be edited in
   *Appearance → Customize → Autodex.ba opcije*.

## Homepage

`front-page.php` renders the whole homepage without Elementor:

1. **Hero** — headline, inquiry CTA, shop link and trust points, with the
   bundled turbo image. The CTA points to the page at
   `/posaljite-upit-za-vase-vozilo/` when it exists, otherwise to `/kontakt/`.
2. **Services** — the three workshop services with images and descriptions.
3. **Category grid** — 17 shop categories with image overlays.
4. **How it works** — three numbered steps on a dark band.
5. **Najpopularnije** — twenty product cards (5 columns × 4 rows) based on the
   theme view counter (`sk_pregledi_proizvoda`), with random products as filler.

Category and service data live in `inc/template-tags.php`
(`autodex_get_curated_categories()`, `autodex_get_services()`), so they can be
changed without touching the template.

## Service and inquiry pages

The theme ensures these pages exist, are published and use the localized slugs:

| Page | Content |
| --- | --- |
| `/veliki-servis/` | Inquiry form + products from the `veliki-servis` product category |
| `/mali-servis/` | Inquiry form + products from the `mali-servis` product category |
| `/reparacija-turbina/` | Inquiry form |
| `/posaljite-upit-za-vase-vozilo/` | General vehicle inquiry form |

The forms collect phone, chassis number (VIN), a message and an optional
registration image or PDF (max 5 MB), then email the site's contact recipient.
The handler and page repair logic live in `inc/inquiry.php`.

## Structure

```
autodex.ba/
├── header.php / footer.php / functions.php
├── front-page.php                  Homepage: hero, services, categories, steps, products
├── page.php / page-o-nama.php / page-kontakt.php
├── index.php / archive.php / single.php / search.php / 404.php / comments.php
├── inc/
│   ├── helpers.php                 Cache, icons, options, WooCommerce guards
│   ├── setup.php                   Theme supports, menus, pages, categories
│   ├── assets.php                  CSS/JS enqueueing and versioning
│   ├── customizer.php              Contact/footer options
│   ├── template-tags.php           Logo, nav, homepage data, breadcrumbs, pagination
│   ├── translations.php            Bosnian WooCommerce strings
│   ├── woocommerce.php             Store setup, loops, filters, account endpoints
│   ├── shortcodes.php              Breadcrumb, cart, products and categories shortcodes
│   ├── inquiry.php                 Service/inquiry forms, uploads and page setup
│   └── contact.php                 Native contact form (replaces WPForms)
├── snippets/
│   ├── brand-cijene.php            Brand markup pricing + low-profit cleanup
│   ├── checkout-postarina-ravenol.php  Ravenol shipping table on checkout
│   └── sakrij-swag-brend.php       Hide SWAG products from archives/search
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
│   └── images/                     logo, hero and category images
└── screenshot.png
```

## Design tokens

All colors, fonts, radii and containers are defined in
`assets/css/global.css`:

| Token | Value | Usage |
| --- | --- | --- |
| `--autodex-primary` | `#E11D2E` | buttons, links, badges, active nav |
| `--autodex-dark` | `#0B141E` | top bar, navigation band, footer, steps |
| `--autodex-heading` | `#101B2A` | headings and prices |
| `--autodex-bg` | `#F3F5F8` | page background |
| `--autodex-font-nav` | Barlow Condensed | headings, navigation, buttons |
| `--autodex-font` | Barlow | body text |

Changing these values re-skins the entire theme. The logo red is taken from the
Autodex brand mark; the dark bands echo the workshop/automotive feel.

## Assets

The logo, hero image and category images were downloaded from the live
Autodex.ba site into `assets/images/`. Smaller `-480` and `-640` width variants
are generated for the category/service cards, and the hero/logo have their own
responsive variants. The Barlow and Barlow Condensed fonts are self-hosted in
`assets/fonts/` and declared at the top of `assets/css/global.css`.

The theme does not generate or bundle product photos.

## Performance notes

- Product loops are limited to 20 items; the homepage "Najpopularnije" section
  requests 20 IDs and renders them in one query.
- The homepage hero is preloaded in `<head>`; below-the-fold imagery uses
  `loading="lazy"`, `srcset` and `sizes`.
- Fonts are self-hosted (no Google Fonts request) and loaded with
  `font-display: swap`.
- WordPress emoji/embed scripts and block CSS are removed where unused.
- WooCommerce CSS is not loaded on pages that do not render store components.
- Product view counting skips bots and counts a visitor once per product per day,
  avoiding a database write on every product view.
- Price ranges, brand lists, category lists, popular IDs and translations are
  cached with transients (or the object cache when a persistent cache exists).
- Cache is invalidated automatically when a product, category, brand or menu is
  changed.
- Assets use `filemtime()` cache busting.
- All WooCommerce code is guarded, so the theme does not fatal if WooCommerce is
  temporarily disabled.

## Notes

- Deactivate **Elementor**, **Header Footer Elementor**, **Blocksy Companion**
  and **WPForms** after switching to this theme. They are no longer needed and
  an active HFE header/footer can otherwise duplicate the theme header/footer.
- The theme creates pages/categories only once, guarded by versioned options
  (`autodex_store_setup_version`, `autodex_categories_setup_version`).
- If the store already has custom page slugs, the setup does not overwrite them.
- The contact form uses `wp_mail()` and sends to `info@autodex.ba` by default.
  Change the recipient in the Customizer.
- The header language switcher renders the GTranslate shortcode `[gtranslate]`.
  It is hidden automatically when the GTranslate plugin is inactive.
- Product tags are hidden from non-admins, matching the original store.
