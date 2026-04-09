# Tech Stack

## Locked Foundation

- PHP 8.3+
- Laravel 12.x
- Bagisto 2.4.x as the commerce core
- MySQL 8.0.32+ minimum, target MySQL 8.4 LTS
- Redis for cache, queue, and session
- Blade + Vue.js
- Vite
- Tailwind CSS
- Server-rendered storefront

Verified foundation versions in this repository:

- Laravel `12.56.0`
- PHP `8.3.30` for the completed verification pass
- Composer `2.9.5`
- MySQL `8.4`
- Redis `7`
- Node.js `24.11.1`
- npm `11.6.2`

## Package Layout

Custom product code lives in neutral packages:

- `packages/commerce-core`
- `packages/experience-cms`
- `packages/theme-core`
- `packages/theme-default`
- `packages/seo-tools`
- `packages/media-tools`
- `packages/platform-support`

Upstream commerce packages live in-repo under `packages/Webkul/*` and are autoloaded from the root `composer.json`.

## Frontend Build Topology

The frontend stack is split into multiple Vite workspaces:

- root `package.json`
  Purpose: custom `theme-default` storefront shell
  Stack: Vite 5, Tailwind CSS 3, PostCSS, Axios

- `packages/Webkul/Shop/package.json`
  Purpose: upstream storefront asset workspace
  Stack: Vite 5, Vue 3, Tailwind CSS 3, PostCSS

- `packages/Webkul/Admin/package.json`
  Purpose: upstream admin asset workspace
  Stack: Vite 5, Vue 3, Tailwind CSS 3, PostCSS

- `packages/Webkul/Installer/package.json`
  Purpose: installer asset workspace
  Stack: Vite 5, Vue 3, Tailwind CSS 3, PostCSS

The custom storefront shell now has a real Tailwind build at the root level through:

- `tailwind.config.js`
- `postcss.config.cjs`
- `resources/css/app.css`
- `vite.config.js`

## Runtime Assumptions

### Local Development

- PHP 8.4.x or 8.3.x
- Composer 2.5+
- MySQL 8+
- Redis
- Node.js LTS
- npm or pnpm
- optional Docker + Sail for containerized local work

### Production

- Linux
- Nginx + PHP-FPM
- MySQL
- Redis
- queue worker
- scheduler
- SSL

## Current Verification Status

- `composer install` succeeds on PHP `8.3.30`
- Composer package discovery succeeds
- `php artisan bagisto:install --skip-env-check --skip-github-star --no-interaction` succeeds when the required env keys are prefilled
- `php artisan db:seed --force` now runs only the neutral platform seeders after installation
- `php artisan about`, `php artisan migrate:status`, and `php artisan route:list` succeed
- live HTTP requests to `/`, `/admin/login`, `/customer/login`, and `/home-preview` returned `200 OK`
- the root storefront build now emits both JS and CSS assets via `npm run build`

## Docker Tooling

`laravel/sail` is installed as a dev dependency and the checked-in `docker-compose.yml` now resolves correctly.

For a fresh clone, Composer must still run first so that:

- `vendor/bin/sail` exists
- `vendor/laravel/sail/runtimes/8.3` exists for the Docker build context
