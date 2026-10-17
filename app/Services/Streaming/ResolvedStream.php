<?php

namespace App\Services\Streaming;

final class ResolvedStream
{
    public function __construct(
        public readonly string $provider,
        public readonly string $externalId,
        public readonly ?string $embedUrl,
        public readonly ?string $watchUrl,
    ) {}
}
