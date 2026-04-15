<?php

namespace Platform\CommerceCore\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Platform\CommerceCore\Payment\AbstractBkashPayment;

class BkashTokenService
{
    public function resolveIdToken(AbstractBkashPayment $payment): string
    {
        $cached = $this->getCachedTokenBundle($payment);

        if ($cached && ! empty($cached['id_token']) && ! empty($cached['expires_at']) && now()->lt($cached['expires_at'])) {
            return $cached['id_token'];
        }

        if ($cached && ! empty($cached['refresh_token'])) {
            try {
                return $this->persistTokenBundle($payment, $this->refreshToken($payment, $cached['refresh_token']));
            } catch (\Throwable) {
                $this->forgetTokens($payment);
            }
        }

        return $this->persistTokenBundle($payment, $this->grantToken($payment));
    }

    public function forgetTokens(AbstractBkashPayment $payment): void
    {
        Cache::forget($this->cacheKey($payment));
    }

    protected function getCachedTokenBundle(AbstractBkashPayment $payment): ?array
    {
        $bundle = Cache::get($this->cacheKey($payment));

        if (! is_array($bundle)) {
            return null;
        }

        if (! empty($bundle['expires_at'])) {
            $bundle['expires_at'] = Carbon::createFromTimestamp($bundle['expires_at']);
        }

        return $bundle;
    }

    protected function persistTokenBundle(AbstractBkashPayment $payment, array $response): string
    {
        if (empty($response['id_token'])) {
            throw new \RuntimeException('bKash did not return an access token.');
        }

        $expiresIn = max(((int) ($response['expires_in'] ?? 3600)) - 60, 60);
        $expiresAt = now()->addSeconds($expiresIn);

        Cache::put($this->cacheKey($payment), [
            'id_token' => $response['id_token'],
            'refresh_token' => $response['refresh_token'] ?? null,
            'expires_at' => $expiresAt->getTimestamp(),
        ], $expiresAt);

        return $response['id_token'];
    }

    protected function grantToken(AbstractBkashPayment $payment): array
    {
        $response = Http::asJson()
            ->acceptJson()
            ->timeout($payment->getRequestTimeout())
            ->withHeaders([
                'username' => $payment->getUsername(),
                'password' => $payment->getPassword(),
            ])
            ->post($payment->getBaseUrl().'/tokenized/checkout/token/grant', [
                'app_key' => $payment->getAppKey(),
                'app_secret' => $payment->getAppSecret(),
            ])
            ->throw()
            ->json();

        if (! is_array($response) || ($response['statusCode'] ?? null) !== '0000') {
            throw new \RuntimeException($response['statusMessage'] ?? $response['errorMessage'] ?? 'Unable to authenticate with bKash.');
        }

        return $response;
    }

    protected function refreshToken(AbstractBkashPayment $payment, string $refreshToken): array
    {
        $response = Http::asJson()
            ->acceptJson()
            ->timeout($payment->getRequestTimeout())
            ->withHeaders([
                'username' => $payment->getUsername(),
                'password' => $payment->getPassword(),
            ])
            ->post($payment->getBaseUrl().'/tokenized/checkout/token/refresh', [
                'app_key' => $payment->getAppKey(),
                'app_secret' => $payment->getAppSecret(),
                'refresh_token' => $refreshToken,
            ])
            ->throw()
            ->json();

        if (! is_array($response) || ($response['statusCode'] ?? null) !== '0000') {
            throw new \RuntimeException($response['statusMessage'] ?? $response['errorMessage'] ?? 'Unable to refresh the bKash access token.');
        }

        return $response;
    }

    protected function cacheKey(AbstractBkashPayment $payment): string
    {
        return sprintf(
            'commerce-core:bkash:token:%s:%s',
            core()->getRequestedChannelCode(),
            $payment->isSandbox() ? 'sandbox' : 'live',
        );
    }
}
