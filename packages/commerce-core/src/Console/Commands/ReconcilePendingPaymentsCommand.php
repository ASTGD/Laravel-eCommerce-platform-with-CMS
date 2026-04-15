<?php

namespace Platform\CommerceCore\Console\Commands;

use Illuminate\Console\Command;
use Platform\CommerceCore\Services\PaymentReconciliationService;

class ReconcilePendingPaymentsCommand extends Command
{
    protected $signature = 'platform:payments:reconcile-pending
        {--provider= : Restrict reconciliation to a single provider (sslcommerz or bkash)}
        {--limit=50 : Maximum number of attempts to reconcile}
        {--older-than= : Only reconcile attempts updated at or before N minutes ago}';

    protected $description = 'Reconcile pending external payment attempts and finalize verified paid orders exactly once.';

    public function __construct(
        protected PaymentReconciliationService $reconciliationService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $result = $this->reconciliationService->reconcilePending(
            provider: $this->option('provider') ?: null,
            limit: (int) $this->option('limit'),
            olderThanMinutes: $this->option('older-than') !== null ? (int) $this->option('older-than') : null,
        );

        $this->components->info('Payment reconciliation run completed.');
        $this->line('Processed: '.$result['processed']);
        $this->line('Paid/Finalized: '.$result['paid']);
        $this->line('Non-paid synced: '.$result['non_paid']);
        $this->line('Errors: '.$result['errors']);

        return self::SUCCESS;
    }
}
