# Acceptance Criteria

## Product-Level Criteria

The product is acceptable when all of the following are true:

1. Laravel 12 is the framework baseline.
2. Bagisto is the commerce foundation.
3. A fresh install can be deployed independently with its own DB and config.
4. Custom code is isolated in neutral packages.
5. Admin can manage structured CMS pages from approved definitions.
6. Header, footer, menus, and theme presets are configurable.
7. Commerce-aware sections render real catalog data.
8. The customer account area is storefront-native.
9. Documentation in `Doc/` matches implementation reality.
10. Upstream core modifications are minimized and documented.

## Milestone 2 Acceptance

Milestone 2 is complete when all of the following are true:

1. Admin can create a page and assign a template.
2. Admin can manage ordered sections for the page inside approved template areas.
3. Section settings and data source payloads are validated against structured definitions.
4. Admin can assign header, footer, menu, SEO meta, and theme preset to the page.
5. Admin can preview draft content through a signed storefront preview route.
6. Admin can publish and unpublish the page, and each transition records a page version snapshot.
7. A published homepage can resolve through the storefront root route without bypassing the commerce core.
8. The homepage can render at least one live commerce-backed section.
9. Minimal CRUD screens exist for pages, templates, section types, component types, menus, header configs, footer configs, and theme presets.
10. Focused CMS tests cover relationships, preview, publish workflow, storefront rendering, and commerce data resolution.

## Milestone 2 Status

Current status: complete for the homepage vertical slice.

Still deferred beyond Milestone 2:

- category page CMS
- PDP block composition
- nested section component authoring UI
- content entry-driven page composition
- version restore UX
