# Backend Completion Note

## Scope

This note records the final major backend architecture pass completed after the homepage CMS vertical slice and before frontend-focused storefront implementation.

The CMS storefront remains opt-in. The public store stays Bagisto-native by default until `EXPERIENCE_CMS_STOREFRONT_MODE=cms` is enabled.

## Category Page Assignment Model

Category pages use `page_assignments` with deterministic precedence:

1. exact active category assignment with the highest priority
2. active global category-page assignment with the highest priority
3. native Bagisto category rendering when no CMS assignment exists

Current category page composition supports:

- `hero`
- `pre_listing`
- `post_listing`

The commerce core continues to own the actual category product listing query. The CMS controls composition around that listing plus listing presentation defaults stored in `pages.settings_json`.

## PDP Assignment Model

Product detail pages use the same `page_assignments` table and precedence model:

1. exact active product assignment with the highest priority
2. active global product-page assignment with the highest priority
3. native Bagisto product rendering when no CMS assignment exists

Current PDP composition supports these controlled block areas:

- `gallery`
- `summary`
- `details`
- `related`

Current seeded PDP blocks:

- product gallery
- product summary
- product price
- product options
- add to cart
- stock and shipping info
- product details
- FAQ
- related products
- trust badges

## Content Entry Usage Model

Content entries are active but intentionally narrow.

Current usage:

- reusable structured marketing or informational content
- selected through approved section data sources
- consumed by sections such as category intro and FAQ

Content entries are not a generic freeform page-builder escape hatch.

## Site Settings Usage Model

Site settings are active in shared render payload resolution.

Current usage:

- store identity
- contact details
- social links
- trust badges
- category page defaults
- product page defaults

Site settings are resolved by a dedicated service and passed into the theme layer as shared payload data.

## Version Restore Behavior

Version restore is deterministic.

When a restore runs:

1. the current authored state is snapshotted first
2. the selected snapshot is restored into the page record
3. SEO meta, sections, and nested components are restored from the snapshot

Restore does not rewrite shared definitions such as:

- menus
- header configs
- footer configs
- theme presets
- assignment records

This keeps restore page-scoped and undoable.

## Nested Component Authoring Status

Nested component authoring is now active in the admin page editor for section types that explicitly support components.

Current behavior:

- add nested components
- edit nested component settings
- remove nested components
- order nested components
- validate nested component settings against the component registry
- persist nested components in page snapshots

This remains form-driven and schema-backed. There is no drag-and-drop visual builder.

## What Frontend Can Safely Assume

Frontend work in `packages/theme-core` and `packages/theme-default` can now assume, once CMS storefront mode is enabled:

- homepage, category page, and PDP payload contracts are stable
- header, footer, menu, preset, site settings, and SEO metadata are consistently available in the render payload
- category pages receive structured hero, pre-listing, listing, and post-listing payload areas
- product pages receive structured gallery, summary, details, and related payload areas
- nested section components are available in the section payload where supported
- preview routes exist for homepage, category pages, and product pages
- Bagisto commerce data remains the source of truth for category listings and product details

## Remaining Work Before Frontend Polish And Customer Portal Completion

Still pending after this backend pass:

- final storefront UI implementation in the theme packages
- token-driven visual refinement and responsive polish
- Figma-driven storefront mapping once approved designs are provided
- customer portal page implementation against the shared theme-layer contracts
- richer merchandising sources beyond the current approved set
- version history diff or compare tooling

## Upstream/Core Touchpoints

No upstream core files were modified during this pass.

The main integration touchpoints are controller bindings around Bagisto-owned storefront routes when CMS storefront mode is enabled:

- CMS-aware home controller binding
- CMS-aware product/category proxy controller binding

This preserves upgrade safety while allowing CMS composition to wrap native commerce surfaces.
