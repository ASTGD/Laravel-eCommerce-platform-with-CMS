<?php

namespace Platform\CommerceCore\Exceptions;

use RuntimeException;

class SslCommerzVerificationException extends RuntimeException
{
    public function __construct(
        string $message,
        protected array $validated = [],
        protected ?string $attemptStatus = null,
    ) {
        parent::__construct($message);
    }

    public function attemptStatus(): ?string
    {
        return $this->attemptStatus;
    }

    public function validated(): array
    {
        return $this->validated;
    }
}
