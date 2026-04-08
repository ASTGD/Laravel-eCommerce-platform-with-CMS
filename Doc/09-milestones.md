# 09 Milestones

## Milestone 1: Foundation

Deliverables:

- Laravel application bootstrap
- neutral repository metadata
- package skeletons and provider wiring
- `Doc/` structure and initial architecture documents
- baseline admin shell and installation-ready local environment

Done when:

- project boots cleanly
- local packages autoload
- provider registration is stable
- documentation foundation exists
- admin navigation shell is available

## Milestone 2: CMS Core

Deliverables:

- CMS tables and models
- template and section type registry
- structured page composition
- page section CRUD
- draft, preview, publish, and version snapshot basics

Done when:

- admin can create a homepage
- admin can assign a template
- admin can add approved sections
- preview works before publish
- publish creates a version snapshot

## Milestone 3: Theme Core + Default Theme

Deliverables:

- theme contracts
- preset resolution
- default storefront theme
- section rendering pipeline
- global header and footer rendering

Done when:

- storefront renders from CMS data
- theme tokens apply predictably
- preset changes affect storefront without code edits

## Milestone 4: Commerce Integration

Deliverables:

- product and category data source support
- commerce-aware sections
- configurable PLP and PDP presentation regions
- merchandising hooks for content-driven pages

Done when:

- sections can resolve real catalog data
- homepage merchandising uses live product data
- category and product pages honor structured layout settings

## Milestone 5: Hardening

Deliverables:

- SEO model completion
- media abstraction
- audit logging
- role and permission hardening
- test coverage for key flows
- deployment and handoff documentation

Done when:

- install and deploy flow is documented
- core CMS flows are covered by tests
- preview/publish behavior is reliable
- the product is ready for client-operated standalone deployment

## Implementation Order

1. Foundation and docs
2. CMS data model and registry
3. Theme contracts and default theme
4. Header, footer, and menu flow
5. Product-aware sections and merchandising
6. SEO, media, audit, permissions, and tests
