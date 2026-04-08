# Architecture

## Structural Principle

Use Bagisto for commerce primitives and admin baseline. Build structured CMS, theme, SEO/media enhancements, and product-specific support tooling as isolated custom packages.

## Package Boundaries

### `packages/commerce-core`

- commerce integration layer
- data-source resolution for commerce-aware sections
- merchandising query services
- storefront-facing commerce abstractions

### `packages/experience-cms`

- pages
- templates and template areas
- sections and components
- preview/publish workflow
- menus
- header/footer config
- site settings

### `packages/theme-core`

- rendering contracts
- theme preset resolution
- section/component rendering pipeline
- shared theme helpers

### `packages/theme-default`

- default storefront layouts
- section views
- page rendering templates
- customer portal presentation layer

### `packages/seo-tools`

- SEO metadata
- canonical support
- redirects and rewrite helpers later

### `packages/media-tools`

- media abstraction hooks
- CMS/storefront media rules

### `packages/platform-support`

- audit logging
- shared support services
- internal install/setup utilities later

## Data Flow

1. Admin manages structured CMS records.
2. CMS stores schema-backed configuration in package-owned tables.
3. Storefront page services resolve template, sections, and data sources.
4. Theme core resolves active preset and section rendering.
5. Theme default renders the page using server-side Blade views.

## Extension Strategy

- Prefer package service providers over app-level sprawl.
- Prefer configuration-driven variation over hardcoded per-client branching.
- Avoid editing upstream commerce packages unless there is a documented blocker.
- When an upstream override is unavoidable, record it in `Doc/`.
