# CMS Vertical Slice Note

## What Is Complete

Milestone 2 provides one coherent structured CMS homepage preview path:

- structured page persistence
- template and template area assignment
- approved section type selection
- header/footer/menu/theme preset assignment
- SEO assignment
- signed preview
- publish and unpublish workflow with version snapshots
- theme-layer rendering for signed previews
- one live commerce-backed featured products section

The CMS-authored storefront path is not the default public storefront path. Normal public browsing uses Bagisto routes/controllers and the active Bagisto shop theme. Bagisto remains the commerce core behind every public storefront theme.

The reference homepage uses:

- `homepage_default` template
- `hero_banner`
- `featured_products`
- `rich_text`
- one header config
- one footer config
- one menu
- one theme preset

## What Was Validated

The slice was validated with:

- `composer dump-autoload`
- `php artisan package:discover --ansi`
- `php artisan migrate --force`
- `php artisan db:seed --force`
- `php artisan test tests/Feature/Cms --stop-on-failure`
- live HTTP `200` checks for `/`, `/home-preview` with a signed URL, `/admin/login`, and `/customer/login`

## Important Touchpoint

The storefront root route keeps the native Bagisto controller flow. CMS preview routes remain signed and separate, including `/home-preview` and `/preview/...` routes, so CMS previews do not intercept product, category, policy, cart, checkout, customer, or order routes.

## Remaining Limitations

Before category page CMS and PDP CMS work, these gaps remain:

- category listing pages do not yet use Gadget or CMS-owned public view overrides
- PDP blocks are stored for preview work, but public PDP rendering remains Bagisto-native until a theme override is added
- nested section component authoring is not yet exposed in admin
- menu editing is still a flat form instead of a drag/tree editor
- site settings and content entries are not yet in the active authoring workflow
- version history is captured but restore/revert UX is not yet implemented

## Recommended Next Step

Proceed to Milestone 3 by hardening the theme-layer contracts around the already working homepage slice rather than introducing new page types first.
