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
3. Public storefront requests resolve through Bagisto routes/controllers and the active Bagisto shop theme.
4. Checkout resolves a single state contract for cart summary, customer draft data, the single-address form contract, district shipping, and payment methods.
5. Theme core resolves preset metadata, section rendering, and preview payloads for structured CMS surfaces.
6. Theme packages provide Bagisto shop theme view overrides and CMS preview Blade views.

## Runtime Ownership

The public site is Bagisto-native by default. Home, category, product, policy/CMS, cart, checkout, customer, and order routes keep the native Bagisto controller flow.

Storefront visual variation is applied through Bagisto shop themes selected on the current channel. The `default` shop theme renders native Bagisto views. The `gadget` shop theme is registered as a normal Bagisto theme and currently overrides only the homepage view, so category, product, policy/static, search, cart, checkout, customer, and order pages fall back to native Bagisto views until matching theme overrides are added.

`packages/experience-cms` keeps structured CMS authoring and signed preview routes. Those preview routes do not replace normal Bagisto storefront routes.

Bagisto remains the core runtime for:

- admin portal
- customer portal
- checkout, cart, and orders
- product, category, price, inventory, and promotion data
- payment, shipping, and order business rules

## Extension Strategy

- Prefer package service providers over app-level sprawl.
- Prefer configuration-driven variation over hardcoded per-client branching.
- Avoid editing upstream commerce packages unless there is a documented blocker.
- When an upstream override is unavoidable, record it in `Doc/`.
