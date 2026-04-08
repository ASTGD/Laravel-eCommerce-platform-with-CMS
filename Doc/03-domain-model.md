# Domain Model

## Ownership Boundary

### Commerce Foundation Owns

- products
- categories
- attributes and options
- pricing
- inventory
- customers
- cart
- checkout
- orders
- promotions

### Custom Platform Packages Own

- pages
- templates
- sections
- components
- menus
- header/footer configuration
- theme presets
- theme token sets
- site settings
- SEO metadata extensions
- audit logging

## Core CMS Entities

- `pages`
- `page_versions`
- `templates`
- `template_areas`
- `section_types`
- `page_sections`
- `component_types`
- `section_components`
- `content_entries`
- `menus`
- `menu_items`
- `header_configs`
- `footer_configs`
- `site_settings`

## Shared Platform Entities

- `theme_presets`
- `theme_token_sets`
- `seo_meta`
- `audit_logs`

## Relationship Summary

- A page belongs to a template.
- A template has many template areas.
- A page has many sections.
- A section belongs to a section type and may have components.
- A page may have many versions.
- Menus have nested items.
- Header/footer configs and theme presets are resolved globally or per page later.
