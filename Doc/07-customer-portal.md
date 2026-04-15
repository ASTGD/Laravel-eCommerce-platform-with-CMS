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

Current gateway-backed order detail coverage:

- `SSLCOMMERZ` payment details and refund history
- direct `bKash` payment details and refund history

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
