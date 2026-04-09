# Milestones

## Milestone 1: Correct Foundation

Status: verified complete.

Verified deliverables:

- Bagisto-based Laravel 12 source tree is in the repository
- neutral package boundaries are present and autoloaded
- `composer.lock` is synchronized with `composer.json`
- `composer install` and package discovery were verified on PHP `8.3.30`
- local install and boot were verified on MySQL `8.4` and Redis `7`
- the root Tailwind/Vite pipeline for the custom storefront shell is wired and built
- Docker/Sail is now a valid dev path because `laravel/sail` is installed
- baseline docs and the foundation verification report are written

## Milestone 2: CMS Core Vertical Slice

- page, template, section, and menu entities
- preview/publish basics
- homepage seed with core sections

## Milestone 3: Theme Core and Default Theme

- preset resolution
- section rendering pipeline
- default storefront page renderer

## Milestone 4: Commerce-Aware CMS

- product-backed sections
- category-backed sections
- configurable listing and PDP presentation

## Milestone 5: Customer Portal and Hardening

- storefront account area
- ACL refinement
- deployment hardening
- broader tests

## Next Recommended Milestone

Resume Milestone 2 work only after using the verified install path from `Doc/08-installation.md` and `Doc/13-development-workflow.md`.
