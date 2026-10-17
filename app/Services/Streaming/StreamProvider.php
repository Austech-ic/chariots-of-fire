<?php

namespace App\Services\Streaming;

/**
 * Adapter contract for video/streaming providers. A public embed does not
 * guarantee API access to live status or metadata; providers that cannot
 * answer return StreamStatus::unknown() and the CMS status is used instead.
 */
interface StreamProvider
{
    public function key(): string;

    public function label(): string;

    /** Parse a pasted URL or raw ID into a normalised stream reference. */
    public function resolve(string $input): ?ResolvedStream;

    public function status(string $externalId): StreamStatus;

    public function supportsStatusChecks(): bool;
}
