<?php

namespace App\Services\Streaming;

/**
 * Generic adapter for any provider that issues an HTTPS embed URL
 * (Mux, Cloudflare Stream, Facebook Live, etc.). Hosts must be allow-listed
 * in config/fco.php so the Content Security Policy can permit them.
 */
class EmbedUrlProvider implements StreamProvider
{
    public function __construct(private readonly array $allowedHosts = []) {}

    public function key(): string
    {
        return 'embed';
    }

    public function label(): string
    {
        return 'Other provider (embed URL)';
    }

    public function resolve(string $input): ?ResolvedStream
    {
        $input = trim($input);
        $host = parse_url($input, PHP_URL_HOST);
        if (! $host || parse_url($input, PHP_URL_SCHEME) !== 'https') {
            return null;
        }

        $allowed = collect($this->allowedHosts)->contains(fn ($h) => $host === $h || str_ends_with($host, '.'.$h));
        if (! $allowed) {
            return null;
        }

        return new ResolvedStream('embed', $input, $input, $input);
    }

    public function supportsStatusChecks(): bool
    {
        return false;
    }

    public function status(string $externalId): StreamStatus
    {
        return StreamStatus::unknown();
    }
}
