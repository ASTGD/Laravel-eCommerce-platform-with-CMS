# AGENTS.md

## Purpose

This repository is a **reusable e-commerce product** built on **Laravel 12** with **Bagisto as the commerce core**.

Your job is to implement and evolve this repository as a **product platform**, not as a one-off website.

Every decision must optimize for:
- correct foundation
- maintainability
- repeatable deployment
- isolated client installs
- structured CMS flexibility
- theme variation through configuration
- upgrade safety

---

## Hard requirements

### Technical foundation
Use and preserve this stack:

- PHP 8.3 is the verified minimum
- PHP 8.4 is the recommended production target
- Laravel 12.x
- Bagisto as the commerce core
- MySQL 8+
- Redis
- Blade + Vue.js
- Vite
- Tailwind CSS
- server-rendered storefront

### Do not violate these
- Do not switch to Laravel 13.
- Do not scaffold a plain Laravel store from scratch.
- Do not replace the commerce core with custom commerce primitives unless explicitly required.
- Do not add Filament for the main admin.
- Do not build a separate admin framework.
- Do not build a headless storefront in v1.
- Do not create a drag-and-drop freeform page builder.
- Do not centralize multiple client sites into one runtime.

---

## Product model

This project is delivered once and installed many times.

Each install must be fully standalone:
- separate host
- separate domain
- separate database
- separate admin users
- separate products and categories
- separate customers and orders
- separate content and media
- separate environment configuration
- separate payment/shipping credentials

This is:
- one reusable product
- many independent installations

This is not:
- one multi-tenant runtime
- one shared admin for many domains
- one shared database for all sites

---

## Architecture boundaries

### The commerce core owns
- products
- categories
- attributes/options
- pricing
- inventory
- customers
- cart
- checkout
- orders
- promotions
- standard commerce admin behavior

### Custom packages own
- structured CMS
- page composition
- section/block system
- theme system
- theme presets
- header/footer/menu configuration
- storefront presentation layer
- SEO/media enhancements
- project-specific support tooling

Do not reimplement built-in commerce capabilities unless there is a clear and documented reason.

Prefer extending through package/module boundaries.

---

## Package structure

Use these neutral custom package names:

- `packages/commerce-core`
- `packages/experience-cms`
- `packages/theme-core`
- `packages/theme-default`
- `packages/seo-tools`
- `packages/media-tools`
- `packages/platform-support`

Use neutral naming in:
- custom namespaces
- custom package names
- repo docs
- business-facing labels

Avoid exposing upstream vendor naming except where technically required in dependency/config contexts.

---

## CMS rules

The CMS is **structured**, not freeform.

### Admin can
- create pages
- choose templates
- add approved sections
- reorder sections
- configure sections
- configure components/blocks
- bind data sources
- edit menus
- edit header/footer
- preview
- publish

### Admin cannot
- arbitrarily create layout logic
- bypass design constraints
- directly edit storefront templates from the CMS
- create one-off page builders that break consistency

Every section type must have:
- a unique code
- config schema
- validation rules
- admin form
- renderer
- preview support

Every component type must have:
- a unique code
- config schema
- validation rules
- renderer
- preview support

All JSON config must be schema-backed and validated.

---

## Theme rules

The storefront must be server-rendered and theme-driven.

Use:
- one theme core
- one default theme
- configurable theme presets
- configurable design tokens
- reusable view/rendering contracts

Do not create unrelated theme architectures.

Visual variation should come from:
- preset configuration
- section variants
- component variants
- style tokens
- header/footer variants

Not from ad hoc per-client code.

---

## Customer portal rules

The customer portal is part of the storefront.

It is not a second admin panel.

Minimum customer features:
- register
- login
- forgot/reset password
- profile
- addresses
- order history
- order detail

Keep customer account UX aligned with the storefront theme system.

---

## Documentation rules

All structural documentation lives in `Doc/`.

Required docs include:
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

Whenever implementation changes architecture, update docs in the same workstream.

---

## Implementation approach

Work in vertical slices.

Preferred order:
1. confirm correct foundation
2. scaffold package/module structure
3. create docs
4. implement CMS entities and contracts
5. implement section registry and component registry
6. implement theme core
7. implement default theme
8. implement homepage vertical slice
9. implement commerce-aware CMS sections
10. implement customer portal basics
11. harden tests/docs/deployment

Do not leave the codebase in a half-switched architectural state.

---

## Coding standards

### General
- keep controllers thin
- use services/actions for workflows
- prefer explicit contracts/interfaces
- avoid god classes
- keep package boundaries clean
- use centralized validation
- type-hint aggressively where practical
- document major architectural decisions

### Views
- no business logic in templates
- use presenters/view models/renderers where appropriate
- keep templates focused on rendering

### Upstream integration
- avoid editing upstream core directly
- if an upstream override is unavoidable, isolate it and document it in `Doc/`

---

## Validation and testing

Before claiming a task is complete:
- run relevant tests
- run code style checks
- verify the feature manually if needed
- update docs if architecture or usage changed

Minimum expectations:
- feature tests for CMS CRUD
- render tests for key page types
- validation tests for section/component schemas
- smoke tests for customer auth/account
- smoke tests for checkout-critical flows

If a command is unavailable, do not invent results. State what is missing and what needs to be set up.

---

## Commit and task behavior

When working on a task:
- summarize assumptions
- make the smallest coherent set of changes that completes a vertical slice
- avoid broad speculative scaffolding with no execution path
- keep changes easy to review
- prefer milestone-aligned commits

When reporting back:
- summarize what changed
- list main files touched
- list assumptions
- list follow-up tasks
- note any upstream/core touchpoints

---

## Decision rules

If there is ambiguity:
1. prefer the most maintainable option
2. prefer the most structured option
3. prefer extension over replacement
4. prefer repeatable deployment over short-term convenience
5. document the decision in `Doc/`

If the requested change conflicts with this file, pause and explain the conflict clearly.

---

## Immediate priorities

Always verify these first in a fresh setup:
- Laravel 12 is in use
- Bagisto is installed and functioning as the commerce core
- package boundaries are clean
- docs exist and match reality

Do not proceed with major custom CMS work until the correct commerce foundation is confirmed.

---

## Verified foundation baseline

The foundation verification pass is complete and should be treated as the current stable baseline.

Verified baseline:
- Laravel 12.56.0
- Bagisto 2.4.x as the commerce core
- PHP 8.3.30 verified
- MySQL 8.4 verified
- Redis 7 verified
- package discovery verified
- install, migrate, and seed verified
- storefront and admin/customer login routes verified
- Tailwind wired for the custom storefront layer

Do not re-platform or replace the commerce foundation unless explicitly requested.
Proceed with feature development on top of this verified baseline.
