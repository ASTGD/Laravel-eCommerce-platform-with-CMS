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
