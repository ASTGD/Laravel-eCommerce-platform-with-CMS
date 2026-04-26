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
- `pickup_points`
- `shipment_carriers`
- `shipment_records`
- `shipment_events`
- `shipment_handover_batches`
- `cod_settlements`
- `settlement_batches`
- `payment_attempts`
- `payment_gateway_events`
- `payment_refunds`
- `affiliate_profiles`
- `affiliate_clicks`
- `affiliate_order_attributions`
- `affiliate_commissions`
- `affiliate_payouts`
- `affiliate_payout_commission_allocations`
- `affiliate_settings`

## Relationship Summary

- A page belongs to a template.
- A template has many template areas.
- A page has many sections.
- A section belongs to a section type and may have components.
- A page may have many versions.
- Menus have nested items.
- Header/footer configs and theme presets are resolved globally or per page later.
- A `pickup_point` is selected against checkout shipping context and copied into the final order shipping snapshot.
- A `shipment_carrier` stores the business-facing courier registry together with optional advanced automation settings.
- A native Bagisto shipment can sync into one `shipment_record`, which becomes the shared operational shipment domain for both Basic and Advanced workflows.
- A `shipment_record` can hold manual parcel-preparation data such as packed state, parcel count, handover mode, and internal courier notes without replacing the native shipment source action.
- A `shipment_handover_batch` groups one or many ready parcels for courier pickup or staff drop-off and preserves manifest-level proof of handover without replacing per-shipment traceability.
- A `cod_settlement` still remains shipment-based even when Basic-mode UI summarizes receivables courier-first.
- A `settlement_batch` groups many `cod_settlements` for advanced remittance and reconciliation workflows.
- A `payment_attempt` tracks one external gateway payment session for one cart and optionally one finalized order.
- A `payment_attempt.provider` currently resolves either `sslcommerz` or direct `bkash`, so payment operations remain provider-aware without forking the order model.
- A `payment_gateway_event` stores each inbound redirect/IPN payload for audit and idempotent gateway reconciliation.
- A `payment_attempt` also stores the last reconciliation timestamp, outcome, source, and reconcile error so payment operations can inspect pending or disputed gateway state without replaying raw callbacks.
- A `payment_refund` tracks one external gateway refund request against one paid order and one payment attempt.
- A `payment_refund` records refund references, refresh results, failure reasons, and the linked Bagisto refund so gateway refund state can be audited without guessing from order totals alone.

## Affiliate Domain

The affiliate system is one shared backend domain used by both admin screens and the customer portal. It is not a separate storefront subsystem.

Phase 1 foundation entities:

- `affiliate_profiles` stores the one-to-one customer affiliate profile, canonical affiliate status, referral code, application details, payout preference, and admin status metadata.
- `affiliate_clicks` stores referral traffic events for reporting and attribution context. Clicks are tracked for reporting only in v1 and do not create payable commission by themselves.
- `affiliate_order_attributions` links one order to one affiliate profile when a valid referral is applied within the configured cookie window.
- `affiliate_commissions` stores the order-based commission ledger for attributed orders.
- `affiliate_payouts` stores customer withdrawal requests and admin payout records.
- `affiliate_payout_commission_allocations` stores exact payout-to-commission allocation rows so payout requests can reserve full or partial commission amounts without cached balance totals.
- `affiliate_settings` stores admin-managed affiliate program settings as key/value overrides on top of package defaults.

Canonical affiliate statuses:

- `pending`
- `active`
- `suspended`
- `rejected`

Core relationship rules:

- One Bagisto customer can have one affiliate profile.
- Only an `active` affiliate profile can access the future customer Affiliate portal area.
- Admin and customer portal screens must read and write these same affiliate records.
- Commission is order-based in v1.
- Click tracking supports reporting and attribution context but never per-click payout.
- Payout balances are derived from approved commissions and payout allocation rows rather than relying on cached totals.
- Self-referral prevention is enabled by default.

Default affiliate settings are package config values under `commerce_affiliate`, with admin-managed overrides stored in `affiliate_settings`. Runtime services read through `AffiliateSettingsService`, so admin and customer portal workflows share the same approval, commission, cookie-window, payout-method, minimum-payout, and terms rules.

Phase 4 referral tracking decisions:

- Referral links use the `ref` query parameter, for example `/?ref=AFFILIATECODE`.
- Storefront GET requests with a valid active referral code create an `affiliate_click` record and store attribution context in both session and an HTTP-only referral cookie.
- Admin routes are excluded from referral capture.
- The configured cookie window is currently 30 days through `commerce_affiliate.cookie_window_days`.
- Session attribution takes priority over cookie attribution when both are present.
- Order attribution runs from the Bagisto `checkout.order.save.after` event and creates one `affiliate_order_attribution` record per order.
- Commission creation happens from the same order-save attribution flow and creates a pending order-based commission using the configured default commission rule.
- Self-referrals are blocked during click capture and order attribution when `commerce_affiliate.self_referral_prevention` is enabled.
- Order cancellation reverses the related commission and marks the order attribution as canceled from the Bagisto `sales.order.cancel.after` event.
- Refund-specific commission adjustment is not part of v1 Phase 4 and should be handled in a later hardening slice if partial-refund behavior is needed.

Phase 5 customer portal decisions:

- Active affiliate dashboard metrics are query projections over `affiliate_clicks`, `affiliate_order_attributions`, `affiliate_commissions`, and `affiliate_payouts`.
- The customer portal does not maintain separate cached affiliate totals in v1.
- Customer withdrawal requests create `affiliate_payouts` records with `requested` status.
- Affiliate payout availability is derived from approved commissions minus active payout allocation rows.
- `affiliate_payouts.payout_reference` remains the system payout record/reference. Customer-entered transfer account details are stored in `affiliate_payouts.meta.payout_account_details`.

Phase 6 payout lifecycle decisions:

- Creating a withdrawal request reserves approved commission amounts through `affiliate_payout_commission_allocations`.
- Allocation supports partial commission payout, so an affiliate can withdraw less than the full amount of a single approved commission without marking the whole commission paid.
- Requested and approved payouts keep allocations in `reserved` state.
- Rejected payouts move their reserved allocations to `released`, which restores the affiliate's available balance.
- Paid payouts move allocations to `paid`; a commission moves to `paid` only after paid allocations cover the full commission amount.
- Admin-created paid payout records use the same allocation and paid-settlement flow as customer withdrawal requests.

Phase 7 reporting and settings decisions:

- Admin affiliate reports are query projections over the shared affiliate tables, not separate reporting tables.
- Report totals cover affiliate counts, referral clicks, attributed orders, attributed sales, commission totals, payout totals, daily trends, and top affiliates.
- Admin settings persist only the operational v1 controls:
  - approval requirement
  - default commission rule
  - cookie window
  - minimum payout threshold
  - payout methods
  - affiliate terms text
- Referral parameter name, cookie name, and self-referral prevention remain package-level technical config values in v1.
- Email Affiliate, Banner/Text Ad, and per-click payout settings are intentionally absent from the v1 domain.
