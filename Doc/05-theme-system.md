# Theme System

## Scope

The storefront is server-rendered and Bagisto-native by default. Bagisto routes and controllers remain the public storefront skeleton; the active Bagisto channel theme controls which Blade views are used.

Custom visual work is delivered as normal Bagisto shop themes. The current custom theme is `gadget`, registered in `config/themes.php`, with views under `resources/themes/gadget/views` and public assets under `public/themes/shop/gadget`.

## Principles

- keep Bagisto as the storefront route, controller, and commerce core
- use one theme-core package for shared contracts and preset activation behavior
- use Bagisto shop themes for visual variation
- override only the views a theme actually owns
- let non-overridden views fall back to native Bagisto
- keep business logic out of Blade templates

## Runtime Render Pipeline

Normal public storefront rendering follows Bagisto:

1. Bagisto resolves the route and controller.
2. The shop theme middleware reads the current channel `theme`.
3. Bagisto view resolution searches the active theme view path first.
4. If the active theme does not provide an override, Bagisto falls back to native shop views.
5. Product, category, cart, checkout, customer, order, price, inventory, and promotion logic stay in Bagisto.

The `default` shop theme renders native Bagisto views. The `gadget` shop theme currently overrides only `shop::home.index` through `resources/themes/gadget/views/home/index.blade.php`.

## Gadget Theme

`gadget` is a saved Bagisto shop theme, not a replacement storefront application.

Current implementation:

- `views_path`: `resources/themes/gadget/views`
- `assets_path`: `public/themes/shop/gadget`
- homepage override: `resources/themes/gadget/views/home/index.blade.php`
- homepage partials: `resources/themes/gadget/views/homepage/...`
- scoped CSS: `public/themes/shop/gadget/gadget.css`
- Vite points to the existing Bagisto shop build so native JavaScript and Vue components continue to work

Because only the homepage is overridden, these pages continue to fall back to native Bagisto while `gadget` is active:

- category listing
- product detail
- policy/static CMS pages
- search
- cart
- checkout
- customer account
- orders

Future Gadget category, product, policy, and customer page designs should be added as Bagisto theme view overrides, not as route/controller replacements.

## Admin Activation

Theme selection is managed through `Admin -> Theme -> Presets`.

Preset activation updates two things in one transaction:

- exactly one `theme_presets` record is active
- the current Bagisto channel `theme` is set to `settings_json.shop_theme_code`

Seeded presets:

- `Default`: `settings_json.shop_theme_code = default`
- `Gadget`: `settings_json.shop_theme_code = gadget`

Activating `Default` restores the native Bagisto storefront. Activating `Gadget` loads the Gadget homepage override while all non-overridden pages remain Bagisto-native.

## CMS Preview Pipeline

Structured CMS still owns authoring, schema-backed sections, component rendering, and signed previews.

CMS preview routes are separate from normal Bagisto storefront routes:

- `/home-preview`
- `/preview/pages/{slug}`
- `/preview/category-pages/{page}/{categorySlug}`
- `/preview/product-pages/{page}/{productSlug}`

Those routes are signed and are not used for normal public browsing. They exist so admins and developers can preview structured CMS/page-composition work without replacing Bagisto storefront controllers.

## Theme Presets

Theme presets remain configuration-driven.

Current preset responsibilities:

- active preset selection in the CMS/admin layer
- mapping a preset to a Bagisto shop theme code through `settings_json.shop_theme_code`
- token payload storage for future design-token-driven rendering

The active preset is not a route/controller switch. It is an admin-facing configuration record that also updates the Bagisto channel theme.

## Section And Component Rendering

Current structured rendering still follows a stable contract for CMS previews:

- section type definition provides defaults and validation
- authored section settings are merged with registry defaults
- supported data source output is attached to the section payload
- the view renders prepared payload data
- nested components are validated, normalized, and rendered from prepared payloads

Current section views include:

- hero banner
- featured products
- rich text
- promo strip
- category intro
- product gallery
- product summary
- product price
- product options
- add to cart
- stock and shipping info
- product details
- FAQ block
- related products
- trust badges
- generic section fallback

Current nested component views include:

- headline
- body text
- CTA button group
- badge list
- link list

## Header, Footer, And Menu Rendering

Bagisto-native public pages use their native header, footer, menu, and component behavior unless a theme view override changes the markup.

CMS preview and CMS-aware theme views use dedicated services for structured global areas:

- `MenuResolver`
- `HeaderResolver`
- `FooterResolver`

CMS-aware homepage theme partials build storefront header payloads through `StorefrontHeaderViewModel`. Gadget and Clothing keep their own visual header markup, but consume the same Header Builder values for uploaded logo path/URL, announcement bar, selected navigation menu, search/account/cart visibility, and sticky behavior. If CMS settings are missing, themes fall back to safe Bagisto/channel defaults.

## Asset Loading Rule

Native Bagisto shop assets remain under `public/themes/shop/default/build`.

The Gadget theme keeps its Figma-specific homepage CSS in `public/themes/shop/gadget/gadget.css` and loads it only from the Gadget homepage override. Root `resources/css/app.css` must not carry Gadget-only CSS because that would affect native Bagisto and CMS/admin surfaces.

## Theme Creation Guide

Follow `Doc/19-theme-creation-guide.md` when creating new themes or adding new Gadget page overrides.

## Remaining Gaps Before Frontend Polish

- Gadget currently overrides homepage only
- category and PDP Gadget designs are pending
- policy/static page Gadget design is pending
- customer account visual alignment can be added later through theme overrides
- preset tokens are stored but not yet exhaustively applied to native Bagisto fallback pages
