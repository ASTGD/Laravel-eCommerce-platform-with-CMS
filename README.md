# Reusable Laravel E-Commerce Platform with Structured CMS

A reusable e-commerce platform built on Laravel with a structured CMS and theme system.

This project is designed to be delivered once and installed multiple times on separate hosts/domains. Each installation is fully standalone, with its own database, admin users, products, customers, orders, content, media, and environment configuration.

## Product model

This is **one reusable product**, not one centralized multi-store runtime.

Each client installation must be:
- a separate host
- a separate domain
- a separate database
- a separate storage/media setup
- a separate admin user set
- a separate payment/shipping configuration

All installations share the same core codebase and product structure.

## Architecture summary

The platform has three main surfaces:

### 1. Admin / Back Office
Used by staff to manage:
- products
- categories
- inventory
- customers
- orders
- promotions
- CMS pages
- menus
- header/footer
- theme presets
- SEO
- media
- users and roles

### 2. Public Storefront
Used by shoppers for:
- homepage
- category pages
- product pages
- landing pages
- cart
- checkout
- content pages

### 3. Customer Portal
Part of the storefront, not a separate admin.

Used by customers for:
- sign in / sign up
- profile
- address book
- order history
- order details
- password reset

## Technical foundation

### Core stack
- PHP 8.3+
- Laravel 12.x
- Bagisto as the commerce core
- MySQL 8+
- Redis
- Nginx + PHP-FPM
- Blade + Vue.js
- Vite
- Tailwind CSS

### Important architectural rule
The commerce foundation must come from the upstream commerce engine.

Do not build a plain Laravel store from scratch and do not replace core commerce primitives unnecessarily.

Custom work must be built as isolated packages/modules on top of the commerce foundation.

## Custom package structure

Planned custom packages:

- `packages/commerce-core`
- `packages/experience-cms`
- `packages/theme-core`
- `packages/theme-default`
- `packages/seo-tools`
- `packages/media-tools`
- `packages/platform-support`

## CMS philosophy

This is **not** a drag-and-drop page builder.

This is a structured CMS based on:
- templates
- sections
- components/blocks
- theme presets
- global editable areas
- controlled merchandising sections

Admins can:
- create pages
- choose templates
- add approved sections
- reorder sections
- configure blocks/components
- edit menus
- edit header/footer
- preview
- publish

Admins cannot:
- arbitrarily break the design system
- create unbounded layouts
- directly edit storefront templates from admin

## Theme philosophy

All installations use the same platform structure.

Visual differences come from:
- theme presets
- design tokens
- section variants
- header/footer variants
- configurable component styles

Do not create five unrelated codebases or five unrelated theme architectures.

## Repository rules

- Keep custom package names neutral.
- Keep architectural docs inside `Doc/`.
- Avoid exposing upstream vendor naming in business-facing docs, repo naming, or custom package names unless technically required.
- Prefer extension and integration over core modification.
- Keep the codebase maintainable and upgrade-safe.

## Documentation

Project docs live in:

- `Doc/00-overview.md`
- `Doc/01-tech-stack.md`
- `Doc/02-architecture.md`
- `Doc/03-domain-model.md`
- `Doc/04-cms-model.md`
- `Doc/05-theme-system.md`
- `Doc/06-admin-ia.md`
- `Doc/07-customer-portal.md`
- `Doc/08-installation.md`
- `Doc/09-deployment.md`
- `Doc/10-coding-standards.md`
- `Doc/11-milestones.md`
- `Doc/12-acceptance-criteria.md`

## Initial priorities

1. Bootstrap the correct commerce foundation on Laravel 12.
2. Create the custom package skeletons.
3. Build the structured CMS core.
4. Build the theme core and default theme.
5. Implement one working homepage vertical slice.
6. Add commerce-aware CMS sections.
7. Implement customer portal basics.
8. Harden docs, tests, and deployment setup.

## Status

This repository is under active product setup.

Before implementing major features, confirm:
- Laravel 12 is being used
- the commerce foundation is installed correctly
- custom packages are isolated cleanly
- docs in `Doc/` reflect actual implementation
