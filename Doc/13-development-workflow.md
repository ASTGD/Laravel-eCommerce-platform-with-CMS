# Development Workflow

## Purpose

This document covers practical local development operations for this repository:

- how to install it locally
- how to start the dev servers
- what URLs to open
- how admin login works
- how to expose the app on a LAN
- what to check during smoke testing

## Verified Runtime

The current verified local bootstrap used:

- PHP `8.3.30`
- Composer `2.9.5`
- MySQL `8.4`
- Redis `7`
- Node `24.11.1`
- npm `11.6.2`

PHP `8.4.x` is also supported by the repository policy. Do not use PHP `8.5.x` yet for the initial bootstrap.

## Local Requirements

- PHP `8.4.x` or `8.3.x`
- Composer `2.5+`
- MySQL `8+`
- Redis
- Node.js LTS
- npm or pnpm

## Asset Workspaces

This repository does not use only one frontend workspace.

Use the correct package depending on what you are changing:

- root `package.json`
  Use for the custom `theme-default` storefront shell
  Output: `public/build`

- `packages/Webkul/Shop/package.json`
  Use for upstream storefront assets
  Output: `public/themes/shop/default/build`

- `packages/Webkul/Admin/package.json`
  Use for upstream admin assets
  Output: `public/themes/admin/default/build`

- `packages/Webkul/Installer/package.json`
  Use for installer UI assets
  Output: `public/themes/installer/default/build`

## Install Flow

1. Copy the environment file:

```bash
cp .env.example .env
```

2. Update `.env` at minimum:

- `APP_URL`
- `APP_ADMIN_URL`
- `APP_TIMEZONE`
- `APP_CURRENCY`
- `DB_*`
- `DB_PREFIX`
- `REDIS_*`
- `CACHE_STORE=redis`
- `SESSION_DRIVER=redis`
- `QUEUE_CONNECTION=redis`

3. Install PHP dependencies:

```bash
composer install
```

4. Run the commerce installer:

```bash
php artisan bagisto:install
```

If `.env` is already complete and you want a non-interactive run:

```bash
php artisan bagisto:install --skip-env-check --skip-github-star --no-interaction
```

5. Seed the neutral platform packages:

```bash
php artisan db:seed --force
```

6. Install and build the root storefront shell:

```bash
npm install
npm run build
```

7. If you are changing upstream Bagisto assets, install those workspaces too:

```bash
npm --prefix packages/Webkul/Shop install
npm --prefix packages/Webkul/Admin install
npm --prefix packages/Webkul/Installer install
```

8. Start the PHP server and any Vite servers you need.

## Recommended `.env` Values For Local Dev

Example:

```dotenv
APP_NAME="Reusable Commerce Platform"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_ADMIN_URL=admin
APP_TIMEZONE=UTC
APP_CURRENCY=USD

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=reusable_commerce
DB_USERNAME=root
DB_PASSWORD=secret
DB_PREFIX=

SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
CACHE_STORE=redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

Admin URL is built from:

- `APP_URL`
- `APP_ADMIN_URL`

With the example above:

- storefront: `http://127.0.0.1:8000`
- admin base: `http://127.0.0.1:8000/admin`
- admin login: `http://127.0.0.1:8000/admin/login`

## How To Start The Dev Server

### Option A: Native PHP + root storefront shell

Start the Laravel app:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Start the root storefront shell Vite server in another terminal:

```bash
npm run dev
```

Open:

- storefront: `http://127.0.0.1:8000`
- admin login: `http://127.0.0.1:8000/admin/login`
- customer login: `http://127.0.0.1:8000/customer/login`

If you are editing upstream Bagisto assets, run those Vite servers separately:

```bash
npm --prefix packages/Webkul/Shop run dev
npm --prefix packages/Webkul/Admin run dev
```

Use the installer Vite server only when you are changing installer UI:

```bash
npm --prefix packages/Webkul/Installer run dev
```

### Option B: Sail / Docker

This repository now includes a working Sail dependency, but Composer must run first so `vendor/bin/sail` and the Sail runtime files exist.

Before starting Sail, set these in `.env` for the containerized path:

- `DB_HOST=mysql`
- `REDIS_HOST=redis`
- `WWWUSER`
- `WWWGROUP`

Start containers:

```bash
./vendor/bin/sail up -d
```

Then run setup commands through Sail:

```bash
./vendor/bin/sail artisan bagisto:install
./vendor/bin/sail artisan db:seed --force
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev -- --host 0.0.0.0 --port 5173
./vendor/bin/sail npm --prefix packages/Webkul/Shop install
./vendor/bin/sail npm --prefix packages/Webkul/Admin install
```

Default exposed ports from `docker-compose.yml`:

- app: `${APP_PORT:-80}`
- Vite: `${VITE_PORT:-5173}`
- MySQL: `${FORWARD_DB_PORT:-3306}`
- Redis: `${FORWARD_REDIS_PORT:-6379}`
- Mailpit SMTP: `${FORWARD_MAILPIT_PORT:-1025}`
- Mailpit UI: `${FORWARD_MAILPIT_DASHBOARD_PORT:-8025}`

## LAN Access

If you want to open the dev app from another device on your local network:

### 1. Find your local IP

macOS:

```bash
ipconfig getifaddr en0
```

If `en0` is empty, try:

```bash
ipconfig getifaddr en1
```

Linux:

```bash
hostname -I
```

### 2. Update `.env`

Example:

```dotenv
APP_URL=http://192.168.1.25:8000
APP_ADMIN_URL=admin
VITE_HOST=0.0.0.0
VITE_PORT=5173
```

### 3. Bind Laravel to all interfaces

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

### 4. Bind Vite to all interfaces

```bash
npm run dev -- --host 0.0.0.0 --port 5173
```

If you are working on upstream Bagisto assets, do the same for the relevant workspace:

```bash
npm --prefix packages/Webkul/Shop run dev -- --host 0.0.0.0 --port 5173
npm --prefix packages/Webkul/Admin run dev -- --host 0.0.0.0 --port 5174
```

### 5. Open from another device

- storefront: `http://192.168.1.25:8000`
- admin login: `http://192.168.1.25:8000/admin/login`

If the page loads but live asset refresh fails on the second device, run a one-time build instead:

```bash
npm run build
```

Then refresh the browser.

## Admin Login

### Admin URL

The admin auth routes are mounted under:

```text
/{APP_ADMIN_URL}
```

Default login path:

```text
/admin/login
```

### Admin Credentials

During `php artisan bagisto:install`, the installer asks for:

- admin name
- admin email
- admin password

At the end of the install, it prints:

- the admin URL
- the admin email
- the admin password

If you accept the installer defaults unchanged, the upstream installer fallback values are:

- name: `Example`
- email: `admin@example.com`
- password: `admin123`

Use those defaults only for local development, and change them immediately in any shared or persistent environment.

## Core Local URLs

Assuming:

- `APP_URL=http://127.0.0.1:8000`
- `APP_ADMIN_URL=admin`

The most useful local URLs are:

- storefront home: `http://127.0.0.1:8000/`
- admin base: `http://127.0.0.1:8000/admin`
- admin login: `http://127.0.0.1:8000/admin/login`
- customer login: `http://127.0.0.1:8000/customer/login`
- customer register: `http://127.0.0.1:8000/customer/register`
- customer account: `http://127.0.0.1:8000/customer/account`
- contact page: `http://127.0.0.1:8000/contact-us`

After `php artisan db:seed --force`, these platform-specific URLs are also useful:

- homepage seed preview: `http://127.0.0.1:8000/home-preview`
- structured CMS preview route: `http://127.0.0.1:8000/preview/pages/{slug}`

## Daily Developer Startup Checklist

1. Start MySQL.
2. Start Redis.
3. Confirm `.env` points to the correct local database.
4. Start Laravel:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

5. Start the root storefront shell Vite server if you need hot reload:

```bash
npm run dev
```

6. Start upstream asset Vite servers only if you are editing those assets.
7. Open:

- storefront home
- admin login
- one customer auth page

## Smoke Test Checklist

After a fresh local boot:

1. Open storefront home.
2. Open admin login.
3. Sign into admin.
4. Confirm catalog pages load.
5. Confirm the custom CMS menu items load in admin.
6. Open the homepage preview route if seeded.
7. Confirm customer login/register pages load.

## Troubleshooting

### `composer install` fails on PHP 8.5

Cause:

- Bagisto `2.4.x` lock file is not currently compatible with PHP `8.5.x`

Fix:

- switch to PHP `8.4.x` or `8.3.x`

### `php artisan bagisto:install --skip-env-check` fails before migration

Cause:

- the upstream installer expects these keys to exist in `.env` before it skips prompts:
  - `APP_TIMEZONE`
  - `APP_CURRENCY`
  - `DB_PREFIX`

Fix:

- make sure your `.env` starts from the current `.env.example`
- do not remove those keys even if you keep the default values

### App loads but CSS/JS does not refresh

Cause:

- the matching Vite workspace is not running, or is not bound correctly for LAN use

Fix:

```bash
npm run dev
```

For LAN:

```bash
npm run dev -- --host 0.0.0.0 --port 5173
```

### Admin page redirects unexpectedly

Check:

- `APP_URL`
- `APP_ADMIN_URL`
- session configuration
- whether you are already authenticated or not

### Another device on LAN cannot open the app

Check:

- Laravel server was started with `--host=0.0.0.0`
- your machine firewall allows inbound access
- both devices are on the same network
- `APP_URL` uses the LAN IP, not `127.0.0.1`
