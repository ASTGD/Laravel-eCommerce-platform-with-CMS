# CMS Model

## Scope

The CMS is now a structured Website Studio for safe e-commerce website content and layout slots.

The admin-facing CMS Studio controls:

- header content and behavior
- footer content and behavior
- navigation menus
- homepage Hero content
- global website and SEO-oriented content settings

The CMS does not provide free-form visual editing for core commerce pages or transactional flows.

The following surfaces remain controlled by commerce, catalog, customer, checkout, and theme logic:

- product detail pages
- category listing pages
- cart
- checkout
- search results
- customer account pages
- order pages
- transactional commerce flows

Core rule:

- CMS edits content and safe layout slots.
- Theme controls visual structure and supported section/component contracts.
- Commerce modules control products, categories, pricing, inventory, checkout, customers, and orders.

## Admin Surface

`My Website > CMS` opens CMS Studio at `admin.cms.index`.

CMS Studio is a single editor workspace with:

- left local navigation
- center structured editor panel
- full-width preview panel

The implemented structured builders are:

- Header Builder
- Footer Builder
- Navigation Builder
- Homepage Hero Builder

`My Website > Site Settings` opens a separate website settings section at `admin.cms.settings.index`. Site Settings is not part of the CMS Studio local tab navigation.

The old standalone CMS admin CRUD screens for pages, templates, section types, component types, assignments, content entries, menus, header configs, footer configs, and raw site setting records have been removed from the admin UI. Their storage models and runtime services remain because they power storefront rendering, preview payloads, and future Studio workflows.

## Persisted Entities

The following persisted entities remain part of the runtime CMS foundation:

- `pages`
- `page_versions`
- `templates`
- `template_areas`
- `section_types`
- `page_sections`
- `component_types`
- `section_components`
- `menus`
- `menu_items`
- `header_configs`
- `footer_configs`
- `site_settings`
- `content_entries`
- `page_assignments`
- `seo_meta`

These entities are not exposed as standalone admin CRUD pages in the current Studio pass.

## Header And Footer Builders

Header and footer editing is now form-driven and structured.

Header Builder fields:

- logo upload
- existing logo path/URL stored internally
- announcement enabled
- announcement text
- announcement link
- navigation menu selector
- show search
- show account icon
- show cart icon
- sticky header

Footer Builder fields:

- footer logo upload
- existing logo path/URL stored internally
- footer description
- footer menu columns, each selecting a menu created in CMS Studio Navigation
- newsletter enabled
- newsletter heading
- newsletter text
- contact email
- contact phone
- social links
- copyright text

Header and footer values are stored internally in the existing JSON storage on `header_configs` and `footer_configs`. The admin no longer exposes raw `settings_json` textareas for these builders.

Navigation remains the place where admins create and edit reusable menus. Footer Builder does not create footer links directly; it selects up to four active saved menus as footer columns, with per-column display titles and sort order. Existing single `navigation.menu_id` settings remain readable as a backward-compatible one-column footer. If no footer columns or legacy footer menu are saved, storefront rendering falls back to active `footer` location menus or the active theme's safe fallback footer links.

## Homepage Sections

Homepage sections are structured and theme-supported.

CMS Studio now includes a Hero-only Homepage Builder slice. Admins can configure, preview, and save the homepage Hero. Everything below the Hero is rendered by the active storefront theme so the CMS does not expose unfinished or misleading homepage section controls.

The current structured editor supports:

- Hero

Hero is a business-friendly content contract rather than a theme layout. It stores `mode` (`static` or `slider`) and up to five slides in the existing `page_sections.settings_json` structure. Each slide can include an image, alt/title, headline, body, primary CTA, and optional secondary CTA. Legacy `hero_banner` and `hero_slider` records remain readable and are normalized into the Hero shape for the Studio and storefront runtime.

The active storefront theme owns presentation. Gadget renders the Hero through the Gadget hero design, Clothing renders the same content through the Clothing hero design, and the default package renderer provides a simple safe fallback.

Existing non-Hero homepage section records, such as catalog-aware sections, are preserved safely in storage but hidden from normal admin editing. Additional section-specific forms can be added only as future vertical slices when they are product-ready.

The section and component registries remain the authority for:

- allowed section/component codes
- default configuration
- validation rules
- supported data sources
- renderer contracts

## Internal Structured Content

`content_entries`, `pages`, and `site_settings` remain available as internal storage and runtime foundation. Normal admins do not create standalone pages or reusable content blocks through CMS Studio in the current client workflow.

Allowed CMS Studio content:

- homepage Hero content
- header content and behavior
- footer content and behavior
- navigation menus
- global metadata and shared storefront content

Disallowed CMS content:

- standalone page creation from CMS Studio
- reusable block creation from CMS Studio
- product page layout editing
- category listing layout editing
- checkout/cart/customer/order workflow editing

## Runtime Services Preserved

The admin cleanup does not remove runtime services.

Preserved runtime services include:

- `HeaderResolver`
- `FooterResolver`
- `MenuResolver`
- `SiteSettingsResolver`
- `ContentEntryResolver`
- `PagePreviewService`
- `StructuredPagePayloadBuilder`
- `CategoryPagePayloadBuilder`
- `ProductPagePayloadBuilder`
- publish and version restore services

These services continue to support the structured CMS and storefront integration layer. The current change only replaces the old admin CRUD UI with CMS Studio.

## Validation Model

CMS Studio must keep authored content schema-backed and form-driven.

Rules:

- normal admins should not edit raw JSON
- header/footer settings are saved into JSON storage internally
- homepage section editing validates predefined structured fields and stores them in existing `page_sections` JSON storage internally
- commerce-critical flows must not be edited through CMS Studio

## Current Limitations

- Navigation and homepage sections have structured Studio builders.
- Header and footer have structured save actions and admin preview mocks.
- Pages and Reusable Blocks are intentionally not exposed in CMS Studio for the current client workflow.
- Site settings are shown in a separate My Website section, not inside CMS Studio.
- Full draft/publish versioning for homepage section edits remains a future Studio hardening workstream.
- Product/category/cart/checkout/customer pages remain outside CMS Studio editing.
