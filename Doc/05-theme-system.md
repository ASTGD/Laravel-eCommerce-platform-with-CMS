# Theme System

## Scope

Milestone 2 completes the minimum theme-driven rendering path required for a CMS-authored homepage. The output is intentionally plain, but the runtime path is structured and reusable.

## Principles

- one theme core
- one default theme
- configuration-driven variation
- no business logic in Blade templates
- no per-page ad hoc rendering branches

## Runtime Render Pipeline

The homepage now renders through these layers:

1. storefront route resolves the target page
2. `PagePreviewService` loads the page, sections, assignments, and SEO data
3. `DataSourceResolverContract` resolves commerce-backed section data where needed
4. theme preset, header, footer, and menu resolvers normalize presentation assignments
5. section payloads are built from validated settings plus resolved data
6. `theme-default` Blade views render the final page

This same pipeline is used for:

- published homepage rendering
- signed preview rendering
- explicit CMS page rendering routes

## Upstream Commerce Touchpoint

The storefront root route still belongs to the commerce core. The CMS homepage is injected by binding the upstream shop home controller to a CMS-aware wrapper that:

- checks for a published CMS page with slug `home`
- renders the page through the CMS preview service when present
- falls back to the upstream commerce homepage when no published CMS homepage exists

This keeps the Bagisto storefront route in place while allowing the product homepage to become CMS-driven.

## Theme Assignments

Pages can now assign:

- one theme preset
- one header config
- one footer config
- one menu

These are persisted on the page and resolved by dedicated services rather than hardcoded view logic.

## Preset Model

Theme presets remain configuration-driven. The current slice resolves tokens and settings from `theme_presets` and passes the selected preset into the page view model.

Current preset responsibilities:

- preset code selection
- token payload resolution
- default/fallback preset handling

Future milestones can expand this into stronger token usage for product cards, category lists, and account surfaces without changing the page composition model.

## Section Rendering

Current section rendering follows a stable contract:

- section type definition provides defaults and validation
- authored section settings are merged with registry defaults
- supported data source output is attached to the section payload
- the view renders only prepared payload data

The current default theme includes explicit section views for:

- hero banner
- featured products
- rich text
- generic section fallback

Generic component output is also supported for preview-safe nested component rendering.

## Header, Footer, And Menu Rendering

The render path now uses dedicated services for global areas:

- `MenuResolver`
- `HeaderResolver`
- `FooterResolver`

The header partial prefers resolved menu items and only falls back to static link settings when no menu is assigned.

## SEO Output

The storefront layout now renders CMS-owned SEO fields where available:

- SEO title
- meta description
- canonical URL

This is enough for the current homepage slice and keeps the view layer aligned with persisted CMS metadata.

## Limitations Before Milestone 3 And 4

- the default theme is still intentionally minimal
- preset tokens are resolved but not yet exhaustively applied across all commerce surfaces
- category page variants and PDP block rendering are not yet implemented
- section-specific view models can be expanded further once more section types are activated
