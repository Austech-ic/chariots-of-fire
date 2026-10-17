<?php

namespace App\Services\Payments;

final class VerificationResult
{
    public const SUCCEEDED = 'succeeded';

    public const FAILED = 'failed';

    public const PENDING = 'pending';

    public const REVERSED = 'reversed';

    public function __construct(
        public readonly string $status,
        public readonly ?int $amountMinor = null,
        public readonly ?string $currency = null,
        public readonly ?string $providerTransactionId = null,
        public readonly ?string $providerStatus = null,
        public readonly ?string $message = null,
    ) {}

    public static function pending(?string $message = null): self
    {
        return new self(self::PENDING, message: $message);
    }
}
