<?php

namespace App\Services\Streaming;

final class StreamStatus
{
    public const LIVE = 'live';

    public const UPCOMING = 'scheduled';

    public const ENDED = 'ended';

    public const UNKNOWN = 'unknown';

    public function __construct(
        public readonly string $status,
        public readonly ?string $title = null,
        public readonly ?string $thumbnailUrl = null,
        public readonly bool $embeddable = true,
        public readonly ?string $error = null,
    ) {}

    public static function unknown(?string $error = null): self
    {
        return new self(self::UNKNOWN, error: $error);
    }
}
