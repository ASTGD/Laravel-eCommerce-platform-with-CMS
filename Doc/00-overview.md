# Overview

## Mission

Build one reusable e-commerce product on Laravel 12 with Bagisto as the commerce core.

The product is delivered once and installed many times on separate hosts and domains. Each installation must remain fully standalone at the infrastructure, data, admin, and integration layers.

## Product Model

This repository is:

- one reusable product
- many independent installations
- one shared codebase with configuration-driven variation

This repository is not:

- a centralized multi-tenant runtime
- a shared admin for many domains
- a freeform page-builder product
- five separate storefront codebases

## Main Surfaces

### Admin / Back Office

Used by staff to manage catalog, orders, customers, promotions, CMS content, menus, header/footer, theme presets, SEO, media, and users/roles.

### Public Storefront

Used by shoppers for homepage, category pages, product pages, campaign pages, cart, checkout, and structured content pages.

### Customer Portal

Part of the storefront, not a second admin. Used for sign-in, profile, addresses, password reset, and order history/detail.

## Non-Goals

- Headless storefront in v1
- Filament-driven main admin
- Marketplace or multi-vendor behavior
- Shared runtime multi-store model
- Drag-anything-anywhere page builder
- One-off client-specific architecture in the core product

## Install Model

Each install must have:

- its own domain
- its own `.env`
- its own database
- its own storage/media
- its own admin users
- its own catalog and CMS data
- its own payment and shipping credentials
- its own theme preset selection
