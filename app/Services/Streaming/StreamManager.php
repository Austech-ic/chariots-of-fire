<?php

namespace App\Services\Streaming;

use App\Models\Livestream;
use InvalidArgumentException;

class StreamManager
{
    /** @var array<string, StreamProvider> */
    private array $providers = [];

    public function __construct(iterable $providers)
    {
        foreach ($providers as $provider) {
            $this->providers[$provider->key()] = $provider;
        }
    }

    public function provider(string $key): StreamProvider
    {
        return $this->providers[$key] ?? throw new InvalidArgumentException("Unknown stream provider [{$key}]");
    }

    public function options(): array
    {
        return collect($this->providers)->map->label()->all();
    }

    public function resolve(?string $provider, ?string $input): ?ResolvedStream
    {
        if (! $provider || ! $input || ! isset($this->providers[$provider])) {
            return null;
        }

        return $this->providers[$provider]->resolve($input);
    }

    /**
     * Refresh a stream's status from its provider when the provider can
     * report it. Manual statuses are never overridden by an "unknown" answer.
     */
    public function syncStatus(Livestream $stream): StreamStatus
    {
        $provider = $this->providers[$stream->provider] ?? null;
        if (! $provider || ! $provider->supportsStatusChecks() || ! $stream->external_id) {
            return StreamStatus::unknown('Provider cannot report status');
        }

        $status = $provider->status($stream->external_id);
        $stream->status_checked_at = now();

        if ($status->status !== StreamStatus::UNKNOWN && $stream->status_source === 'provider') {
            $stream->status = $status->status;
            if ($status->status === StreamStatus::ENDED && ! $stream->ended_at) {
                $stream->ended_at = now();
            }
        }

        $stream->saveQuietly();
        $stream->forgetLiveCache();

        return $status;
    }
}
