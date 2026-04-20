# Milestones

## Milestone 1: Correct Foundation

Status: verified complete.

Verified deliverables:

- Bagisto-based Laravel 12 source tree is in the repository
- neutral package boundaries are present and autoloaded
- `composer.lock` is synchronized with `composer.json`
- `composer install` and package discovery were verified on PHP `8.3.30`
- local install and boot were verified on MySQL `8.4` and Redis `7`
- the root Tailwind/Vite pipeline for the custom storefront shell is wired and built
- Docker/Sail is a valid dev path because `laravel/sail` is installed
- baseline docs and the foundation verification report are written

## Milestone 2: CMS Core Vertical Slice

Status: implemented and validated.

Validated deliverables:

- core CMS entities are persisted with working relationships
- pages can assign templates, header configs, footer configs, menus, SEO meta, and theme presets
- page sections are authored as approved structured records
- template areas are synchronized from template schema
- section type and component type registries are active
- publish and unpublish record page version snapshots
- signed preview works for draft content
- published homepage rendering flows through the theme layer
- featured products resolve from live commerce data through the commerce integration layer
- admin CRUD screens exist for pages, templates, section types, component types, menus, header configs, footer configs, and theme presets
- homepage seed proves one working end-to-end vertical slice

Validation completed during this milestone:

- `composer dump-autoload`
- `php artisan package:discover --ansi`
- `php artisan migrate --force`
- `php artisan db:seed --force`
- focused CMS feature tests
- live HTTP checks for published homepage, signed homepage preview, admin login, and customer login

## Backend Completion Pass: Commerce-Aware CMS And Frontend Readiness

Status: implemented and validated.

Validated deliverables:

- deterministic page assignment resolution exists for category pages and product pages
- category pages can resolve a CMS layout around the native commerce listing flow
- product detail pages can resolve controlled CMS blocks around native commerce product data
- content entries are active in page composition through approved data sources
- site settings are active in shared storefront payload resolution
- nested section components can be authored, validated, persisted, rendered, and snapshotted
- page version history can be restored deterministically
- published and preview homepage, category page, and product page routes render through the CMS/theme layer
- the layout asset path is stable under Bagisto theming middleware in live HTTP requests

Validation completed during this pass:

- `php artisan package:discover --ansi`
- `php artisan migrate --force`
- `php artisan db:seed --force`
- focused CMS feature tests for relationships, admin workflows, assignments, preview, restore, homepage render, category render, PDP render, nested component validation, content entry resolution, and site settings resolution
- live HTTP checks for published homepage, published category page, published product page, signed homepage preview, signed category preview, signed product preview, admin login, and customer login

## Milestone 3: Frontend Theme Implementation

Next target.

Planned deliverables:

- implement approved storefront UI in `packages/theme-core` and `packages/theme-default`
- map the stable backend payload contracts into production storefront views
- apply preset tokens and variants consistently across homepage, category page, and PDP
- harden accessibility and responsive behavior across the custom storefront
- keep the Bagisto admin mostly native while extending only the required CMS screens

## Pre-Frontend Hardening Track

Optional hardening work that can run before or alongside Milestone 3 is tracked in [Doc/17-backend-pre-frontend-checklist.md](/Users/shafin/Documents/Laravel-eCommerce-platform-with-CMS/Doc/17-backend-pre-frontend-checklist.md).

This track should remain limited to:

- ACL hardening
- regression coverage
- dev workflow cleanup
- runtime verification
- SEO and media hardening

It should not reopen the now-stable CMS architecture.

## Milestone 4: Customer Portal And Remaining Storefront Surfaces

Planned deliverables:

- storefront account area completion
- customer page styling aligned with the active theme system
- richer merchandising sources where needed
- additional structured content page parity

## Milestone 5: Hardening

Planned deliverables:

- ACL refinement
- SEO/media admin hardening
- broader automated coverage
- deployment hardening

## Next Recommended Milestone

Proceed to Milestone 3 while preserving the current backend-complete CMS model as the reference path for:

- structured page composition
- preview/publish workflow
- assignment resolution
- homepage resolution
- category page resolution
- product page resolution
- theme assignment resolution
- shared site settings and content entry usage

## Bangladesh Payment Sequence Note

The Bangladesh payment work should proceed in this order:

1. finish the SSLCommerz operational slice completely
   - reconciliation
   - refund workflow
   - production hardening
2. start direct `bKash` as a separate payment provider
3. defer direct `Nagad` until after the direct `bKash` stage is stable

Current status:

- the SSLCommerz operational slice is complete enough for checkout, reconciliation, and refunds
- the direct `bKash` payment slice is now active with direct checkout initiation, callback finalization, payment reconciliation, and refunds
- one final review pass remains for the `bKash` and `SSLCommerz` payment methods before this workstream is considered closed
- direct `Nagad` is still deferred

## Checkout Contract Note

The checkout workstream now has a dedicated single-state contract for the future theme layer.

Current status:

- single-screen checkout flow is in place at the core level
- district-based shipping is automatic from the selected address district
- the checkout state payload now carries cart summary, customer draft data, the single-address form contract, district shipping rules, and payment methods as one contract
- the visible checkout form is now reduced to Name, Mobile Number, Country/Region, District/Region, Full Address, and Email, with guest create-account support declared in the contract
- city and postcode remain hidden compatibility fields until the theme layer replaces the current checkout presentation entirely
- the checkout page shell is now moving into the default theme layer so the screenshot-style layout can be rendered without changing the core flow contract
- checkout mode routing is now being split so Bagisto native full checkout and the custom one-page checkout can coexist behind an admin-selected mode switch

## Order Status Workflow Note

The first custom order-status slice is now active.

Current status progression for physical-order operations:

- new order: `Pending`
- admin confirmation: `Processing`
- full shipment creation: `Shipped`

Current implementation notes:

- Bagisto native shipment creation no longer acts as the first transition out of `Pending`
- payment success paths no longer auto-promote supported orders to `Processing`
- admin order confirmation is now the explicit gate before shipment
- later shipment sub-stages and courier workflow detail are still deferred to a dedicated shipment slice

## Shipment And COD Workstream Note

The shipment and COD domain workstream is now active.

Current status:

- the temporary execution plan is documented in [Doc/TEMP-shipment-cod-settlement-plan.md](/Users/shafin/Documents/Laravel-eCommerce-platform-with-CMS/Doc/TEMP-shipment-cod-settlement-plan.md)
- the first implementation slice is `Sales > Carriers`
- the carrier registry slice is now implemented in `packages/commerce-core`
- the `Shipment Ops` slice is now active as the custom operational shipment layer
- native Bagisto shipment creation now syncs into custom shipment records and timeline events
- shipment timelines now support manual operational events without forcing every update through a pure status-change form
- shipment exception handling now supports:
  - structured failure reasons
  - delivery-attempt counts
  - reattempt approval
  - return-to-origin initiation and completion
- the first customer-facing shipment consumer is now active on customer order detail as a read-only shipment tracking timeline
- a guarded public shipment-tracking page is now active for guest lookup by order/tracking reference plus phone number
- customer and public tracking views now support carrier tracking links from the carrier registry tracking URL template
- public shipment-tracking entry links are now exposed from checkout success and storefront footer surfaces
- the courier API integration foundation slice is now active:
  - carriers can store tracking integration driver and API credential placeholders
  - shipment ops can record last sync state/message and trigger a manual sync action
  - queue job and CLI command foundations now exist for later real courier adapters
- the first live courier adapter slice is now active:
  - `Steadfast` tracking sync can call a real courier endpoint and map external statuses into internal shipment ops states
  - non-integrated carriers still remain on the placeholder provider until their dedicated adapter slices are implemented
- the first courier webhook slice is now active:
  - `Steadfast` carriers now have a dedicated webhook callback route guarded by the carrier webhook secret
  - webhook status updates now flow through the same shipment status pipeline and remain duplicate-safe for repeated callbacks
  - invoice fallback is supported when a tracking number is not present in the webhook payload
- the booking-reference persistence slice is now active:
  - shipment records can now persist carrier booking reference, consignment id, invoice reference, and booked-at timestamp
  - Shipment Ops includes an admin-only booking-reference form so persisted courier ids can be managed before automated booking is implemented
  - Steadfast webhook matching can now resolve shipment records by stored `consignment_id`
- the first live courier booking slice is now active:
  - Shipment Ops can create a `Steadfast` booking directly from admin using the configured carrier API credentials
  - successful courier booking now persists consignment id, invoice reference, booked-at, and tracking code back onto the shipment record
  - booking capture is logged as an operational event without triggering customer shipment notifications
- dedicated carrier integration notes now live in [Doc/18-shipment-carrier-integrations.md](/Users/shafin/Documents/Laravel-eCommerce-platform-with-CMS/Doc/18-shipment-carrier-integrations.md)
- the Pathao booking adapter slice is now active:
  - `Pathao` can create bookings through the merchant API contract using the carrier's stored API credentials and merchant store ID
  - booking resolves city, zone, and area through the merchant API lookup flow before creating the order
  - booking stores the returned consignment ID and logs a non-notifying operational event
- the Pathao tracking adapter slice is now active:
  - `Pathao` can query the merchant order lookup flow using the stored consignment ID or tracking number
  - Pathao tracking responses are mapped into the same downgrade-safe shipment status pipeline used by Steadfast
  - Pathao tracking sync remains duplicate-safe and non-destructive when the carrier returns an earlier status
- the Pathao webhook adapter slice is now active:
  - `Pathao` webhook callbacks can update shipment records through the same shipment status pipeline used by tracking sync
  - webhook callbacks are verified with the configured carrier secret and use a Pathao-specific response handshake header
  - duplicate webhook callbacks remain duplicate-safe when the mapped status already matches the shipment record
- the shipment notifications and communications slice is now active:
  - Shipment Ops events can queue customer/admin operational shipment emails
  - shipment detail now shows queued / skipped / failed communication audit rows
  - sales configuration now controls operational shipment email toggles
- the courier settlement import and automated sync slice is now active:
  - `Sales > Settlement Batches` now supports courier remittance CSV import
  - CSV import creates one settlement batch and auto-syncs linked COD settlement states
  - CLI import command `platform:cod-settlements:import` now supports automated operator/cron runs
- the `COD Settlements` slice is now active as the first money-operations layer for COD shipments
- COD shipment records now auto-create one admin settlement row per shipment record
- the manual COD reconciliation hardening slice is now active:
  - invalid finance-state transitions are blocked
  - outstanding and attention visibility is exposed on settlement and batch screens
  - disputed and short-settlement cases are surfaced more clearly for operators
- the `Settlement Batches` slice is now active as the courier payout grouping layer above COD settlements
- many COD settlements can now be grouped into one payout batch with item-level short-settlement visibility
- the admin order view now shows shipment, COD settlement, and payout-batch summaries through custom order-side components
- carrier management is intended to become the foundation for later:
  - SMS / WhatsApp shipment notifications
