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
