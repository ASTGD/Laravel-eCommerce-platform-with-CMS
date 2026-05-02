<?php

namespace Platform\CommerceCore\Listeners;

use Platform\CommerceCore\Services\OrderInvoiceLifecycleService;
use Webkul\Sales\Models\Order;

class CancelCodInvoiceForCanceledOrder
{
    public function __construct(
        protected OrderInvoiceLifecycleService $orderInvoiceLifecycleService,
    ) {}

    public function handle(Order $order): void
    {
        $this->orderInvoiceLifecycleService->refundCodOrderInvoices($order);
    }
}
