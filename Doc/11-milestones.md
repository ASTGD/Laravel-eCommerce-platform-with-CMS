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
- Docker/Sail is a valid dev path because `laravel/sail` is installed
- baseline docs and the foundation verification report are written

## Milestone 2: CMS Core Vertical Slice

Status: implemented and validated.

Validated deliverables:

- core CMS entities are persisted with working relationships
- pages can assign templates, header configs, footer configs, menus, SEO meta, and theme presets
- page sections are authored as approved structured records
- template areas are synchronized from template schema
- section type and component type registries are active
- publish and unpublish record page version snapshots
- signed preview works for draft content
- published homepage rendering flows through the theme layer
- featured products resolve from live commerce data through the commerce integration layer
- admin CRUD screens exist for pages, templates, section types, component types, menus, header configs, footer configs, and theme presets
- homepage seed proves one working end-to-end vertical slice

Validation completed during this milestone:

- `composer dump-autoload`
- `php artisan package:discover --ansi`
- `php artisan migrate --force`
- `php artisan db:seed --force`
- focused CMS feature tests
- live HTTP checks for published homepage, signed homepage preview, admin login, and customer login

## Milestone 3: Theme Core And Default Theme

Next target.

Planned deliverables:

- stronger render contracts and view models
- broader preset token application
- improved section/component render separation
- static content page rendering parity
- global area presentation hardening

## Milestone 4: Commerce-Aware CMS

Planned deliverables:

- category-aware CMS sections
- richer merchandising sources
- configurable category listing presentation
- controlled PDP block composition

## Milestone 5: Customer Portal And Hardening

Planned deliverables:

- storefront account area completion
- ACL refinement
- SEO/media admin hardening
- broader automated coverage
- deployment hardening

## Next Recommended Milestone

Proceed to Milestone 3 while preserving the Milestone 2 vertical slice as the reference path for:

- structured page composition
- preview/publish workflow
- homepage resolution
- theme assignment resolution
