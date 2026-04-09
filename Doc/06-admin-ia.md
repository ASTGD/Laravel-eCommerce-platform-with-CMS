# Admin Information Architecture

## Scope

Milestone 2 implements the minimum maintainable admin workflows needed to author and manage a structured homepage vertical slice.

## Navigation

Relevant admin areas now exposed:

- Dashboard
- Catalog
- Sales
- Customers
- Promotions
- CMS
- Theme
- SEO
- Media
- Settings
- Users & Roles
- Audit Logs

## CMS Area

Implemented CMS screens:

- Pages
- Templates
- Section Types
- Component Types
- Menus
- Header Configs
- Footer Configs

Implemented page workflow from the CMS area:

- create page
- edit page metadata
- assign template
- assign header config
- assign footer config
- assign menu
- assign theme preset
- manage page status
- preview draft content
- publish page
- unpublish page

## Theme Area

Implemented Theme screen:

- Presets

Theme presets are still minimal in Milestone 2, but the admin CRUD is live and the selected preset is resolved by the storefront render path.

## Page Editing Workflow

The page editor is intentionally form-driven rather than builder-driven.

The editor currently supports:

- title
- slug
- page type
- template selection
- header assignment
- footer assignment
- menu assignment
- theme preset assignment
- SEO fields
- ordered page sections

Each section row includes:

- template area
- section type
- title
- sort order
- active toggle
- section settings JSON
- data source type
- data source payload JSON

This keeps the slice operable without introducing a drag-and-drop builder.

## Template Editing Workflow

Templates are managed through:

- template name and code
- page type
- schema JSON
- active toggle

Template areas are synchronized from the schema so authored pages always target persisted template areas instead of freeform strings.

## Menu Editing Workflow

Menus are managed with:

- menu metadata
- location
- active toggle
- flat item authoring rows

Nested tree editing is not yet implemented. Parent relationships already exist in the schema for future expansion.

## ACL

CMS ACL entries now cover:

- pages
- pages create
- pages edit
- pages publish
- pages unpublish
- templates
- template create/edit
- section types
- component types
- menus
- header configs
- footer configs

Theme ACL remains centered on preset management in the current slice.

## Current Limitations

- page editing does not yet expose nested section component authoring
- menu authoring is flat-form only
- content entries and site settings do not yet have admin screens
- category page and PDP configuration screens are intentionally deferred to later milestones
