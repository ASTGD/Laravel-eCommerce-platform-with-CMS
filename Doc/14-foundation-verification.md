# Foundation Verification

## Scope

This pass verified the repository foundation before any additional CMS or storefront feature work.

Goals covered:

- prove the repo is on Laravel 12 with Bagisto as the commerce core
- synchronize dependencies and lock files
- verify local installability on a supported PHP runtime
- align docs with the actual verified workflow

## Verified Foundation Facts

- Root `composer.json` targets Laravel 12 through `laravel/framework:^12.0`
- Bagisto source packages are present in-repo under `packages/Webkul/*`
- Root PSR-4 autoload maps the Webkul packages explicitly
- Neutral custom packages are required through path repositories and are installed correctly
- `bootstrap/providers.php` loads both the Webkul providers and the neutral platform providers
- `database/seeders/DatabaseSeeder.php` now:
  - seeds Bagisto only when the core catalog data is missing
  - always seeds the neutral theme and CMS packages
- `laravel/sail` is installed, so the checked-in `docker-compose.yml` is now a valid dev path after Composer install
- The root custom storefront shell now has a working Tailwind build

## Runtime Used For Verification

- Date: `2026-04-09`
- PHP: `8.3.30`
- Composer: `2.9.5`
- MySQL: `8.4`
- Redis: `7`
- Node.js: `24.11.1`
- npm: `11.6.2`

Verification was executed through a temporary Docker PHP 8.3 image with the required PHP extensions installed.

## What Was Run

- `composer update --no-interaction`
- `composer require laravel/sail --dev --no-interaction`
- `composer install --no-interaction`
- Composer package discovery
- `php artisan about`
- `php artisan bagisto:install --skip-env-check --skip-github-star --no-interaction`
- `php artisan optimize:clear`
- `php artisan db:seed --force`
- `php artisan migrate:status`
- `php artisan route:list`
- `npm install`
- `npm run build`
- smoke HTTP requests against:
  - `/`
  - `/admin/login`
  - `/customer/login`
  - `/home-preview`
- smoke tests:
  - `packages/Webkul/Admin/tests/Feature/ExampleTest.php`
  - `packages/Webkul/Installer/tests/Feature/InstallerSecurityTest.php`

## Outcomes

- `composer install` now succeeds on PHP `8.3.30`
- `composer.lock` is synchronized with the root package definitions
- Bagisto installation completed successfully
- the database migrated successfully on MySQL
- the neutral platform seeders completed successfully after install
- storefront, admin login, customer login, and homepage preview returned `200 OK`
- root storefront assets now compile to both CSS and JS build artifacts
- smoke tests passed: `10` tests, `19` assertions

## Foundation Cleanup Applied

- added `laravel/sail` to dev dependencies
- removed the stale root seeder behavior that duplicated Bagisto seed data after install
- wired Tailwind CSS into the root custom storefront shell
- made the root Vite config honor `VITE_HOST` and `VITE_PORT`
- updated `.env.example` to reflect the verified Redis-based local defaults
- updated installation and development docs to match the verified workflow
- added this verification record

## Remaining Notes

- PHP `8.5.x` is still out of scope for the install path because of upstream dependency constraints
- the non-interactive Bagisto installer still assumes `APP_TIMEZONE`, `APP_CURRENCY`, and `DB_PREFIX` exist in `.env` when `--skip-env-check` is used
- root `npm install` reported `2` moderate audit warnings in dev dependencies; this did not block the verified build
- upstream Admin, Shop, and Installer asset workspaces remain separate from the root storefront shell and should be managed with their own package manifests when those assets are edited

## Recommended Next Milestone

Proceed to Milestone 2 only after using the verified install flow from:

- `Doc/08-installation.md`
- `Doc/13-development-workflow.md`

Recommended next workstream:

- continue the structured CMS vertical slice on top of the now-verified Laravel 12 + Bagisto foundation
