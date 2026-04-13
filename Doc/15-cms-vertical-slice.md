# CMS Vertical Slice Note

## What Is Complete

Milestone 2 now provides one coherent CMS-authored homepage path:

- structured page persistence
- template and template area assignment
- approved section type selection
- header/footer/menu/theme preset assignment
- SEO assignment
- signed preview
- publish and unpublish workflow with version snapshots
- theme-layer rendering for the published homepage
- one live commerce-backed featured products section

This CMS-authored storefront path is opt-in. The live public storefront remains Bagisto-native by default until `EXPERIENCE_CMS_STOREFRONT_MODE=cms` is set.

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
- live HTTP `200` checks for `/`, `/home-preview` with a signed URL, `/admin/login`, and `/customer/login` when CMS storefront mode is enabled

## Important Touchpoint

The storefront root route remains part of the commerce core. The CMS homepage is enabled through a CMS-aware wrapper bound to the upstream home controller only when CMS storefront mode is enabled, which preserves the commerce route while allowing a published CMS homepage to take over when present.

## Remaining Limitations

Before category page CMS and PDP CMS work, these gaps remain:

- category listing pages do not yet use CMS-owned pre-listing or layout configuration
- PDP blocks are not yet stored or rendered through a controlled CMS structure
- nested section component authoring is not yet exposed in admin
- menu editing is still a flat form instead of a drag/tree editor
- site settings and content entries are not yet in the active authoring workflow
- version history is captured but restore/revert UX is not yet implemented

## Recommended Next Step

Proceed to Milestone 3 by hardening the theme-layer contracts around the already working homepage slice rather than introducing new page types first.
