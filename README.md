# Commerce Platform

Reusable Laravel commerce platform with a structured CMS, configuration-driven theme presets, and a default storefront that can be deployed as fully standalone installations.

## Scope

- One engineered product
- Multiple independent installations
- Separate database, domain, storage, admin users, catalog, content, and settings per installation
- Structured CMS with templates, sections, components, presets, and publish workflow
- Server-rendered storefront with a reusable rendering engine

## Repository Shape

- `packages/commerce-core`: commerce domain boundaries and catalog integration hooks
- `packages/experience-cms`: structured CMS, page composition, admin flows, preview, publish
- `packages/theme-core`: theme contracts, preset resolution, rendering support
- `packages/theme-default`: default storefront theme and baseline renderers
- `packages/seo-tools`: SEO metadata and URL support
- `packages/media-tools`: media abstraction and upload policies
- `packages/platform-support`: shared enums, audit logging, bootstrap helpers
- `themes/`: preset assets and token references
- `Doc/`: product, architecture, delivery, and handoff documentation

## Local Bootstrap

```bash
php artisan migrate --seed
php artisan serve
```

Default admin credentials are resolved from:

- `PLATFORM_ADMIN_EMAIL`
- `PLATFORM_ADMIN_PASSWORD`

If those values are not set, the seeder provisions a development-only super admin using the defaults described in [Doc/06-installation.md](/Users/shafin/Documents/Laravel-eCommerce-platform-with-CMS/Doc/06-installation.md).

## Documentation

Start with:

- [Overview](/Users/shafin/Documents/Laravel-eCommerce-platform-with-CMS/Doc/00-overview.md)
- [Architecture](/Users/shafin/Documents/Laravel-eCommerce-platform-with-CMS/Doc/01-architecture.md)
- [Milestones](/Users/shafin/Documents/Laravel-eCommerce-platform-with-CMS/Doc/09-milestones.md)
