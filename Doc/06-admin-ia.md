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

## Catalog Area

The product list remains Bagisto-native in structure, but now uses a parent-first hierarchy for configurable catalogs:

- top-level product rows show parent products only
- configurable variants are expanded under the parent row on demand
- variant search by SKU or name resolves back to the parent row in the list

This keeps the catalog list manageable without turning variants into a second top-level catalog.

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

## Checkout Configuration

The sales configuration area now includes the Bangladesh-specific checkout controls needed for this product install:

- `Configuration > Sales > Shipping Methods > Courier`
- `Configuration > Sales > Payment Methods > Payment Channel`
- `Configuration > Sales > Payment Methods > SSLCOMMERZ Gateway`
- `Configuration > Sales > Payment Methods > bKash`
- `Configuration > Sales > Payment Methods > Nagad`
- `Configuration > Sales > Payment Methods > Bank Card`

Current shipping behavior:

- `Courier` is a single carrier with two selectable storefront rates
- `Home Delivery`
- `Courier Pick-up`

Current payment behavior:

- `Payment Channel = Default` keeps the native Bagisto storefront methods
- `Payment Channel = Custom` switches the storefront to the curated Bangladesh set
- `Cash On Delivery` remains available in both modes when it is enabled
- the first online card gateway target is `SSLCOMMERZ`
- `bKash`, `Nagad`, and `Bank Card` are exposed as separate storefront choices backed by the shared SSLCOMMERZ credential block

## Payment Operations

The sales area now also exposes payment operations for external Bangladesh gateway traffic:

- `Sales > Payments`
- payment attempt detail
- manual payment reconciliation from the payment attempt detail screen
- manual payment reconciliation from the admin order view when the order is backed by an SSLCOMMERZ payment attempt
- refund history on the admin order view for SSLCOMMERZ-backed orders
- refund status refresh on the admin order view for pending or invalid SSLCOMMERZ refunds

The payment operations view is intended for support and fulfillment use, not storefront authoring. It exists so repeated callbacks, delayed gateway confirmations, and pending validations can be reviewed without inspecting logs directly.

Refund handling stays inside the native Bagisto order workflow:

- admin still starts refunds from the standard order refund action
- SSLCOMMERZ-backed refunds are sent to the gateway during that workflow, not after a separate manual export
- rejected gateway refunds stop the local refund from completing
- accepted or pending gateway refunds are written to `payment_refunds` and shown back on the order for later follow-up
