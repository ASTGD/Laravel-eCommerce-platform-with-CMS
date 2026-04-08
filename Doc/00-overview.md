# 00 Overview

## Product Summary

This repository contains one reusable commerce product that is intended to be deployed multiple times as separate installations. Each installation is operationally isolated with its own domain, storage, database, admin users, catalog, content, and credentials, while still sharing the same engineered codebase and release process.

The platform combines two bounded capabilities:

- `commerce-core`: product, pricing, inventory, cart, checkout, order, promotion, and merchandising integration points
- `experience-cms`: structured content composition with templates, sections, components, menus, global areas, preview, and publish

The storefront is server-rendered and intentionally structured. Admins can control layout through approved templates, sections, components, and theme presets. They cannot inject arbitrary frontend code or break the design system by bypassing rendering contracts.

## Mission

Build a deployable product, not a one-off website:

- one reusable platform
- one default storefront
- one internal admin and CMS
- one documented delivery package
- many independent downstream installations

## Deployment Model

The platform follows a single-product, many-installations model:

- one source repository
- one release stream
- one platform architecture
- many separate deployments

Every installation owns:

- environment configuration
- database
- media storage
- admin users
- payment credentials
- shipping settings
- tax settings
- content
- visual configuration

There is no shared runtime tenancy layer in v1.

## Structured CMS Positioning

This is a mid-range structured CMS. It provides:

- page templates
- section catalogs
- component catalogs
- schema-backed settings
- global header/footer management
- menu management
- theme presets and tokens
- preview and publish workflow
- basic version snapshots

This is intentionally not a drag-anything-anywhere visual builder.

## Non-Goals

Version 1 explicitly excludes:

- freeform page building
- centralized multi-site runtime administration
- marketplace or multi-vendor behavior
- recommendation engines
- loyalty systems
- arbitrary theme code editing from admin
- unrelated bespoke theme implementations

## Initial Deliverables

The first implementation wave delivers:

- modular repository and package structure
- structured documentation in `Doc/`
- CMS entities and migrations
- section registry with built-in v1 sections
- theme contracts and default theme
- admin CRUD for the first CMS entities
- homepage preview and publish foundation

## Architecture Guardrails

- Keep custom logic inside isolated packages/modules.
- Avoid business logic in Blade templates.
- Prefer schema-backed configuration to ad hoc JSON blobs.
- Preserve stable contracts between stored CMS data and renderers.
- Treat each installation as an independent product deployment.
