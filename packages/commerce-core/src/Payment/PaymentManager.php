<?php

namespace Platform\CommerceCore\Payment;

use Illuminate\Support\Facades\Config;
use Platform\CommerceCore\Support\PaymentChannel;
use Platform\CommerceCore\Support\PaymentMethodRegistry;
use Webkul\Payment\Payment as BasePaymentManager;

class PaymentManager extends BasePaymentManager
{
    public function getPaymentMethods()
    {
        $paymentMethods = [];

        foreach (Config::get('payment_methods') as $paymentMethodConfig) {
            $paymentMethod = app($paymentMethodConfig['class']);

            if (! $paymentMethod->isAvailable()) {
                continue;
            }

            if (! $this->shouldExposeMethod($paymentMethod->getCode())) {
                continue;
            }

            $paymentMethods[] = [
                'method'       => $paymentMethod->getCode(),
                'method_title' => $paymentMethod->getTitle(),
                'description'  => $paymentMethod->getDescription(),
                'sort'         => $paymentMethod->getSortOrder(),
                'image'        => $paymentMethod->getImage(),
            ];
        }

        usort($paymentMethods, function ($a, $b) {
            if ($a['sort'] == $b['sort']) {
                return 0;
            }

            return $a['sort'] < $b['sort'] ? -1 : 1;
        });

        return $paymentMethods;
    }

    protected function shouldExposeMethod(string $code): bool
    {
        if ($code === 'cashondelivery') {
            return true;
        }

        if (PaymentMethodRegistry::isHiddenLegacyCode($code)) {
            return false;
        }

        $mode = PaymentChannel::mode();

        if ($mode === PaymentChannel::CUSTOM) {
            return in_array($code, PaymentMethodRegistry::customStorefrontCodes(), true);
        }

        return ! in_array($code, PaymentMethodRegistry::customStorefrontCodes(), true);
    }
}
