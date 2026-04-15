<?php

namespace Platform\CommerceCore\Support;

class BkashStatusMapper
{
    public function statusFromCallback(string $status): string
    {
        return match (strtolower(trim($status))) {
            'failure', 'failed' => 'failed',
            'cancel', 'cancelled', 'canceled' => 'cancelled',
            default => 'pending_validation',
        };
    }

    public function statusFromValidated(array $validated): string
    {
        return match ($this->transactionStatus($validated)) {
            'COMPLETED' => 'paid',
            'FAILED' => 'failed',
            'CANCELLED', 'CANCELED' => 'cancelled',
            'INITIATED', 'PENDING', 'PROCESSING', 'AUTHORIZED' => 'pending_validation',
            default => ($this->apiStatusCode($validated) === '0000' ? 'pending_validation' : 'invalid'),
        };
    }

    public function isPaid(array $validated): bool
    {
        return $this->statusFromValidated($validated) === 'paid';
    }

    public function transactionStatus(array $validated): ?string
    {
        $status = $validated['transactionStatus'] ?? $validated['status'] ?? null;

        return is_string($status) ? strtoupper(trim($status)) : null;
    }

    public function apiStatusCode(array $validated): ?string
    {
        $statusCode = $validated['statusCode'] ?? null;

        return is_string($statusCode) ? trim($statusCode) : null;
    }

    public function userMessageForStatus(string $status): string
    {
        return match ($status) {
            'failed' => 'The bKash payment failed. Please choose a payment method again.',
            'cancelled' => 'The bKash payment was cancelled. Please choose a payment method again.',
            'pending_validation' => 'The bKash payment is still pending confirmation. Please try again shortly.',
            default => 'The bKash payment could not be verified. Please try again.',
        };
    }

    public function userMessageForValidated(array $validated): string
    {
        return $this->userMessageForStatus($this->statusFromValidated($validated));
    }

    public function validationStatus(array $validated): ?string
    {
        return $this->transactionStatus($validated) ?? $this->apiStatusCode($validated);
    }
}
