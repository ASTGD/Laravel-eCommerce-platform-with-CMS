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
- `payment_attempts`
- `payment_gateway_events`
- `payment_refunds`

## Relationship Summary

- A page belongs to a template.
- A template has many template areas.
- A page has many sections.
- A section belongs to a section type and may have components.
- A page may have many versions.
- Menus have nested items.
- Header/footer configs and theme presets are resolved globally or per page later.
- A `pickup_point` is selected against checkout shipping context and copied into the final order shipping snapshot.
- A `payment_attempt` tracks one external gateway payment session for one cart and optionally one finalized order.
- A `payment_attempt.provider` currently resolves either `sslcommerz` or direct `bkash`, so payment operations remain provider-aware without forking the order model.
- A `payment_gateway_event` stores each inbound redirect/IPN payload for audit and idempotent gateway reconciliation.
- A `payment_attempt` also stores the last reconciliation timestamp, outcome, source, and reconcile error so payment operations can inspect pending or disputed gateway state without replaying raw callbacks.
- A `payment_refund` tracks one external gateway refund request against one paid order and one payment attempt.
- A `payment_refund` records refund references, refresh results, failure reasons, and the linked Bagisto refund so gateway refund state can be audited without guessing from order totals alone.
