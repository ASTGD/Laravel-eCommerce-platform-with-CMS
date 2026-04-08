# 01 Architecture

## Architectural Style

The platform is implemented as a modular monolith. Runtime stays within one Laravel application, while product boundaries are expressed through local packages and explicit contracts.

Primary characteristics:

- package-first customization
- structured CMS composition
- configuration-driven theme rendering
- server-rendered storefront delivery
- clear installation isolation at infrastructure and data layers

## Package Responsibilities

### `packages/commerce-core`

- commerce domain service boundaries
- catalog retrieval contracts
- product and category merchandising integration hooks
- pricing, inventory, cart, checkout, and order integration seams

### `packages/experience-cms`

- pages and templates
- section and component catalogs
- page composition and preview/publish workflows
- menu, header, footer, and content entry administration
- admin CRUD for structured CMS entities

### `packages/theme-core`

- renderer contracts
- theme preset resolution
- data source resolution contracts
- theme token and layout support services

### `packages/theme-default`

- baseline storefront implementation
- shared storefront layout
- default section renderers
- default header and footer rendering

### `packages/seo-tools`

- SEO metadata storage
- canonical and social metadata hooks
- future redirect and URL rewrite support

### `packages/media-tools`

- media abstraction seams
- upload rule home
- responsive media support points

### `packages/platform-support`

- shared enums
- audit logging
- admin bootstrap support
- install-time helpers and cross-package primitives

## Layering Rules

1. `platform-support` exposes cross-cutting primitives only.
2. `theme-core` depends on contracts and stable CMS data, not on ad hoc view state.
3. `theme-default` implements presentation against `theme-core` contracts.
4. `experience-cms` owns page composition and can call theme contracts, but not hardcode theme internals.
5. `commerce-core` exposes catalog-oriented services that sections can consume through resolver contracts.

## Data Flow

### Admin authoring flow

1. Admin edits a page, template selection, and structured sections.
2. Section settings are validated against the registered section schema.
3. Draft data is stored in normalized tables and JSON-backed settings columns.
4. Publish creates a version snapshot and updates publish state timestamps.

### Storefront rendering flow

1. Request resolves a page by route context or page type.
2. CMS composes the active page with ordered sections.
3. Theme preset resolver determines active tokens and layout settings.
4. Section renderer resolves section definitions, normalized settings, and data source payloads.
5. Theme views render the final storefront response.

## Repository Boundaries

Top-level application code remains lean:

- authentication
- middleware
- root configuration
- framework bootstrap

Business capabilities live in packages. This reduces upgrade risk and keeps future extraction or package versioning realistic.

## Upgrade Safety

- Do not patch framework internals.
- Prefer provider registration and local package autoloading.
- Document any unavoidable framework touchpoints in `Doc/`.
- Keep theme presets and section schemas declarative wherever possible.

## Current Foundation Decisions

- Laravel is used as the hosting application and dependency container.
- Public storefront pages are server-rendered.
- Admin uses standard Laravel routing, controllers, requests, and Blade views.
- Package namespaces and folder names stay neutral and product-oriented.
