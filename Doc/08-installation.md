# Installation

## Local Requirements

- PHP 8.4.x or 8.3.x
- Composer 2.5+
- MySQL 8+
- Redis
- Node.js LTS

## Important Constraint

Do not bootstrap this repository on PHP `8.5.x` until upstream Bagisto `2.4.x` dependencies are confirmed compatible. Current lock-file installation is blocked by `phpoffice/phpspreadsheet 1.30.2`.

## Setup Flow

1. Install PHP 8.4.x or 8.3.x.
2. Install Composer 2.5+.
3. Copy `.env.example` to `.env`.
4. Configure database, Redis, mail, and admin URL values.
5. Run `composer install`.
6. Run `php artisan key:generate`.
7. Run `php artisan bagisto:install`.
8. Run `npm install` and `npm run build`.
9. Run database seeders for platform packages if not already included.

## Verification

- app boots
- admin login works
- storefront loads
- homepage CMS seed renders

## Related Developer Ops Doc

For day-to-day local development details, including:

- how to start the PHP dev server
- how to start the Vite dev server
- LAN access setup
- admin login URLs and credentials behavior
- daily smoke-test URLs

see `Doc/13-development-workflow.md`.
