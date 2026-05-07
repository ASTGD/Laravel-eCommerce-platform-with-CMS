<?php

namespace Platform\CommerceCore\Console\Commands;

use Illuminate\Console\Command;
use Platform\CommerceCore\Services\PendingPaymentExpiryService;

class ExpirePendingPaymentsCommand extends Command
{
    protected $signature = 'platform:payments:expire-pending
        {--provider= : Restrict expiry to a single provider (sslcommerz or bkash)}
        {--limit=50 : Maximum number of attempts to process}
        {--expire-after= : Expire unresolved pending payments after N minutes}
        {--reconcile-older-than= : Reconcile again only if the last reconciliation is older than N minutes}';

    protected $description = 'Expire stale unpaid online payment attempts and cancel linked pending-payment orders.';

    public function __construct(
        protected PendingPaymentExpiryService $expiryService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $result = $this->expiryService->expirePending(
            provider: $this->option('provider') ?: null,
            limit: (int) $this->option('limit'),
            expireAfterMinutes: $this->option('expire-after') !== null ? (int) $this->option('expire-after') : null,
            reconcileOlderThanMinutes: $this->option('reconcile-older-than') !== null ? (int) $this->option('reconcile-older-than') : null,
        );

        if ($result['disabled']) {
            $this->components->warn('Pending payment expiry is disabled.');

            return self::SUCCESS;
        }

        $this->components->info('Pending payment expiry run completed.');
        $this->line('Processed: '.$result['processed']);
        $this->line('Reconciled: '.$result['reconciled']);
        $this->line('Paid/Finalized: '.$result['paid']);
        $this->line('Expired/Handled Attempts: '.$result['expired_attempts']);
        $this->line('Cancelled Orders: '.$result['cancelled_orders']);
        $this->line('Skipped: '.$result['skipped']);
        $this->line('Errors: '.$result['errors']);

        return self::SUCCESS;
    }
}
