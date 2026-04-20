# Temporary Shipment And COD Settlement Implementation Plan

## Purpose

This is the working implementation note for the next backend workstream.

The order-status workflow is already settled:

- `Pending`
- `Processing`
- `Shipped`

This document covers the next two admin-only domains:

- shipment operations
- COD settlement and reconciliation

This is a temporary execution document intended to guide the next step-by-step implementation slices.

## Scope Boundary

### In Scope

- admin carrier management
- shipment records and shipment timeline
- delivery attempt and return flow
- COD collected/remitted/settled tracking
- courier payout batch reconciliation
- admin reporting and operations views

### Out Of Scope For This Workstream

- storefront shipment tracking UX polish
- broad courier API coverage beyond the first live adapter
- SMS or WhatsApp integrations
- automated courier label printing
- advanced delivery proof uploads
- full finance/accounting module

## Domain Separation

Do not mix these three domains:

- `Order`
  - business state
  - already implemented as `Pending -> Processing -> Shipped`
- `Shipment`
  - logistics state
  - where carrier movement and delivery events live
- `COD Settlement`
  - money state
  - where courier-collected cash is reconciled to merchant remittance

Delivered does not mean settled.

A parcel can be delivered today while the courier remits the COD balance later.

## Recommended Status Models

### Shipment Status

Use these internal shipment statuses:

- `draft`
- `ready_for_pickup`
- `handed_to_carrier`
- `in_transit`
- `out_for_delivery`
- `delivered`
- `delivery_failed`
- `returned`
- `canceled`

### COD Settlement Status

Use these internal settlement statuses:

- `expected`
- `collected_by_carrier`
- `remitted`
- `settled`
- `short_settled`
- `disputed`
- `written_off`

## Recommended Entities

Add these custom platform entities in `packages/commerce-core`:

- `shipment_carriers`
- `shipment_records`
- `shipment_record_items`
- `shipment_events`
- `cod_settlements`
- `settlement_batches`
- `settlement_batch_items`

`shipment_carriers` is the preferred storage name because Bagisto already uses the term `carrier` for shipping-method implementations. The admin label can still be `Carriers`.

`shipment_records` and `shipment_record_items` are preferred over `shipments` and `shipment_items` because Bagisto already owns native shipment persistence in those tables. The custom domain should reference the native shipment, not collide with it.

## Entity Intent

### shipment_carriers

Stores courier agencies and their payout characteristics.

Suggested fields:

- `code`
- `name`
- `is_active`
- `contact_name`
- `contact_phone`
- `contact_email`
- `tracking_url_template`
- `integration_driver`
- `tracking_sync_enabled`
- `api_base_url`
- `api_username`
- `api_password`
- `api_key`
- `api_secret`
- `webhook_secret`
- `supports_cod`
- `default_cod_fee_type`
- `default_cod_fee_amount`
- `default_return_fee_amount`
- `default_payout_method`
- `notes`

### shipment_records

One parcel or consignment record linked to one order.

Suggested fields:

- `order_id`
- `carrier_id`
- `status`
- `tracking_number`
- `inventory_source_id`
- `origin_label`
- `destination_country`
- `destination_region`
- `destination_city`
- `recipient_name`
- `recipient_phone`
- `recipient_address`
- `cod_amount_expected`
- `cod_amount_collected`
- `carrier_fee_amount`
- `cod_fee_amount`
- `return_fee_amount`
- `net_remittable_amount`
- `handed_over_at`
- `delivered_at`
- `returned_at`
- `notes`

### shipment_record_items

Maps order items and quantities into a shipment.

Suggested fields:

- `shipment_id`
- `order_item_id`
- `qty`
- `weight`

### shipment_events

Timeline log for operational updates.

Suggested fields:

- `shipment_id`
- `event_type`
- `status_after_event`
- `event_at`
- `actor_admin_id`
- `note`
- `meta`

### cod_settlements

Tracks COD expectation and settlement at the shipment level.

Suggested fields:

- `shipment_id`
- `status`
- `expected_amount`
- `collected_amount`
- `remitted_amount`
- `short_amount`
- `disputed_amount`
- `carrier_fee_amount`
- `cod_fee_amount`
- `return_fee_amount`
- `net_amount`
- `remitted_at`
- `settled_at`
- `notes`

### settlement_batches

One courier remittance batch covering many delivered COD shipments.

Suggested fields:

- `carrier_id`
- `reference`
- `payout_method`
- `status`
- `gross_collected_amount`
- `gross_remitted_amount`
- `total_deductions_amount`
- `net_amount`
- `remitted_at`
- `received_at`
- `notes`

### settlement_batch_items

Links `cod_settlements` into a remittance batch.

Suggested fields:

- `settlement_batch_id`
- `cod_settlement_id`
- `expected_amount`
- `remitted_amount`
- `adjustment_amount`
- `note`

## Admin Information Architecture

These should live under `Sales` in Bagisto admin through `packages/commerce-core`:

- `Sales > Orders`
  - keep as business overview
  - add shipment summary and COD summary cards later
- `Sales > Shipment Ops`
  - operational shipment management
  - transitional name used to coexist with Bagisto native `Sales > Shipments`
- `Sales > Carriers`
  - carrier CRUD
- `Sales > COD Settlements`
  - delivered-but-unsettled money tracking
- `Sales > Settlement Batches`
  - courier remittance batch reconciliation

Do not use `Sales > Bookings` for this domain.

That screen is only for booking-product reservations.

## Implementation Rules

- implement one branch per slice
- do not skip ahead
- finish tests for each slice before starting the next
- keep Bagisto admin structure native
- add custom sales sections through `packages/commerce-core/config/menu.php`, `packages/commerce-core/config/acl.php`, and `packages/commerce-core/routes/admin.php`
- keep controllers thin
- use service/repository/action classes for workflow logic
- do not place business logic in Blade views
- use render hooks on the native order view where order-level shipment or COD summaries must appear

## Current Progress Snapshot

Implemented slices so far:

- `feature/shipping-carrier-registry`
- `feature/shipment-domain-core`
- `feature/shipment-event-timeline`
- `feature/cod-settlement-core`
- `feature/cod-settlement-batches`
- `feature/order-shipment-cod-summary`
- `feature/shipment-return-operations`
- `feature/customer-shipment-tracking-timeline`
- `feature/public-shipment-tracking-page`
- `feature/carrier-tracking-links-and-public-entry`
- `feature/courier-api-integration-foundation`
- `feature/courier-settlement-import-and-automated-sync`
- `feature/manual-cod-reconciliation-hardening`
- `feature/shipment-notifications-and-communications`

Current manual/admin coverage now includes:

- carrier registry
- shipment operations with timeline and exception handling
- COD settlement tracking and payout batches
- order-level shipment / COD summaries
- customer and public tracking views
- carrier tracking links
- carrier-sync foundation
- operational shipment email notifications with communication audit logs

## Step-By-Step Execution Plan

### Slice 1

Branch:

- `feature/shipping-carrier-registry`

Goal:

- create the carrier master-data domain

Work:

- add `carriers` migration, model, repository
- add admin CRUD screens under `Sales > Carriers`
- add menu and ACL entries
- add datagrid and create/edit form
- add active toggle and payout metadata

Acceptance:

- admin can create, edit, activate, and deactivate carriers
- carriers appear in a dedicated sales submenu
- carrier data is ready to be selected by shipment creation later

### Slice 2

Branch:

- `feature/shipment-domain-core`

Goal:

- create the shipment domain and stop relying on the native Bagisto shipment form as the long-term operator UI

Work:

- add `shipment_records` and `shipment_record_items`
- sync operational shipment records from native Bagisto shipment creation
- create custom shipment ops view screens
- create shipment datagrid under `Sales > Shipment Ops`
- link shipment to order, carrier, and inventory source
- support initial statuses:
  - `handed_to_carrier`
  - `in_transit`
  - `out_for_delivery`
  - `delivered`
  - `delivery_failed`
  - `returned`
- add order-view shipment summary card

Acceptance:

- native shipment creation auto-syncs into a custom operational shipment record
- shipment record has carrier snapshot, tracking, source, destination snapshot, and COD expectation fields
- shipment records are visible under a dedicated operational shipment list

### Slice 3

Branch:

- `feature/shipment-event-timeline`

Goal:

- add a real logistics timeline instead of a single shipment row

Work:

- add `shipment_events`
- allow manual event append/update from admin
- support manual event types:
  - `arrived_destination_hub`
  - `delivery_attempted`
  - `customer_unreachable`
  - `customer_refused`
  - `reattempt_approved`
  - `return_initiated`
  - `return_completed`
- allow event logging without forcing a shipment status change
- support progression:
  - `in_transit`
  - `out_for_delivery`
  - `delivered`
  - `delivery_failed`
  - `returned`
- add shipment detail timeline UI

Acceptance:

- each shipment has an auditable event history
- admin can add operational events even when the shipment status should remain unchanged
- resulting status can still be advanced from the event flow when needed
- admin can record failed delivery or return transitions

### Slice 4

Branch:

- `feature/cod-settlement-core`

Goal:

- create per-shipment COD money tracking

Work:

- add `cod_settlements`
- auto-create expected settlement row for COD shipments
- track:
  - expected COD
  - collected by carrier
  - remitted amount
  - fees
  - short amount
  - dispute note
- add `Sales > COD Settlements`

Acceptance:

- delivered COD shipments appear in COD settlement operations
- admin can mark collected/remitted/settled states manually
- admin can identify delivered but unpaid shipments

### Slice 5

Branch:

- `feature/cod-settlement-batches`

Goal:

- track courier remittance as batches, not only per shipment

Work:

- add `settlement_batches` and `settlement_batch_items`
- create batch CRUD/list/view screens
- allow many COD settlements to be attached to one remittance batch
- calculate gross, deductions, and net totals

Acceptance:

- admin can create a courier remittance batch
- admin can attach many COD shipments to one payout
- linked COD settlements can be reconciled from the batch lifecycle
- short settlement becomes visible at batch and item level

### Slice 6

Branch:

- `feature/order-shipment-cod-summary`

Goal:

- surface shipment and COD information where operators already work: the order view

Work:

- add shipment summary card to admin order view
- add COD settlement summary card to admin order view
- add quick links to shipment and settlement details
- add order-level action shortcuts where appropriate

Acceptance:

- an admin opening an order can see:
  - current shipment status
  - carrier and tracking
  - COD expected/settled state
  - linked settlement batch when the order is already part of a payout batch

### Slice 7

Branch:

- `feature/shipment-return-operations`

Goal:

- complete the manual Bangladesh courier exception flow

Work:

- add delivery-attempt notes
- add return-to-origin flow
- add failure reasons
- add reattempt support

Acceptance:

- admin can distinguish:
  - delivered
  - failed delivery
  - returned
  - reattempt required

Current implementation status:

- implemented
- shipment ops now stores:
  - delivery attempt count
  - failure reason
  - reattempt-required state
  - last delivery-attempt timestamp
  - return-initiated timestamp
- admin actions now exist for:
  - recording delivery failure
  - approving reattempt
  - initiating return
  - completing return

## Test Expectations Per Slice

Each slice should include:

- migration coverage where relevant
- feature tests for admin CRUD and workflow actions
- status transition tests
- datagrid smoke tests where practical
- render test or HTTP smoke test for key admin views

## Suggested First Deliverable Order

If implementation starts immediately, the next branch should be:

- `feature/shipping-carrier-registry`

Do not start shipment or COD workflow screens before carriers exist.

## Follow-Up Note

After these admin-only slices are stable, a separate later workstream can add:

- customer-facing shipment tracking timeline
- courier API integration
- automated settlement imports
- SMS/WhatsApp event notifications

Current follow-up status:

- customer-facing shipment tracking timeline is now active on customer order detail
- public shipment tracking page is now active with reference + phone lookup
- carrier tracking links and public-entry surfaces are now active
- courier API integration foundation is now active:
  - carrier registry stores integration driver and API credential placeholders
  - shipment ops stores last sync state and supports manual sync triggers
  - queue job and CLI command foundations exist for future provider adapters
- the first live courier adapter is now active:
  - `Steadfast` can call a real tracking endpoint and map external delivery statuses into the internal shipment timeline
  - other courier drivers still remain on the placeholder sync foundation until their dedicated adapter slices are implemented
- the first courier webhook slice is now active:
  - `Steadfast` carriers expose a dedicated callback URL plus shared-secret verification
  - webhook payloads can update shipment records through the same internal status pipeline used by manual tracking sync
  - invoice fallback is supported when tracking code is absent
  - persisted booking references now allow `consignment_id` webhook matching on shipment records
- the booking-reference persistence slice is now active:
  - shipment records can store carrier booking reference, consignment id, invoice reference, and booked-at timestamp
  - Shipment Ops now includes an admin form for maintaining those external references without changing shipment status
  - booking-reference updates are logged as operational timeline events without triggering shipment notifications
- the first live courier booking slice is now active:
  - Shipment Ops can create a `Steadfast` booking directly from the admin screen using the carrier API credentials already stored on the carrier
  - successful booking responses persist consignment id, invoice reference, booked-at timestamp, and courier tracking code on the shipment record
  - automated booking creates a non-notifying operational timeline event, so courier IDs are captured without falsely advancing shipment status
- carrier-specific API notes and future live credential details should be tracked in [Doc/18-shipment-carrier-integrations.md](/Users/shafin/Documents/Laravel-eCommerce-platform-with-CMS/Doc/18-shipment-carrier-integrations.md)
- manual COD reconciliation hardening is now active:
  - invalid `settled`, `disputed`, and batch-dispute transitions are blocked
  - COD settlements now expose outstanding amount and linked-batch visibility
  - settlement batches now expose reconciliation health counts and attention state
- courier settlement import and automated sync is now active:
  - `Sales > Settlement Batches` now includes CSV import with strict row validation
  - one imported CSV can create one payout batch and auto-sync linked COD settlement statuses
  - the same import flow is exposed through `platform:cod-settlements:import` for operator automation
- additional courier adapters beyond `Steadfast` remain deferred
- additional live courier booking adapters beyond `Steadfast` remain deferred
