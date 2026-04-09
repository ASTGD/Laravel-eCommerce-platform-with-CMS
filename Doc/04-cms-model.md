# CMS Model

## Scope

Milestone 2 delivers a structured CMS vertical slice for the homepage. Admin users can manage pages, templates, sections, menus, header/footer configs, component types, and theme preset assignments without introducing a freeform page builder.

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

Current page statuses are:

- `draft`
- `published`

### Page Versions

`page_versions` capture publish workflow snapshots. Each snapshot stores the authored page payload plus the resolved structural assignments in `snapshot_json`:

- template
- template areas
- SEO meta
- header config
- footer config
- menu
- theme preset
- page sections
- section components

The publish workflow writes a snapshot on both publish and unpublish transitions so page history remains auditable.

### Templates And Areas

`templates` define the structured layout contract for a page type. `template_areas` are synchronized from the template schema and define where sections may be placed.

The current homepage slice ships with `homepage_default` and these areas:

- `hero`
- `content`

### Section Types

`section_types` are the approved building blocks that an admin can place into template areas. Each type declares:

- unique `code`
- `config_schema_json`
- `allowed_data_sources_json`
- `renderer_class`
- activation flag

The registry is authoritative for defaults, validation rules, preview support, and supported data source modes.

Built-in section types for the current slice:

- `hero_banner`
- `promo_strip`
- `category_grid`
- `featured_products`
- `flash_sale_products`
- `best_sellers`
- `new_arrivals`
- `rich_text`

Only `hero_banner`, `featured_products`, and `rich_text` are used in the seeded homepage flow.

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

Milestone 2 includes:

- component type CRUD
- component persistence in the schema and relationships
- component snapshot capture
- generic component preview rendering support

The admin page editor does not yet expose nested component composition. That becomes relevant in later milestones when richer section internals are introduced.

### Navigation And Layout Assignments

The homepage vertical slice uses first-class assignments instead of hidden conventions:

- `menus` and `menu_items`
- `header_configs`
- `footer_configs`
- `theme_presets`
- `seo_meta`
- `site_settings`

Pages can now explicitly reference their active header, footer, menu, and theme preset.

## Relationships

Key relationships in the current slice:

- `Page` belongs to `Template`
- `Page` belongs to `SeoMeta`
- `Page` belongs to `HeaderConfig`
- `Page` belongs to `FooterConfig`
- `Page` belongs to `Menu`
- `Page` belongs to `ThemePreset`
- `Page` has many `PageSection`
- `Page` has many `PageVersion`
- `Template` has many `TemplateArea`
- `PageSection` belongs to `TemplateArea`
- `PageSection` belongs to `SectionType`
- `PageSection` has many `SectionComponent`
- `SectionComponent` belongs to `ComponentType`
- `Menu` has many `MenuItem`

## Validation Model

All authored JSON in the current slice is schema-backed at the request or registry level.

- Template schema is entered as JSON and synchronized into `template_areas`.
- Section settings are validated through the section registry.
- Data source payloads are validated against the supported source contract of the selected section type.
- SEO Open Graph payloads and menu item settings are decoded and normalized before save.

The CMS does not allow arbitrary layout logic or arbitrary JSON blobs to bypass section definitions.

## Preview And Publish Workflow

### Preview

Preview is available from the admin page editor. The admin action redirects to a signed storefront preview URL.

Preview characteristics:

- draft content is visible
- unpublished pages remain private
- the route is signed
- rendering flows through the same theme layer used by the published homepage

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

## Current Vertical Slice

The seeded homepage proves the end-to-end model:

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

## Limitations Before Category And PDP CMS

- category listing pages are not yet composed through CMS templates
- product detail pages are not yet composed through controlled PDP blocks
- nested section component authoring UI is not yet exposed in admin
- content entries are not yet wired into page composition workflows
- page version restore UX is not yet implemented
