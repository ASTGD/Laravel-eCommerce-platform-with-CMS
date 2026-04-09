# Installation

## Local Requirements

- PHP 8.4.x or 8.3.x
- Composer 2.5+
- MySQL 8+
- Redis
- Node.js LTS
- npm

Verified local bootstrap versions:

- PHP `8.3.30`
- Composer `2.9.5`
- MySQL `8.4`
- Redis `7`
- Node `24.11.1`
- npm `11.6.2`

## Important Constraint

Do not bootstrap this repository on PHP `8.5.x` until upstream Bagisto `2.4.x` dependencies are confirmed compatible. The current dependency graph still relies on `phpoffice/phpspreadsheet 1.30.2`, which supports `<8.5.0`.

## Setup Flow

1. Install PHP 8.4.x or 8.3.x.
2. Install Composer 2.5+.
3. Copy `.env.example` to `.env`.
4. Configure these minimum `.env` values before running the installer:
   - `APP_URL`
   - `APP_ADMIN_URL`
   - `APP_TIMEZONE`
   - `APP_CURRENCY`
   - `DB_CONNECTION`
   - `DB_HOST`
   - `DB_PORT`
   - `DB_DATABASE`
   - `DB_USERNAME`
   - `DB_PASSWORD`
   - `DB_PREFIX`
   - `REDIS_CLIENT`
   - `REDIS_HOST`
   - `REDIS_PASSWORD`
   - `REDIS_PORT`
   - `CACHE_STORE=redis`
   - `SESSION_DRIVER=redis`
   - `QUEUE_CONNECTION=redis`
5. Run `composer install`.
6. Run the commerce installer:

```bash
php artisan bagisto:install
```

If you want a fully prefilled non-interactive run, use:

```bash
php artisan bagisto:install --skip-env-check --skip-github-star --no-interaction
```

Important:
- the non-interactive `--skip-env-check` path expects the env keys above to already exist
- `APP_TIMEZONE`, `APP_CURRENCY`, and `DB_PREFIX` must be present, even if they use the defaults from `.env.example`

7. Run the neutral platform seeders:

```bash
php artisan db:seed --force
```

8. Build the custom storefront shell assets:

```bash
npm install
npm run build
```

9. If you plan to modify upstream Bagisto assets directly, install their workspace dependencies too:

```bash
npm --prefix packages/Webkul/Shop install
npm --prefix packages/Webkul/Admin install
npm --prefix packages/Webkul/Installer install
```

## Verification

- `php artisan about` succeeds
- `php artisan migrate:status` shows all migrations as ran
- storefront home returns `200`
- admin login returns `200`
- customer login returns `200`
- `home-preview` returns `200`
- `php artisan db:seed --force` succeeds after install
- `npm run build` emits `public/build/assets/*.css` and `public/build/assets/*.js`

## Related Developer Ops Doc

For day-to-day local development details, including:

- how to start the PHP dev server
- how to start the Vite dev server
- LAN access setup
- admin login URLs and credentials behavior
- daily smoke-test URLs

see `Doc/13-development-workflow.md`.
