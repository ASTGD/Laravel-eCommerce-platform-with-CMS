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

- SSLCOMMERZ `success`, `fail`, `cancel`, and `ipn` URLs must point to the production storefront host
- callback/IPN traffic must reach the application directly over HTTPS
- `sales.payment_methods.sslcommerz_gateway.strict_amount_validation` should remain enabled unless SSLCOMMERZ support requires a different amount contract
- `sales.payment_methods.sslcommerz_gateway.log_payloads` can be disabled in stricter environments if raw callback retention is not desired
- after go-live, verify one real or low-risk production transaction end-to-end:
  - hosted checkout redirect works
  - success callback creates one order
  - invoice is created once
  - repeated redirect/IPN calls do not duplicate fulfillment
