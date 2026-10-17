<?php

namespace App\Services\Payments;

final class RefundResult
{
    public function __construct(
        public readonly bool $accepted,
        public readonly ?string $providerReference = null,
        public readonly ?string $message = null,
    ) {}
}
