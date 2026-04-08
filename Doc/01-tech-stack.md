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

## Package Layout

Custom product code lives in neutral packages:

- `packages/commerce-core`
- `packages/experience-cms`
- `packages/theme-core`
- `packages/theme-default`
- `packages/seo-tools`
- `packages/media-tools`
- `packages/platform-support`

## Runtime Assumptions

### Local Development

- PHP 8.4.x or 8.3.x
- Composer 2.5+
- MySQL 8+
- Redis
- Node.js LTS
- npm or pnpm

### Production

- Linux
- Nginx + PHP-FPM
- MySQL
- Redis
- queue worker
- scheduler
- SSL

## Current Bootstrap Status

- Repository foundation source is based on Bagisto `2.4.0`.
- Composer dependency installation is blocked on this machine because the active PHP runtime is `8.5.x` while the upstream lock file includes `phpoffice/phpspreadsheet 1.30.2`, which only supports `<8.5.0`.
- Full install verification must be completed under PHP `8.4.x` or `8.3.x`.
