# Theme System

## Scope

The backend-complete theme layer now supports homepage, category page, and product detail page rendering through one structured payload pipeline. The output remains intentionally plain, but the contracts are now stable enough for frontend implementation work.

## Principles

- one theme core
- one default theme
- configuration-driven variation
- no business logic in Blade templates
- no per-page ad hoc rendering branches

## Runtime Render Pipeline

The storefront now renders through these layers:

1. storefront route resolves the target surface
2. CMS-aware controllers or preview routes resolve the relevant `Page`
3. `PagePreviewService` delegates to the correct payload builder for homepage, category page, or product page
4. `StructuredPagePayloadBuilder` composes the common shell payload
5. commerce-aware builders add category listing or PDP-specific payload data
6. `DataSourceResolverContract`, `ContentEntryResolverContract`, and `SiteSettingsResolverContract` resolve structured content dependencies
7. header, footer, menu, and preset resolvers normalize shared presentation assignments
8. section and nested component payloads are rendered through `theme-default` views

This same pipeline is used for:

- published homepage rendering
- published category page rendering
- published product page rendering
- signed preview rendering
- explicit CMS page rendering routes

## Upstream Commerce Touchpoints

The storefront root route still belongs to the commerce core. The CMS homepage is injected by binding the upstream shop home controller to a CMS-aware wrapper that:

- checks for a published CMS page with slug `home`
- renders the page through the CMS preview service when present
- falls back to the upstream commerce homepage when no published CMS homepage exists

This keeps the Bagisto storefront route in place while allowing the product homepage to become CMS-driven.

Category and product route ownership also stays with the commerce core. The CMS integrates by binding the upstream product/category proxy controller to a CMS-aware wrapper that:

- checks for a matching category or product
- resolves an active page assignment
- renders the CMS-composed category or PDP layout when an assignment exists
- falls back to the native Bagisto commerce surface when no assignment exists

No upstream core files are modified. The integration point is controller binding plus commerce repository usage.

## Theme Assignments And Shared Payload

Pages can now assign:

- one theme preset
- one header config
- one footer config
- one menu

These are persisted on the page and resolved by dedicated services rather than hardcoded view logic.

Shared render payload also includes resolved site settings so storefront surfaces can consume:

- store identity
- contact data
- social links
- trust badges
- page-level defaults for category and product surfaces

## Preset Model

Theme presets remain configuration-driven. The current slice resolves tokens and settings from `theme_presets` and passes the selected preset into the page view model.

Current preset responsibilities:

- preset code selection
- token payload resolution
- default/fallback preset handling

Future frontend work can expand this into stronger token usage for product cards, category lists, account surfaces, and richer component styling without changing the page composition model.

## Section And Component Rendering

Current section rendering follows a stable contract:

- section type definition provides defaults and validation
- authored section settings are merged with registry defaults
- supported data source output is attached to the section payload
- the view renders only prepared payload data
- nested components are validated, normalized, and rendered from prepared payloads

The current default theme includes explicit section views for:

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

The current default theme includes explicit nested component views for:

- headline
- body text
- CTA button group
- badge list
- link list

## Header, Footer, And Menu Rendering

The render path now uses dedicated services for global areas:

- `MenuResolver`
- `HeaderResolver`
- `FooterResolver`

The header partial prefers resolved menu items and only falls back to static link settings when no menu is assigned.

Category and product templates receive the same shared shell payload as the homepage, so frontend implementation can assume one header/footer/menu/preset model across storefront surfaces.

## Commerce-Aware Payload Builders

The theme layer now receives stable payload shapes from dedicated backend services:

- `StructuredPagePayloadBuilder`
- `CategoryPagePayloadBuilder`
- `ProductPagePayloadBuilder`

Current category-specific payload areas:

- `heroSections`
- `preListingSections`
- `listing`
- `postListingSections`
- `category`

Current product-specific payload areas:

- `gallerySections`
- `summarySections`
- `detailsSections`
- `relatedSections`
- `product`
- `productData`

## SEO Output

The storefront layout now renders CMS-owned SEO fields where available:

- SEO title
- meta description
- canonical URL

This is enough for the current homepage slice and keeps the view layer aligned with persisted CMS metadata.

## Asset Loading Rule

The custom storefront layout now targets the root application Vite build directory explicitly instead of relying on the Bagisto theme Vite singleton state. This avoids runtime manifest collisions between Bagisto theme assets and the custom storefront shell.

## Remaining Gaps Before Frontend Polish

- the default theme is still intentionally minimal
- preset tokens are resolved but not yet exhaustively applied across all storefront surfaces
- category and PDP payloads are stable, but the final visual implementation is still pending
- customer account pages are not yet moved onto the same structured theme implementation
