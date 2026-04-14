<?php

namespace Platform\CommerceCore\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleXMLElement;
use Platform\CommerceCore\Support\PaymentChannel;
use Webkul\Checkout\Facades\Cart;
use Webkul\Payment\Payment\Payment;

abstract class AbstractSslCommerzPayment extends Payment
{
    protected array $gatewayTypes = [];

    public function getRedirectUrl()
    {
        return route('commerce-core.sslcommerz.redirect', ['code' => $this->getCode()]);
    }

    public function isAvailable()
    {
        return parent::isAvailable()
            && PaymentChannel::mode() === PaymentChannel::CUSTOM
            && $this->hasValidCredentials();
    }

    public function getTitle()
    {
        return $this->getConfigData('title') ?: config('payment_methods.'.$this->getCode().'.title');
    }

    public function getDescription()
    {
        return $this->getConfigData('description') ?: config('payment_methods.'.$this->getCode().'.description');
    }

    public function getImage()
    {
        $url = $this->getConfigData('image');

        return $url ? Storage::url($url) : bagisto_asset('images/money-transfer.png', 'shop');
    }

    public function getSortOrder()
    {
        return (int) ($this->getConfigData('sort') ?: config('payment_methods.'.$this->getCode().'.sort', 99));
    }

    public function hasValidCredentials(): bool
    {
        return (bool) ($this->getStoreId() && $this->getStorePassword());
    }

    public function isSandbox(): bool
    {
        return (bool) core()->getConfigData('sales.payment_methods.sslcommerz_gateway.sandbox');
    }

    public function getStoreId(): ?string
    {
        return core()->getConfigData('sales.payment_methods.sslcommerz_gateway.store_id');
    }

    public function getStorePassword(): ?string
    {
        return core()->getConfigData('sales.payment_methods.sslcommerz_gateway.store_password');
    }

    public function getPreferredGatewayCode(): ?string
    {
        return $this->getConfigData('gateway_code');
    }

    public function getRequestTimeout(): int
    {
        return (int) (core()->getConfigData('sales.payment_methods.sslcommerz_gateway.request_timeout') ?: 30);
    }

    public function makeMerchantTransactionId($cart): string
    {
        return sprintf(
            'cart_%d_%s',
            $cart->id,
            strtoupper(Str::random(16))
        );
    }

    public function createSession($cart = null, ?string $transactionId = null): array
    {
        $cart ??= Cart::getCart();

        $billingAddress = $cart->billing_address;
        $shippingAddress = $cart->shipping_address ?: $billingAddress;
        $transactionId ??= $this->makeMerchantTransactionId($cart);

        $payload = [
            'store_id'     => $this->getStoreId(),
            'store_passwd' => $this->getStorePassword(),
            'total_amount' => number_format((float) $cart->base_grand_total, 2, '.', ''),
            'currency'     => strtoupper($cart->base_currency_code ?? core()->getBaseCurrencyCode()),
            'tran_id'      => $transactionId,
            'success_url'  => route('commerce-core.sslcommerz.success', ['code' => $this->getCode()]),
            'fail_url'     => route('commerce-core.sslcommerz.fail', ['code' => $this->getCode()]),
            'cancel_url'   => route('commerce-core.sslcommerz.cancel', ['code' => $this->getCode()]),
            'ipn_url'      => route('commerce-core.sslcommerz.ipn', ['code' => $this->getCode()]),
            'cus_name'     => trim(($billingAddress->first_name ?? '').' '.($billingAddress->last_name ?? '')),
            'cus_email'    => $billingAddress->email ?? $cart->customer_email,
            'cus_add1'     => $billingAddress->address[0] ?? '',
            'cus_add2'     => $billingAddress->address[1] ?? '',
            'cus_city'     => $billingAddress->city ?? '',
            'cus_state'    => $billingAddress->state ?? '',
            'cus_postcode' => $billingAddress->postcode ?? '',
            'cus_country'  => $billingAddress->country ?? 'BD',
            'cus_phone'    => $billingAddress->phone ?? '',
            'ship_name'    => trim(($shippingAddress->first_name ?? '').' '.($shippingAddress->last_name ?? '')),
            'ship_add1'    => $shippingAddress->address[0] ?? '',
            'ship_add2'    => $shippingAddress->address[1] ?? '',
            'ship_city'    => $shippingAddress->city ?? '',
            'ship_state'   => $shippingAddress->state ?? '',
            'ship_postcode'=> $shippingAddress->postcode ?? '',
            'ship_country' => $shippingAddress->country ?? 'BD',
            'product_name' => 'Order #'.$cart->id,
            'product_category' => 'ecommerce',
            'product_profile'  => 'general',
            'value_a'      => (string) $cart->id,
            'value_b'      => $this->getCode(),
        ];

        $response = Http::asForm()
            ->timeout($this->getRequestTimeout())
            ->post($this->getGatewayBaseUrl().'/gwprocess/v3/api.php', $payload)
            ->throw()
            ->json();

        if (($response['status'] ?? null) !== 'SUCCESS') {
            throw new \RuntimeException($response['failedreason'] ?? 'Unable to initiate SSLCOMMERZ payment session.');
        }

        return $response;
    }

    public function resolveRedirectUrl(array $sessionResponse): string
    {
        $gatewayCode = $this->getPreferredGatewayCode();

        foreach ($sessionResponse['desc'] ?? [] as $gateway) {
            if ($gatewayCode && ($gateway['gw'] ?? null) === $gatewayCode) {
                return $gateway['redirectGatewayURL']
                    ?? (! empty($sessionResponse['redirectGatewayURL']) && ! empty($gateway['gw'])
                        ? $sessionResponse['redirectGatewayURL'].$gateway['gw']
                        : ($sessionResponse['GatewayPageURL'] ?? ''));
            }

            if (
                $this->gatewayTypes
                && in_array($gateway['type'] ?? null, $this->gatewayTypes, true)
            ) {
                return $gateway['redirectGatewayURL']
                    ?? (! empty($sessionResponse['redirectGatewayURL']) && ! empty($gateway['gw'])
                        ? $sessionResponse['redirectGatewayURL'].$gateway['gw']
                        : ($sessionResponse['GatewayPageURL'] ?? ''));
            }
        }

        return $sessionResponse['GatewayPageURL']
            ?? $sessionResponse['redirectGatewayURL']
            ?? '';
    }

    public function validateTransaction(array $payload): array
    {
        if (! empty($payload['verify_sign']) && ! $this->isHashValid($payload)) {
            throw new \RuntimeException('SSLCOMMERZ hash verification failed.');
        }

        $query = [
            'store_id'     => $this->getStoreId(),
            'store_passwd' => $this->getStorePassword(),
            'v'            => 1,
            'format'       => 'json',
        ];

        if (! empty($payload['val_id'])) {
            $query['val_id'] = $payload['val_id'];

            $url = $this->getGatewayBaseUrl().'/validator/api/validationserverAPI.php';
        } elseif (! empty($payload['tran_id'])) {
            $query['tran_id'] = $payload['tran_id'];

            $url = $this->getGatewayBaseUrl().'/validator/api/merchantTransIDvalidationAPI.php';
        } else {
            throw new \RuntimeException('SSLCOMMERZ did not return a validation id or transaction id.');
        }

        return Http::timeout($this->getRequestTimeout())
            ->get($url, $query)
            ->throw()
            ->json();
    }

    public function matchesSuccessfulStatus(array $validated): bool
    {
        return in_array(strtoupper((string) ($validated['status'] ?? '')), ['VALID', 'VALIDATED'], true);
    }

    public function refundTransaction(string $bankTransactionId, float $amount, string $remarks, string $referenceId): array
    {
        return $this->callRefundOperation('initiateRefund', [
            'bank_tran_id' => $bankTransactionId,
            'refund_amount' => round($amount, 2),
            'refund_remarks' => $remarks,
            'refe_id' => $referenceId,
            'store_Id' => $this->getStoreId(),
            'store_Passwd' => $this->getStorePassword(),
        ]);
    }

    public function queryRefundStatus(string $refundReferenceId): array
    {
        return $this->callRefundOperation('inquiryRefund', [
            'refund_ref_id' => $refundReferenceId,
            'store_Id' => $this->getStoreId(),
            'store_Passwd' => $this->getStorePassword(),
        ]);
    }

    protected function isHashValid(array $payload): bool
    {
        if (empty($payload['verify_key']) || empty($payload['verify_sign'])) {
            return false;
        }

        $keys = explode(',', $payload['verify_key']);
        $hashData = [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $payload)) {
                $hashData[$key] = $payload[$key];
            }
        }

        $hashData['store_passwd'] = md5((string) $this->getStorePassword());

        ksort($hashData);

        return md5(http_build_query($hashData, '', '&', PHP_QUERY_RFC3986)) === $payload['verify_sign'];
    }

    protected function getGatewayBaseUrl(): string
    {
        return $this->isSandbox()
            ? 'https://sandbox.sslcommerz.com'
            : 'https://securepay.sslcommerz.com';
    }

    protected function callRefundOperation(string $operation, array $arguments): array
    {
        $payload = $this->buildSoapEnvelope($operation, $arguments);

        $response = Http::withHeaders([
            'Content-Type' => 'text/xml; charset=utf-8',
            'SOAPAction' => 'urn:validationquote#'.$operation,
        ])
            ->timeout($this->getRequestTimeout())
            ->withBody($payload, 'text/xml; charset=utf-8')
            ->post($this->getGatewayBaseUrl().'/validator/api/merchantTransIDvalidationAPI.php')
            ->throw()
            ->body();

        return $this->decodeSoapJsonResponse($response);
    }

    protected function buildSoapEnvelope(string $operation, array $arguments): string
    {
        $argumentXml = collect($arguments)
            ->map(fn ($value, $key) => sprintf('<urn:%s>%s</urn:%s>', $key, e((string) $value), $key))
            ->implode('');

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:validationquote">
    <soapenv:Header/>
    <soapenv:Body>
        <urn:{$operation}>
            {$argumentXml}
        </urn:{$operation}>
    </soapenv:Body>
</soapenv:Envelope>
XML;
    }

    protected function decodeSoapJsonResponse(string $response): array
    {
        $xml = new SimpleXMLElement($response);
        $returnNode = $xml->xpath('//*[local-name()="return"]')[0] ?? null;

        if (! $returnNode) {
            throw new \RuntimeException('SSLCOMMERZ refund response did not include a return payload.');
        }

        $decoded = json_decode((string) $returnNode, true);

        if (! is_array($decoded)) {
            throw new \RuntimeException('SSLCOMMERZ refund response could not be decoded.');
        }

        return $decoded;
    }
}
