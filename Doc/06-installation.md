# 06 Installation

## Local Setup

- PHP 8.3+
- SQLite for quick local bootstrap or MySQL 8+ for parity with production
- queue and cache drivers configurable through `.env`

## Initial Steps

1. Copy `.env.example` to `.env`.
2. Set application, database, cache, queue, mail, and storage values.
3. Run `php artisan migrate --seed`.
4. Start the app with `php artisan serve`.

## Default Development Admin

Unless overridden, local bootstrap uses:

- email: `admin@example.test`
- password: `password`

These values are intended for local development only and should be overridden immediately outside development environments.
