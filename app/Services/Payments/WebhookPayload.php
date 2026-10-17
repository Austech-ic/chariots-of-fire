<?php

namespace App\Services\Payments;

final class WebhookPayload
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $eventType,
        public readonly ?string $reference,
    ) {}
}
