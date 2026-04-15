<?php

namespace Platform\CommerceCore\Listeners;

use Platform\CommerceCore\Services\PaymentRefundService;
use Webkul\Admin\Mail\Order\RefundedNotification;

class Refund extends \Webkul\Admin\Listeners\Refund
{
    public function __construct(
        protected PaymentRefundService $refundService,
    ) {}

    public function afterCreated($refund)
    {
        $this->refundOrder($refund);

        try {
            if (! core()->getConfigData('emails.general.notifications.emails.general.notifications.new_refund_mail_to_admin')) {
                return;
            }

            $this->prepareMail($refund, new RefundedNotification($refund));
        } catch (\Exception $e) {
            report($e);
        }
    }

    public function refundOrder($refund)
    {
        if ($this->refundService->requestRefund($refund)) {

            return;
        }

        parent::refundOrder($refund);
    }
}
