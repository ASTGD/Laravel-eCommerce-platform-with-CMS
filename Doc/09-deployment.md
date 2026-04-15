# Deployment

## Production Assumptions

- Linux server
- Nginx + PHP-FPM
- MySQL
- Redis
- supervisor or equivalent queue worker management
- scheduler enabled
- SSL configured

## Repeatable Deployment Rules

- one database per install
- one `.env` per install
- one storage/media space per install
- one payment/shipping credential set per install

## Post-Deploy Checks

- migrations ran successfully
- queue worker is consuming jobs
- scheduler is running
- admin is reachable
- storefront is reachable
- CMS homepage renders
- mail and media storage are configured

## Bangladesh Payment Deployment Notes

- SSLCommerz `success`, `fail`, `cancel`, and `ipn` URLs must point to the production storefront host
- callback/IPN traffic must reach the application directly over HTTPS
- `sales.payment_methods.sslcommerz_gateway.strict_amount_validation` should remain enabled unless SSLCommerz support requires a different amount contract
- `sales.payment_methods.sslcommerz_gateway.log_payloads` can be disabled in stricter environments if raw callback retention is not desired
- direct bKash credentials must be configured separately from SSLCommerz:
  - `sales.payment_methods.bkash_gateway.username`
  - `sales.payment_methods.bkash_gateway.password`
  - `sales.payment_methods.bkash_gateway.app_key`
  - `sales.payment_methods.bkash_gateway.app_secret`
  - correct sandbox/live `base_url`
- the direct bKash callback URL must point to the production storefront host:
  - `/payment/bkash/bkash/callback`
- direct bKash should stay in `sandbox` mode until the merchant account is ready for live tokenized checkout
- after go-live, verify one real or low-risk production transaction end-to-end:
  - hosted checkout redirect works
  - success callback creates one order
  - invoice is created once
  - repeated redirect/IPN calls do not duplicate fulfillment
- after enabling direct bKash, verify one end-to-end bKash payment separately from SSLCommerz:
  - token grant succeeds
  - create payment returns a `bkashURL`
  - success callback executes or queries the payment once
  - duplicate callbacks do not duplicate fulfillment
- after refund enablement, verify one controlled refund in the merchant environment:
  - refund request is accepted by the configured gateway
  - admin order view shows refund history and status
  - customer order detail shows the refund reference and status
  - pending refunds can be refreshed without creating duplicate local refund records
- after enabling direct bKash refunds, verify one controlled bKash refund separately:
  - refund request returns a `refundTrxID`
  - the order detail shows the bKash refund reference
  - refund-status refresh keeps the local refund row aligned with the gateway response
- when payment state looks stale, admin can reconcile a single order from `Sales > Orders` or review the full payment attempt timeline in `Sales > Payments`
- the platform now exposes a reconciliation command for pending external payment attempts:
  - `php artisan platform:payments:reconcile-pending`
  - `php artisan platform:payments:reconcile-pending --provider=sslcommerz`
  - `php artisan platform:payments:reconcile-pending --provider=bkash`
  - run it manually first before scheduling it automatically
