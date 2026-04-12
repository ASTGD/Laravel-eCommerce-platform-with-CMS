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

The recommended local path for this repository is Sail.

1. Install PHP 8.4.x or 8.3.x.
2. Install Composer 2.5+.
3. Install Docker Desktop.
4. Copy `.env.example` to `.env`.
5. Use the Sail-oriented defaults from `.env.example` unless you have a concrete reason to change them.
6. Configure these minimum `.env` values before running the installer:
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
7. Run `composer install`.
8. Start the containers:

```bash
composer run dev:sail-up
```

9. Run the commerce installer through Sail:

```bash
./vendor/bin/sail artisan bagisto:install
```

If you want a fully prefilled non-interactive run, use:

```bash
./vendor/bin/sail artisan bagisto:install --skip-env-check --skip-github-star --no-interaction
```

Important:
- the non-interactive `--skip-env-check` path expects the env keys above to already exist
- `APP_TIMEZONE`, `APP_CURRENCY`, and `DB_PREFIX` must be present, even if they use the defaults from `.env.example`

10. Run the neutral platform seeders:

```bash
./vendor/bin/sail artisan db:seed --force
```

In `local` environment this also seeds a small ASTGD demo catalog and an AliExpress shirt sample through `database/seeders/SampleCatalogSeeder.php`, so you have usable sample products, image swatches, and a demo category for smoke testing. The seeded category is `/mens-shirts`.

11. Install frontend dependencies:

```bash
./vendor/bin/sail npm install
```

12. If you plan to modify upstream Bagisto assets directly, install their workspace dependencies too:

```bash
./vendor/bin/sail npm --prefix packages/Webkul/Shop install
./vendor/bin/sail npm --prefix packages/Webkul/Admin install
./vendor/bin/sail npm --prefix packages/Webkul/Installer install
```

13. Build the root storefront shell if you are not using Vite watch mode:

```bash
./vendor/bin/sail npm run build
```

## Known Good Local Commands

Use these exact commands for the verified containerized path:

```bash
composer run dev:sail-up
composer run dev:sail-install
composer run dev:sail-vite
composer run dev:sail-worker
composer run dev:sail-schedule
composer run dev:sail-down
```

Important:

- once `.env` uses `DB_HOST=mysql` and `REDIS_HOST=redis`, prefer `./vendor/bin/sail artisan ...` over host `php artisan ...`
- host artisan commands can fail because the service hostnames only resolve inside the Docker network

## Verification

- `./vendor/bin/sail artisan about` succeeds
- `./vendor/bin/sail artisan migrate:status` shows all migrations as ran
- storefront home returns `200`
- admin login returns `200`
- customer login returns `200`
- `home-preview` returns `200` when `EXPERIENCE_CMS_STOREFRONT_MODE=cms`
- `./vendor/bin/sail artisan db:seed --force` succeeds after install
- `./vendor/bin/sail npm run build` emits `public/build/assets/*.css` and `public/build/assets/*.js`

## Related Developer Ops Doc

For day-to-day local development details, including:

- how to start the PHP dev server
- how to start the Vite dev server
- LAN access setup
- admin login URLs and credentials behavior
- daily smoke-test URLs

see `Doc/13-development-workflow.md`.
