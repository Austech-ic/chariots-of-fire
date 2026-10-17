<?php

namespace App\Services\Payments;

final class CheckoutSession
{
    public function __construct(
        public readonly string $checkoutUrl,
        public readonly ?string $providerReference = null,
    ) {}
}
