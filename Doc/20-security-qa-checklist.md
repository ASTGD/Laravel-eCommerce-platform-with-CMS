# Security QA Checklist

Use this checklist before client handover and after any production configuration change.

## Environment

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL` uses the production HTTPS domain
- `APP_KEY` is unique to the installation
- `SESSION_SECURE_COOKIE=true`
- `SESSION_HTTP_ONLY=true`
- `SESSION_SAME_SITE=lax` or stricter after browser checkout testing
- `SESSION_ENCRYPT=true`
- database and Redis credentials are unique and not weak defaults
- Debugbar is unavailable in production
- `RESPONSE_CACHE_ENABLED=false` until cache QA has passed; if enabled, protected route exclusions must remain active

## Sessions And Cache

- Admin logout redirects to the admin login page.
- Reloading `/admin/*` after admin logout or session expiry redirects to login and does not render dashboard content.
- Customer logout redirects away from account pages.
- Reloading customer account, checkout, order, and sensitive AJAX/datagrid routes after logout or session expiry does not render authenticated content.
- Protected responses include `Cache-Control` with `no-store`.
- Response cache never serves admin, customer account, checkout, cart, order, payment, webhook, or authenticated responses.

## Authentication

- Admin login is throttled after repeated failed attempts.
- Customer login is throttled after repeated failed attempts.
- Customer registration and password-reset requests are throttled.
- Successful admin and customer login regenerates the session.
- Inactive or unverified users remain blocked.
- Password reset completion is logged.
- Privileged admin two-factor policy is documented before enforcement.

## Ownership

- Customers cannot view another customer's orders, invoices, downloads, GDPR exports, addresses, wishlist items, reviews, or returns.
- Review submission is only allowed for eligible delivered order items.
- Admin-only routes require the expected ACL permission.
- Feature-toggle middleware blocks disabled commerce features.

## Payments And Webhooks

- SSLCommerz callbacks are verified server-side.
- bKash callbacks are verified server-side.
- Payment callback amount, currency, method, and transaction identifiers match server records.
- Duplicate payment callbacks do not create duplicate orders, invoices, or transactions.
- Webhook endpoints are CSRF-exempt only where an external provider cannot send CSRF tokens.
- Courier webhooks reject missing or invalid tokens with `401` or `422`.
- Courier webhooks do not echo configured webhook secrets.
- Payment and courier callback received, accepted/finalized, and failed/rejected events are written to the security audit log.

## Uploads And Documents

- Review attachments reject executable/script files.
- Review attachments reject SVG unless a safe sanitizer path is explicitly approved.
- Review attachments enforce extension, MIME type, and max-size rules.
- Sensitive generated documents are downloaded only through authenticated owner/admin routes.
- Another customer cannot download someone else's invoice, downloadable product file, GDPR export, or private report.
- Temporary sensitive exports have an expiry or cleanup plan.

## Headers

- `X-Content-Type-Options: nosniff` is present.
- `X-Frame-Options: DENY` or a matching `frame-ancestors` policy is present.
- HSTS is present only for production HTTPS requests.
- CSP is in report-only mode until violations are reviewed.
- Admin and storefront assets still load with CSP report-only enabled.
