# Shipment Carrier Integrations

## Purpose

This document is the working reference for custom shipment carrier adapters, booking APIs, tracking APIs, webhook handling, and the live-credential checklist for future courier onboarding.

Use this as the source of truth whenever a new carrier is added to `packages/commerce-core`.

## Scope

This document covers:

- carrier booking adapters
- carrier tracking adapters
- webhook callbacks
- credential storage and naming
- status mapping and timeline behavior
- admin setup and smoke testing

This document does not store secrets. Do not paste live API keys, passwords, bearer tokens, or webhook secrets into the repo.

## Current Architecture

Carrier integrations are implemented through small provider contracts and registries:

- booking providers: [packages/commerce-core/src/Contracts/CarrierBookingProvider.php](/Users/shafin/Documents/Laravel-eCommerce-platform-with-CMS/packages/commerce-core/src/Contracts/CarrierBookingProvider.php)
- tracking providers: [packages/commerce-core/src/Contracts/CarrierTrackingProvider.php](/Users/shafin/Documents/Laravel-eCommerce-platform-with-CMS/packages/commerce-core/src/Contracts/CarrierTrackingProvider.php)
- booking registry: [packages/commerce-core/src/Support/CarrierBookingProviderRegistry.php](/Users/shafin/Documents/Laravel-eCommerce-platform-with-CMS/packages/commerce-core/src/Support/CarrierBookingProviderRegistry.php)
- tracking registry: [packages/commerce-core/src/Support/CarrierTrackingProviderRegistry.php](/Users/shafin/Documents/Laravel-eCommerce-platform-with-CMS/packages/commerce-core/src/Support/CarrierTrackingProviderRegistry.php)
- carrier settings model: [packages/commerce-core/src/Models/ShipmentCarrier.php](/Users/shafin/Documents/Laravel-eCommerce-platform-with-CMS/packages/commerce-core/src/Models/ShipmentCarrier.php)

Shared carrier settings currently available on each carrier record:

- `code`
- `name`
- `integration_driver`
- `tracking_sync_enabled`
- `api_base_url`
- `api_username`
- `api_password`
- `api_key`
- `api_secret`
- `webhook_secret`
- COD defaults and payout defaults

## Carrier Adapter Contract

Every live carrier adapter should define:

- a booking provider when the carrier supports order creation
- a tracking provider when the carrier supports polling
- a webhook controller/service when the carrier supports callbacks
- a status mapping table from carrier statuses to internal shipment statuses
- a duplicate-safe event policy so repeated syncs do not create duplicate timeline rows

Every adapter slice should answer these questions before merge:

- what is the booking endpoint?
- what is the tracking endpoint?
- what auth headers or bearer tokens are required?
- what payload is sent?
- what response fields are persisted?
- which status values are allowed to advance, hold, or skip shipment state?
- how do webhook retries behave?
- what is the manual fallback path if the carrier API is down?

## Steadfast Reference

Steadfast is the first live carrier adapter in this repository.

Current code references:

- booking provider: [packages/commerce-core/src/ShipmentBooking/Providers/SteadfastCarrierBookingProvider.php](/Users/shafin/Documents/Laravel-eCommerce-platform-with-CMS/packages/commerce-core/src/ShipmentBooking/Providers/SteadfastCarrierBookingProvider.php)
- tracking provider: [packages/commerce-core/src/ShipmentTracking/Providers/SteadfastCarrierTrackingProvider.php](/Users/shafin/Documents/Laravel-eCommerce-platform-with-CMS/packages/commerce-core/src/ShipmentTracking/Providers/SteadfastCarrierTrackingProvider.php)
- webhook controller: [packages/commerce-core/src/Http/Controllers/SteadfastWebhookController.php](/Users/shafin/Documents/Laravel-eCommerce-platform-with-CMS/packages/commerce-core/src/Http/Controllers/SteadfastWebhookController.php)
- webhook service: [packages/commerce-core/src/Services/SteadfastWebhookService.php](/Users/shafin/Documents/Laravel-eCommerce-platform-with-CMS/packages/commerce-core/src/Services/SteadfastWebhookService.php)
- shared API helper: [packages/commerce-core/src/ShipmentCarriers/SteadfastApiSupport.php](/Users/shafin/Documents/Laravel-eCommerce-platform-with-CMS/packages/commerce-core/src/ShipmentCarriers/SteadfastApiSupport.php)

Known booking flow in the current codebase:

- admin creates the shipment in Bagisto as usual
- Shipment Ops can trigger booking from the admin screen
- the booking provider sends a courier API request using carrier credentials already stored on the carrier record
- on success, the shipment record stores:
  - `carrier_booking_reference`
  - `carrier_consignment_id`
  - `carrier_invoice_reference`
  - `carrier_booked_at`
  - `tracking_number` if the carrier returns one
- booking creates an operational timeline event without changing shipment status

Known tracking and webhook flow in the current codebase:

- tracking sync can map external courier statuses into internal shipment statuses
- webhook sync uses the same status mapper as polling
- webhook matching can use:
  - `carrier_consignment_id`
  - `tracking_number`
  - invoice fallback
- repeated callbacks are duplicate-safe

Steadfast API notes currently captured in code:

- booking request uses a courier invoice reference and recipient delivery details
- tracking sync uses the carrier API URL template with `tracking_number`
- webhook authorization uses the carrier `webhook_secret`

Live credential checklist for Steadfast:

- confirm the official endpoint URLs with the carrier
- confirm the required auth headers or bearer token shape
- confirm the request payload fields and any optional fields
- confirm the response payload fields for booking and tracking
- confirm webhook header names and retry behavior
- confirm whether `consignment_id` or another field is the stable external identifier
- run one admin smoke test for booking
- run one webhook callback smoke test
- run one tracking sync smoke test

## Pathao Reference

Pathao is the next carrier candidate and this section records the implemented booking and tracking slices plus the remaining live-contract gaps.

## Public Pathao References

These are the public references we can rely on without merchant-panel access:

- [Pathao Courier](https://pathao.com/courier/)
- [How can I track orders from customers to customer service?](https://help.pathao.com/track-orders-customers-to-customer-service/)
- [How can I get the address of the customer service booking point from the customer?](https://help.pathao.com/customer-service-booking-address/)
- [Where is Pathao’s service available?](https://help.pathao.com/pathao-service-availability/)
- [What kind of service do you provide?](https://help.pathao.com/types-of-services/)
- [Courier Tracking Is Here!](https://pathao.com/blog/courier-tracking-is-here/)

Publicly verified Pathao facts from those pages:

- Pathao Courier is available in Bangladesh and advertises service across 64 districts.
- Pathao supports merchant courier service and customer-to-customer booking-point service.
- Pathao’s public tracking flow uses a consignment ID plus the customer phone number.
- Pathao’s merchant onboarding flow points users to `merchant.pathao.com/register`.
- Pathao provides public merchant tracking links and a user-app tracking flow.
- Pathao advertises COD and parcel return support in its courier service.

## Pathao Contract Placeholder

We still need live merchant/API access to fill in and verify the actual adapter contract before production use.

Capture the following once credentials are available:

- booking endpoint
- tracking endpoint
- webhook callback support
- authentication model
- request payload shape
- response payload shape
- external status values
- retry and duplicate callback behavior
- COD remittance and settlement notes if supported
- booking status field names returned by the API
- whether Pathao exposes a stable consignment identifier distinct from the tracking number

## Pathao Booking Adapter

The first Pathao booking slice is now implemented against the merchant API flow inferred from Pathao's public merchant guidance and public integration examples.

Current Pathao booking implementation notes:

- token acquisition uses the merchant API `issue-token` flow
- booking uses the merchant API `orders` flow
- location lookups use:
  - `city-list`
  - `cities/{city_id}/zone-list`
  - `zones/{zone_id}/area-list`
- Pathao booking requires a merchant store ID, stored on the carrier as `api_store_id`
- Pathao merchant credentials are mapped from the carrier fields already used by the admin carrier form:
  - `api_key` -> client ID
  - `api_secret` -> client secret
  - `api_username` -> merchant username
  - `api_password` -> merchant password
- carrier contact fields are used as the sender identity for booking
- booking stores the returned consignment ID and related courier identifiers on the shipment record
- booking creates a non-notifying operational event

Live merchant verification is still required before production use.

## Pathao Tracking Adapter

The first Pathao tracking slice is now implemented against Pathao's merchant order lookup flow.

Current Pathao tracking implementation notes:

- tracking uses the merchant API `view_order` flow against `aladdin/api/v1/orders/{consignment_id}`
- tracking requests reuse the same merchant credentials and bearer token flow as booking
- tracking can fall back to the stored tracking number when a consignment id is missing
- Pathao responses are read from `order_status` / `order_status_slug` and mapped into the internal shipment timeline
- the status mapper keeps shipment state from downgrading when the carrier returns an earlier status

Live merchant verification is still required before production use.

## Implementation Notes

When adding a new carrier adapter:

1. add or extend the booking/tracking provider under `packages/commerce-core`
2. add a provider entry in the relevant config registry
3. add or update the shipment ops admin form if the carrier needs manual setup
4. add focused tests for booking, tracking, and webhook behavior
5. update this document with the carrier-specific contract details
6. update [Doc/11-milestones.md](/Users/shafin/Documents/Laravel-eCommerce-platform-with-CMS/Doc/11-milestones.md) when the slice is completed

## Open Questions

- whether future carriers should share a common booking payload normalizer
- whether webhook verification should remain carrier-specific or move to a shared signature verifier
- whether a carrier settlement import format should live here or in the COD settlement document
- whether Pathao should use the same booking invoice format as Steadfast or a carrier-specific invoice key
- whether Pathao booking should resolve zone and area from explicit admin-configured mappings instead of the first matching merchant API result
