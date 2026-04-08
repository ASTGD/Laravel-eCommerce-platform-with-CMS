# Development Workflow

## Purpose

This document covers practical local development operations for this repository:

- how to install it locally
- how to start the dev servers
- what URLs to open
- how admin login works
- how to expose the app on a LAN
- what to check during smoke testing

## Important Runtime Constraint

Use PHP `8.4.x` or `8.3.x` for this project.

Do not try to complete the Bagisto `2.4.x` install on PHP `8.5.x` until upstream dependencies are updated. The current lock file includes `phpoffice/phpspreadsheet 1.30.2`, which requires PHP `<8.5.0`.

## Local Requirements

- PHP `8.4.x` or `8.3.x`
- Composer `2.5+`
- MySQL `8+`
- Redis
- Node.js LTS
- npm or pnpm

## Install Flow

1. Copy the environment file:

```bash
cp .env.example .env
```

2. Update `.env` at minimum:

- `APP_URL`
- `APP_ADMIN_URL`
- `DB_*`
- `REDIS_*`

3. Install PHP dependencies:

```bash
composer install
```

4. Generate the app key:

```bash
php artisan key:generate
```

5. Run the commerce installer:

```bash
php artisan bagisto:install
```

6. Install frontend dependencies:

```bash
npm install
```

7. Start the PHP server and asset server.

## Recommended `.env` Values For Local Dev

Example:

```dotenv
APP_NAME="Reusable Commerce Platform"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_ADMIN_URL=admin

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=reusable_commerce
DB_USERNAME=root
DB_PASSWORD=secret

REDIS_HOST=127.0.0.1
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

### Option A: Native PHP + npm

Start the Laravel app:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Start Vite in another terminal:

```bash
npm run dev
```

Open:

- storefront: `http://127.0.0.1:8000`
- admin login: `http://127.0.0.1:8000/admin/login`

### Option B: Sail / Docker

This repository includes a `docker-compose.yml`, but it depends on Composer-installed vendor files. Use this only after `composer install`.

Start containers:

```bash
./vendor/bin/sail up -d
```

Then run setup commands through Sail:

```bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan bagisto:install
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev -- --host 0.0.0.0 --port 5173
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
```

### 3. Bind Laravel to all interfaces

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

### 4. Bind Vite to all interfaces

```bash
npm run dev -- --host 0.0.0.0 --port 5173
```

Important:

- the current `vite.config.js` does not read `VITE_HOST` to change the bind host
- use the CLI flags above for LAN access

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

- name: `Admin`
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

After the custom CMS migrations and seeders are run, these platform-specific URLs are also useful:

- homepage seed preview: `http://127.0.0.1:8000/home-preview`
- structured CMS page route: `http://127.0.0.1:8000/pages/{slug}`
- structured CMS preview route: `http://127.0.0.1:8000/preview/pages/{slug}`

## Daily Developer Startup Checklist

1. Start MySQL.
2. Start Redis.
3. Confirm `.env` points to the correct local database.
4. Start Laravel:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

5. Start Vite:

```bash
npm run dev
```

6. Open:

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

### App loads but CSS/JS does not refresh

Cause:

- Vite dev server is not running, or is not bound correctly for LAN use

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
