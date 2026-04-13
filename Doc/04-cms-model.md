# CMS Model

## Scope

The CMS now covers the backend-complete authoring model needed before frontend-focused implementation begins. The system remains structured and schema-backed.

Implemented authoring surfaces:

- homepage pages
- static and campaign pages
- category page layouts
- product detail page layouts
- content entries used by page composition
- site settings used by render payload resolution
- page version restore
- nested section component authoring

The public storefront uses these structures only when `EXPERIENCE_CMS_STOREFRONT_MODE=cms` is enabled. Native Bagisto storefront routing remains the default runtime mode.

## Persisted Entities

### Pages

`pages` store the authored page record and own the main workflow fields:

- `title`
- `slug`
- `type`
- `template_id`
- `status`
- `published_at`
- `seo_meta_id`
- `header_config_id`
- `footer_config_id`
- `menu_id`
- `theme_preset_id`
- `settings_json`

Current page statuses are:

- `draft`
- `published`

### Page Versions

`page_versions` capture snapshot history for publish, unpublish, and restore workflows. Each snapshot stores the authored page payload plus the structural composition state in `snapshot_json`:

- template
- template areas
- SEO meta
- header config
- footer config
- menu
- theme preset
- page settings
- page sections
- section components

The workflow now records snapshots for:

- publish
- unpublish
- restore pre-state, so a restore is itself undoable

### Templates And Areas

`templates` define the structured layout contract for a page type. `template_areas` are synchronized from the template schema and define where sections may be placed.

Current seeded templates:

- `homepage_default`
- `category_default`
- `product_default`

Current area structure:

- homepage
  - `hero`
  - `content`
- category page
  - `hero`
  - `pre_listing`
  - `post_listing`
- product page
  - `gallery`
  - `summary`
  - `details`
  - `related`

### Section Types

`section_types` are the approved building blocks that an admin can place into template areas. Each type declares:

- unique `code`
- `config_schema_json`
- `allowed_data_sources_json`
- `renderer_class`
- activation flag

The registry is authoritative for defaults, validation rules, preview support, and supported data source modes.

Built-in section types now active:

- `hero_banner`
- `promo_strip`
- `category_grid`
- `featured_products`
- `flash_sale_products`
- `best_sellers`
- `new_arrivals`
- `rich_text`
- `category_intro`
- `product_gallery`
- `product_summary`
- `product_price`
- `product_options`
- `add_to_cart`
- `stock_shipping_info`
- `product_details`
- `faq_block`
- `related_products`
- `trust_badges`

These are used across the seeded homepage, category page, and PDP layouts.

### Page Sections

`page_sections` belong to a page and a template area, and reference one approved section type. Each record stores:

- authored title
- sort order
- `settings_json`
- `visibility_rules_json`
- `data_source_type`
- `data_source_payload_json`
- activation flag

Validation is performed before persistence. The request layer checks that:

- the selected template area belongs to the selected template
- the section type exists and is active
- the chosen data source is allowed by the section definition
- section settings match the registry validation rules

### Component Types And Section Components

`component_types` store approved nested component definitions. `section_components` store per-section component instances.

Active nested component model:

- component type CRUD
- per-section nested component persistence
- nested component validation through the component registry
- nested component snapshot capture
- nested component authoring in the page editor
- nested component payload rendering in the theme layer

Current built-in component definitions:

- `headline`
- `body_text`
- `cta_button_group`
- `badge_list`
- `link_list`

### Navigation And Layout Assignments

The CMS now uses first-class layout assignments instead of hidden conventions:

- `menus` and `menu_items`
- `header_configs`
- `footer_configs`
- `theme_presets`
- `seo_meta`
- `site_settings`
- `page_assignments`
- `content_entries`

Pages can explicitly reference their active header, footer, menu, and theme preset.

`page_assignments` map a page layout to a commerce-aware surface:

- `page_type`
- `scope_type`
- `entity_type`
- `entity_id`
- `priority`
- `is_active`

Current supported assignment targets:

- global category page fallback
- exact category override
- global product page fallback
- exact product override

## Relationships

Key relationships in the current slice:

- `Page` belongs to `Template`
- `Page` belongs to `SeoMeta`
- `Page` belongs to `HeaderConfig`
- `Page` belongs to `FooterConfig`
- `Page` belongs to `Menu`
- `Page` belongs to `ThemePreset`
- `Page` has many `PageAssignment`
- `Page` has many `PageSection`
- `Page` has many `PageVersion`
- `Template` has many `TemplateArea`
- `PageSection` belongs to `TemplateArea`
- `PageSection` belongs to `SectionType`
- `PageSection` has many `SectionComponent`
- `SectionComponent` belongs to `ComponentType`
- `Menu` has many `MenuItem`
- `ContentEntry` is resolved into sections through explicit data-source payloads
- `SiteSetting` is resolved into shared render payloads through a dedicated resolver

## Validation Model

All authored JSON in the current slice is schema-backed at the request or registry level.

- Template schema is entered as JSON and synchronized into `template_areas`.
- Section settings are validated through the section registry.
- Data source payloads are validated against the supported source contract of the selected section type.
- Template area rules can restrict allowed section codes.
- Nested component settings are validated through the component registry.
- Page settings are validated by page type.
- SEO Open Graph payloads and menu item settings are decoded and normalized before save.

The CMS does not allow arbitrary layout logic or arbitrary JSON blobs to bypass section definitions.

Current supported content-aware data source modes:

- `featured_products`
- `best_sellers`
- `new_arrivals`
- `discounted_products`
- `category_products`
- `manual_products`
- `manual_categories`
- `selected_content_entries`

## Preview And Publish Workflow

### Preview

Preview is available from the admin page editor and from assignment records. Admin actions redirect to signed storefront preview URLs.

Preview characteristics:

- draft content is visible
- unpublished pages remain private
- the route is signed
- rendering flows through the same theme layer used by the published storefront routes
- category page preview requires a concrete category slug
- product page preview requires a concrete product slug

### Publish

Publish performs these steps:

1. capture a version snapshot
2. set page status to `published`
3. stamp `published_at`

### Unpublish

Unpublish performs these steps:

1. capture a version snapshot
2. return the page to `draft`
3. clear `published_at`

### Restore

Restore performs these steps:

1. capture the current authored state as a new version snapshot
2. restore the selected snapshot into the page, SEO meta, sections, and nested components
3. preserve shared assignment records and shared header/footer/menu/theme entities

Restore affects the selected page record and its owned composition only. It does not rewrite global shared definitions.

## Assignment Resolution

Assignment resolution is deterministic and reusable.

Current precedence:

1. exact active entity assignment with highest priority
2. active global assignment for the same page type with highest priority
3. no CMS assignment, so the storefront falls back to native commerce rendering

This model is shared by category pages and product pages and is designed to extend to future commerce-aware surfaces.

## Active Authoring Flows

The seeded homepage still proves the original Milestone 2 flow:

- one homepage page record
- one template
- one header config
- one footer config
- one menu
- one theme preset
- one hero banner section
- one featured products section backed by commerce data
- one rich text section
- preview and publish workflow

The backend completion pass adds two more active seeded flows:

- category page
  - one global category page assignment
  - promo strip before listing
  - structured intro block using a content entry
  - featured products after listing
- product page
  - one global PDP assignment
  - controlled gallery, summary, price, options, add-to-cart, stock, details, FAQ, related, and trust-badge blocks
  - FAQ block backed by a content entry
  - shipping note and trust badges backed by site settings

## Current Usage Boundaries

Content entries and site settings are active, but intentionally bounded:

- content entries are for reusable structured content snippets consumed by approved sections
- site settings are for shared storefront payload values such as store identity, contact data, trust badges, and page defaults
- neither is a freeform escape hatch for arbitrary layouts

## Remaining Backend Gaps

- customer portal pages are not yet moved into the same assignment model
- richer merchandising sources can still be added, but the resolver contract is already in place
- version history has restore support, but no diff view or compare screen yet
