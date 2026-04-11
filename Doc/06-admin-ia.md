# Admin Information Architecture

## Scope

The admin now exposes the minimum maintainable workflows required to manage the backend-complete CMS authoring model before frontend-focused implementation begins.

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
- Assignments
- Menus
- Header Configs
- Footer Configs
- Content Entries
- Site Settings

Implemented page workflow from the CMS area:

- create page
- edit page metadata
- assign template
- assign header config
- assign footer config
- assign menu
- assign theme preset
- edit page settings JSON for page-type-specific behavior
- manage page status
- preview draft content
- publish page
- unpublish page
- review version history
- restore a previous version
- manage nested section components

Implemented assignment workflow:

- create category page assignments
- create product page assignments
- set global fallback assignments
- set exact entity overrides
- preview assignment targets when the assignment is entity-scoped

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
- page settings JSON
- SEO fields
- ordered page sections
- ordered nested components within supported sections

Each section row includes:

- template area
- section type
- title
- sort order
- active toggle
- section settings JSON
- data source type
- data source payload JSON
- nested components when the section type supports them

Each nested component row includes:

- component type
- sort order
- active toggle
- component settings JSON
- component data source payload JSON

This keeps the CMS operable without introducing a drag-and-drop builder.

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

## Content Entries And Site Settings

Content entries are managed as reusable structured content records for approved CMS sections.

Site settings are managed as shared structured payload records for:

- store identity
- contact data
- social links
- trust badges
- category page defaults
- product page defaults

These screens are intentionally constrained. They are not general freeform page builders.

## Versions

The page edit screen now includes:

- version list
- version note visibility
- restore action

Restore semantics are limited to the selected page and its owned composition:

- page fields
- SEO meta
- sections
- nested components

Shared entities such as menus, header configs, footer configs, and theme presets are not rewritten by a restore action.

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
- assignments
- menus
- header configs
- footer configs
- content entries
- site settings

Theme ACL remains centered on preset management in the current slice.

Custom CMS and theme routes no longer rely only on route-name ACL matches. They now apply explicit permission middleware so the following route pairs share the same intended permission key:

- create and store
- edit and update
- edit and preview

This closes the gap where custom POST or PUT routes could otherwise miss ACL enforcement if their route names were not represented directly in the ACL tree.

Recommended role split before frontend work begins:

- Super Admin: full access
- Content Manager: pages, assignments, menus, header/footer, content entries
- Theme Manager: theme presets and site settings
- Catalog Manager: Bagisto catalog ownership only
- SEO Manager: SEO and metadata workflows when that surface is hardened further

## Current Limitations

- menu authoring is flat-form only
- version comparison and diff UX are not yet implemented
- customer portal page authoring is still deferred to a later workstream
