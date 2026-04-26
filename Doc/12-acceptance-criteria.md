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

## Backend Completion Pass Acceptance

The backend completion pass is complete when all of the following are true:

1. Category pages can resolve CMS assignments with deterministic precedence and still keep native commerce listing ownership in the commerce core.
2. Product detail pages can resolve CMS assignments with controlled block composition while keeping native commerce product ownership in the commerce core.
3. Category and product page previews are available through signed routes.
4. Content entries are active in approved CMS section data sources.
5. Site settings are active in shared storefront payload resolution.
6. Nested section components can be authored, validated, persisted, and rendered.
7. Page versions can be listed and restored with deterministic restore semantics.
8. Homepage rendering continues to work after category and PDP support is added.
9. Published homepage, category page, and product page routes all render through the CMS/theme layer without bypassing the commerce core.
10. Focused tests cover assignment resolution, render smoke checks, nested component validation, content entry resolution, site settings resolution, restore behavior, and homepage regression.

## Backend Completion Pass Status

Current status: complete and validated.

Remaining items after this pass:

- storefront visual implementation and polish
- customer portal theme implementation
- richer customer-facing surfaces and merchandising presentation
- diff/compare tooling for version history

## Unified Affiliate MVP Acceptance

The affiliate MVP is acceptable when all of the following are true:

1. One Bagisto customer maps to one affiliate profile.
2. Customers can apply from the existing customer account portal.
3. Pending, rejected, suspended, and active states are canonical and shared between admin and customer portal.
4. Admin can create an affiliate profile directly for an existing customer account.
5. Admin can approve, reject, suspend, reactivate, and regenerate referral codes from the admin `Affiliates` area.
6. The storefront has a public affiliate-program entry page that sends guests to login/register and logged-in customers to the account affiliate flow.
7. Active affiliates see the customer Affiliate dashboard; non-active affiliates see only their relevant state.
8. Active affiliates can copy their referral code/link and generate simple tracked links for internal storefront paths.
9. Referral links create click records for reporting and attribution context only.
10. Referral links remain valid while the affiliate is active and the code is current; attribution expires by the cookie window.
11. Attributed orders create order-based commission records.
12. Payout availability is derived from approved commissions and payout allocation rows.
13. Customer withdrawal requests and admin payout records use the same payout lifecycle.
14. Admin reports read totals from the shared affiliate tables.
15. Affiliate settings are shared by admin and customer portal services.
16. Email Affiliate, Banner/Text Ad management, per-click payouts, and separate affiliate login are absent from v1 routes and navigation.
17. Feature tests cover the full customer/admin/referral/commission/payout/report workflow.

Current status: Phase 8 hardening tests cover the listed MVP workflow and exclusion requirements.
