# Customer Portal

## Scope

The customer portal is part of the storefront theme system, not a second admin.

## v1 Features

- registration
- login
- forgot/reset password
- account dashboard
- profile details
- address book
- order history
- order detail
- payment and refund status visibility on order detail for gateway-backed orders
- affiliate application and affiliate portal states after the shared affiliate domain is enabled

Current gateway-backed order detail coverage:

- `SSLCommerz` payment details and refund history
- direct `bKash` payment details and refund history
- shipment tracking timeline from the custom shipment-operations domain

Current customer-facing order lifecycle:

- new order: `Pending`
- admin-confirmed order: `Processing`
- shipped order: `Shipped`

The customer order history and order detail views now render the same order state progression as the admin sales area for this slice.

Current shipment-tracking coverage on order detail:

- read-only shipment tracking card
- carrier and tracking-number visibility
- carrier tracking link when the selected carrier has a tracking URL template
- customer-facing shipment timeline events such as:
  - shipment created
  - arrived at destination hub
  - out for delivery
  - delivered
  - delivery failed
  - return initiated
  - returned to origin

This view intentionally does not expose:

- COD settlement state
- remittance batches
- admin-only shipment notes

Current shipment communication coverage:

- customer operational shipment emails are now sent for:
  - out for delivery
  - delivered
  - delivery failed
  - return initiated
  - returned
- customer emails link back to the public shipment-tracking page with the order reference prefilled

Public storefront shipment tracking is also now available for guest and support use.

Current public tracking rule:

- lookup requires:
  - order number or tracking number
  - phone number used during checkout
- successful lookup shows:
  - shipment status
  - carrier
  - tracking number
  - external carrier tracking link when configured
  - simplified shipment timeline
- public tracking still does not expose:
  - COD settlements
- payout batches
- internal admin notes

Current public-entry surfaces:

- checkout success page
- storefront footer / support surface

## Registration Flow

- the customer registration flow remains Bagisto-based
- the existing admin setting at `Configuration > Customer > Settings > Email > Email Verification` is the single source of truth for storefront account verification
- when email verification is enabled, successful registration should land on an explicit verification-pending page with resend access rather than a silent redirect
- when email verification is disabled, successful registration should land on an explicit success page with a direct sign-in action
- the registration-success sign-in action should direct the customer into the account dashboard, not back to storefront home
- customer login should continue to block unverified accounts and clearly offer verification resend guidance

## Route Principle

Account routes should remain storefront-facing and authenticated with customer guards, while sharing the same layout and preset system as the storefront.

## Backend Assumption For Later Milestones

Customer-facing account pages should reuse the same shared storefront shell contracts already active for homepage, category pages, and product pages:

- header resolution
- footer resolution
- menu resolution
- theme preset resolution
- site settings resolution

Customer portal work should extend the existing theme-layer payload model rather than introducing a separate page-composition system.

## Affiliate Portal Plan

The affiliate portal is part of the existing customer account area and uses the same shared affiliate backend as the admin screens.

Customer-facing rules:

- any logged-in customer can apply to become an affiliate
- one customer account maps to one affiliate profile
- before approval, the customer sees only the relevant application state:
  - no affiliate profile
  - pending
  - rejected
  - suspended
- only `active` affiliates see the full Affiliate portal area
- the portal reads the same clicks, attributed orders, commissions, withdrawal requests, and payouts as admin

Current Phase 2 customer account route:

- `Customer Account > Affiliate`
- route: `shop.customers.account.affiliate.index`
- path: `/customer/account/affiliate`

Current public affiliate entry route:

- route: `shop.affiliate-program.index`
- path: `/affiliate-program`
- guest CTA: login or register before applying
- logged-in customer CTA: go to the account-based Affiliate application/dashboard

Current Phase 2 behavior:

- customers without an affiliate profile can submit an application
- rejected customers can update details and resubmit
- pending customers see only an under-review state
- suspended customers see only a suspended-state notice
- active customers see the full affiliate dashboard

Current Phase 4 referral behavior:

- approved affiliates use their generated referral code in storefront links through the `ref` query parameter
- active affiliates can copy their referral code and homepage referral link from the customer dashboard
- active affiliates can generate simple tracked links for valid internal storefront paths
- referral codes are stable for the life of the affiliate profile
- referral links remain valid while the affiliate is active
- visitor attribution expires by the configured cookie window, not by link age
- referral clicks are tracked for reporting and attribution context only
- attributed orders and commissions are written to the shared affiliate backend records that the customer Affiliate portal reads
- self-referrals are blocked, so an affiliate customer cannot earn commission from their own customer account orders

Current Phase 5 approved-affiliate portal behavior:

- dashboard
- referral link and referral code copy tools
- simple internal storefront link builder
- traffic summary
- sales summary
- commission summary
- payout and withdrawal summary
- payout history
- withdrawal request flow

Withdrawal request rules:

- only `active` affiliates can request payouts
- withdrawal requests use the same `affiliate_payouts` table that admin payout screens read
- available balance is derived from approved commissions minus active payout allocation rows
- minimum payout threshold and available payout methods are read from the shared affiliate settings
- the public payout record reference remains system-generated and unique
- customer-entered bank, wallet, or payout account details are stored in payout metadata as `payout_account_details`
- requested and approved payouts reserve commission balance immediately
- rejected payouts release reserved commission balance back to available balance
- paid payouts settle the allocated commissions and remain visible in payout history
- affiliate application terms text is read from the shared affiliate settings

Planned later customer portal improvements:

- dedicated commission detail pagination
- richer traffic trend charts
- downloadable payout statements

The v1 customer portal must not introduce a separate affiliate login, separate affiliate tables, Email Affiliate, Banner/Text Ad management, or per-click payout behavior.
