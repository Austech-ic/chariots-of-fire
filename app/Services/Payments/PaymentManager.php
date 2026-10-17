<?php

namespace App\Services\Payments;

use InvalidArgumentException;

class PaymentManager
{
    /** @param array<string, PaymentGateway> $gateways */
    public function __construct(private array $gateways, private readonly string $default) {}

    public function gateway(?string $key = null): PaymentGateway
    {
        $key ??= $this->default;

        return $this->gateways[$key] ?? throw new InvalidArgumentException("Payment gateway [{$key}] is not enabled.");
    }

    public function has(string $key): bool
    {
        return isset($this->gateways[$key]);
    }

    public function defaultKey(): string
    {
        return $this->default;
    }

    /** @return array<string, string> */
    public function options(): array
    {
        return array_map(fn (PaymentGateway $g) => $g->label(), $this->gateways);
    }
}
