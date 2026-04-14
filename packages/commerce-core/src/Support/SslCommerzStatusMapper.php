<?php

namespace Platform\CommerceCore\Support;

class SslCommerzStatusMapper
{
    public function isPaid(array $validated): bool
    {
        return $this->statusFromValidated($validated) === 'paid';
    }

    public function statusFromEvent(string $eventType): string
    {
        return match ($eventType) {
            'fail_redirect' => 'failed',
            'cancel_redirect' => 'cancelled',
            default => 'pending_validation',
        };
    }

    public function statusFromValidated(array $validated): string
    {
        return match ($this->validationStatus($validated)) {
            'VALID', 'VALIDATED' => 'paid',
            'FAILED', 'FAILED_TRANSACTION', 'DECLINED' => 'failed',
            'CANCELLED', 'CANCELED' => 'cancelled',
            'PENDING', 'PROCESSING', 'INITIATED' => 'pending_validation',
            default => 'invalid',
        };
    }

    public function userMessageForStatus(string $status): string
    {
        return match ($status) {
            'failed' => 'The online payment failed. Please choose a payment method again.',
            'cancelled' => 'The online payment was cancelled. Please choose a payment method again.',
            'pending_validation' => 'The payment is still pending confirmation. Please check again shortly.',
            default => 'The payment could not be verified. Please try again.',
        };
    }

    public function userMessageForValidated(array $validated): string
    {
        return $this->userMessageForStatus($this->statusFromValidated($validated));
    }

    public function validationStatus(array $validated): ?string
    {
        $status = $validated['status'] ?? null;

        return is_string($status) ? strtoupper(trim($status)) : null;
    }
}
