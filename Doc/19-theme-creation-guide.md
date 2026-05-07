# Theme Creation Guide

## Purpose

This guide explains how to create storefront themes without breaking Bagisto.

Themes in this product are Bagisto shop themes. They change storefront presentation through Blade view overrides and scoped assets. They must not replace Bagisto storefront routes, controllers, product logic, category logic, cart, checkout, customer account, orders, pricing, inventory, payment, or shipping behavior.

## Current Baseline

Registered shop themes:

- `default`: native Bagisto storefront
- `gadget`: custom Gadget homepage override

Admin presets:

- `Default`: maps to `settings_json.shop_theme_code = default`
- `Gadget`: maps to `settings_json.shop_theme_code = gadget`

Theme activation happens in `Admin -> Theme -> Presets`. Activating a preset updates the current Bagisto channel `theme`.

## Theme Anatomy

A custom theme has three parts:

- a theme registration in `config/themes.php`
- Blade overrides under `resources/themes/{theme-code}/views`
- public assets under `public/themes/shop/{theme-code}`

Example for `gadget`:

```txt
resources/themes/gadget/views
public/themes/shop/gadget
```

The active theme is selected by the current Bagisto channel:

```txt
channels.theme = gadget
```

Bagisto view resolution checks the active theme first. If the theme does not provide a matching view, Bagisto falls back to native shop views.

## Non-Negotiable Boundary

Do not change these for visual theme work:

- Bagisto storefront routes
- Bagisto public storefront controllers
- product/category slug routing
- cart logic
- checkout logic
- customer account logic
- order logic
- pricing, inventory, sale, tax, shipping, payment logic
- database schema or migrations

Theme work should normally touch only:

- `config/themes.php`
- `resources/themes/{theme-code}/views/...`
- `public/themes/shop/{theme-code}/...`
- theme preset seed/config
- docs/tests for the theme

## Step 1: Choose A Theme Code

Use a stable lowercase code:

```txt
gadget
fashion
furniture
beauty
electronics
```

Rules:

- lowercase
- no spaces
- prefer letters, numbers, hyphens, or underscores
- do not expose client-specific private names in reusable product code

## Step 2: Register The Theme

Add the theme to `config/themes.php`:

```php
'shop' => [
    'default' => [
        'name' => 'Default',
        'assets_path' => 'public/themes/shop/default',
        'views_path' => 'resources/themes/default/views',
        'vite' => [
            'hot_file' => 'shop-default-vite.hot',
            'build_directory' => 'themes/shop/default/build',
            'package_assets_directory' => 'src/Resources/assets',
        ],
    ],

    'gadget' => [
        'name' => 'Gadget',
        'assets_path' => 'public/themes/shop/gadget',
        'views_path' => 'resources/themes/gadget/views',
        'vite' => [
            'hot_file' => 'shop-default-vite.hot',
            'build_directory' => 'themes/shop/default/build',
            'package_assets_directory' => 'src/Resources/assets',
        ],
    ],
],
```

For v1 themes, keep `vite` pointed at the native Bagisto shop build unless the theme has a documented reason to own a separate shop asset build. This keeps native Vue components working.

## Step 3: Create Theme Folders

Create:

```txt
resources/themes/{theme-code}/views
public/themes/shop/{theme-code}
```

For example:

```txt
resources/themes/gadget/views
public/themes/shop/gadget
```

Do not put theme assets in admin directories. Do not put theme-specific CSS in root `resources/css/app.css`.

## Step 4: Add A Preset

Theme activation is driven by `theme_presets.settings_json.shop_theme_code`.

Seeder example:

```php
[
    'name' => 'Gadget',
    'code' => 'gadget',
    'tokens_json' => [
        'code' => 'gadget',
        'name' => 'Gadget',
        'colors' => [
            'background' => '#ffffff',
            'surface' => '#f5f7f2',
            'primary' => '#111111',
            'accent' => '#ff4b37',
            'text' => '#111111',
            'muted' => '#5f6368',
        ],
    ],
    'settings_json' => [
        'shop_theme_code' => 'gadget',
        'header_variant' => 'gadget',
        'footer_variant' => 'gadget',
        'product_card_variant' => 'gadget',
    ],
],
```

Important:

- `code` is the admin preset code.
- `settings_json.shop_theme_code` is the real Bagisto shop theme code.
- The shop theme code must exist in `config('themes.shop')`.

## Step 5: Override Only The Needed Views

Copy or recreate only the Bagisto views that the theme must visually change.

Common view targets:

```txt
shop::home.index
shop::categories.view
shop::products.view
shop::cms.page
shop::search.index
shop::components.products.card
```

Theme file paths:

```txt
resources/themes/{theme-code}/views/home/index.blade.php
resources/themes/{theme-code}/views/categories/view.blade.php
resources/themes/{theme-code}/views/products/view.blade.php
resources/themes/{theme-code}/views/cms/page.blade.php
resources/themes/{theme-code}/views/search/index.blade.php
resources/themes/{theme-code}/views/components/products/card.blade.php
```

If a view is not present in the custom theme, Bagisto falls back to the native view.

This is the expected behavior. It allows a theme to be built page-by-page.

## Step 6: Keep Data Dynamic

Use Bagisto-provided variables and services. Do not reimplement commerce logic in Blade.

Examples:

- product page overrides should use the `$product` passed by Bagisto
- category page overrides should use the `$category` and listing data passed by Bagisto
- CMS page overrides should use the `$page` passed by Bagisto
- search page overrides should use the existing search payload
- product cards should keep product links, images, prices, sale labels, and add-to-cart behavior functional

If a Figma design needs a section but data is not ready, use a safe fallback and add a short TODO comment.

## Step 7: Add Scoped Theme Assets

Theme-specific CSS should live under:

```txt
public/themes/shop/{theme-code}/{theme-code}.css
```

Load it only from the theme view that needs it:

```blade
@push('styles')
    <link rel="stylesheet" href="{{ asset('themes/shop/gadget/gadget.css') }}">
@endpush
```

Rules:

- prefix theme classes, for example `.gadget-home`, `.gadget-product-card`
- avoid global selectors that affect native Bagisto pages
- do not hotlink Figma asset URLs
- store exported assets locally under `public/themes/shop/{theme-code}`
- avoid changing admin CSS

## Step 8: Figma-To-Theme Workflow

For each approved Figma page:

1. Inspect the Figma node with Figma MCP.
2. Identify every visible section in order.
3. Map the page to the Bagisto view target.
4. Create or update theme-local partials.
5. Export required assets into `public/themes/shop/{theme-code}`.
6. Keep real Bagisto data and links functional.
7. Add fallbacks only where data/assets are unavailable.
8. Test desktop, tablet, and mobile widths.
9. Run lint, style, tests, and asset build.

Recommended section folder pattern:

```txt
resources/themes/{theme-code}/views/{page}/sections
resources/themes/{theme-code}/views/{page}/partials
```

Example:

```txt
resources/themes/gadget/views/homepage/sections
resources/themes/gadget/views/homepage/partials
```

## Step 9: Page Implementation Order

Recommended order for a new theme:

1. homepage
2. product card component
3. category listing page
4. product detail page
5. search page
6. policy/static CMS page
7. customer auth/account pages, if explicitly scoped
8. cart/checkout visual polish, only if business flow is preserved

Do not start by replacing cart or checkout. Those flows are business-critical.

## Step 10: Activation Test

After creating a theme, verify:

```bash
./vendor/bin/sail artisan tinker --execute='dump(array_keys(config("themes.shop")));'
```

Activate from `Admin -> Theme -> Presets`, then confirm:

```bash
./vendor/bin/sail artisan tinker --execute='dump(core()->getCurrentChannel()->fresh()->theme);'
```

The output should match the theme code.

## Step 11: Required Commands

Run:

```bash
./vendor/bin/sail artisan view:clear
./vendor/bin/sail artisan optimize:clear
./vendor/bin/sail pint --dirty
npm run build
```

Run targeted tests for theme activation and affected storefront pages:

```bash
./vendor/bin/sail artisan test tests/Feature/Theme/StorefrontThemeActivationTest.php
```

Add or update tests when adding new page overrides.

## Step 12: Manual Smoke Checks

Check both `default` and the custom theme.

Default theme:

- `/` shows native Bagisto homepage
- no custom theme markers/classes appear

Custom theme:

- `/` shows custom theme homepage if overridden
- overridden pages render custom UI
- non-overridden pages fall back to native Bagisto

Always check:

- homepage
- category route
- product route
- `/page/privacy-policy`
- search
- cart
- checkout entry
- customer login
- admin login

Responsive widths:

- `1440px`
- `1024px`
- `768px`
- `390px`

Check for:

- no horizontal overflow
- product links work
- category links work
- search works
- cart/account links remain functional
- no new console errors from theme code

## Common Mistakes

Do not:

- create a separate frontend app
- add React/Vue for the storefront theme unless Bagisto already uses it for that surface
- override controllers for visual design
- duplicate product price or sale logic
- duplicate inventory logic
- place theme CSS in `resources/css/app.css`
- modify admin dashboard CSS for storefront themes
- add theme records without registering the theme in `config/themes.php`
- assume color tokens alone change the storefront UI

Theme presets are configuration records. They only change the storefront visually when they map to a registered shop theme that has actual Blade/CSS overrides.

## How Gadget Should Continue

Gadget currently owns:

```txt
resources/themes/gadget/views/home/index.blade.php
resources/themes/gadget/views/homepage
public/themes/shop/gadget/gadget.css
```

When Gadget category design is approved, add:

```txt
resources/themes/gadget/views/categories/view.blade.php
```

When Gadget product design is approved, add:

```txt
resources/themes/gadget/views/products/view.blade.php
resources/themes/gadget/views/components/products/card.blade.php
```

When Gadget policy/static page design is approved, add:

```txt
resources/themes/gadget/views/cms/page.blade.php
```

No route/controller changes should be needed for these pages.
