<?php

namespace App\Services\Streaming;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class YouTubeProvider implements StreamProvider
{
    public function __construct(private readonly ?string $apiKey = null) {}

    public function key(): string
    {
        return 'youtube';
    }

    public function label(): string
    {
        return 'YouTube';
    }

    public function resolve(string $input): ?ResolvedStream
    {
        $id = self::extractId($input);
        if (! $id) {
            return null;
        }

        return new ResolvedStream(
            'youtube',
            $id,
            'https://www.youtube-nocookie.com/embed/'.$id.'?rel=0&modestbranding=1',
            'https://www.youtube.com/watch?v='.$id,
        );
    }

    public static function extractId(string $input): ?string
    {
        $input = trim($input);
        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $input)) {
            return $input;
        }

        $parts = parse_url($input);
        if (! $parts || empty($parts['host'])) {
            return null;
        }

        $host = strtolower(preg_replace('/^(www\.|m\.|music\.)/', '', $parts['host']));
        $path = $parts['path'] ?? '';

        if ($host === 'youtu.be') {
            $candidate = ltrim($path, '/');
        } elseif (in_array($host, ['youtube.com', 'youtube-nocookie.com'], true)) {
            parse_str($parts['query'] ?? '', $query);
            $candidate = $query['v'] ?? null;
            if (! $candidate && preg_match('#^/(embed|live|shorts|v)/([^/?]+)#', $path, $m)) {
                $candidate = $m[2];
            }
        } else {
            return null;
        }

        $candidate = explode('/', (string) $candidate)[0];

        return preg_match('/^[A-Za-z0-9_-]{11}$/', $candidate) ? $candidate : null;
    }

    public function supportsStatusChecks(): bool
    {
        return filled($this->apiKey);
    }

    /** Uses the YouTube Data API (videos.list) server-side; the key never reaches the browser. */
    public function status(string $externalId): StreamStatus
    {
        if (! $this->supportsStatusChecks()) {
            return StreamStatus::unknown('YouTube API key not configured');
        }

        try {
            return Cache::remember('yt.status.'.$externalId, 60, function () use ($externalId) {
                $response = Http::timeout(8)->retry(2, 300, throw: false)->get('https://www.googleapis.com/youtube/v3/videos', [
                    'part' => 'snippet,status,liveStreamingDetails',
                    'id' => $externalId,
                    'key' => $this->apiKey,
                ]);

                if ($response->status() === 403) {
                    return StreamStatus::unknown('YouTube quota exceeded or key rejected');
                }
                if (! $response->successful()) {
                    return StreamStatus::unknown('YouTube API error '.$response->status());
                }

                $item = $response->json('items.0');
                if (! $item) {
                    return new StreamStatus(StreamStatus::ENDED, embeddable: false, error: 'Video is private, deleted or unavailable');
                }

                $state = match ($item['snippet']['liveBroadcastContent'] ?? 'none') {
                    'live' => StreamStatus::LIVE,
                    'upcoming' => StreamStatus::UPCOMING,
                    default => StreamStatus::ENDED,
                };

                return new StreamStatus(
                    $state,
                    $item['snippet']['title'] ?? null,
                    $item['snippet']['thumbnails']['high']['url'] ?? null,
                    (bool) ($item['status']['embeddable'] ?? true),
                );
            });
        } catch (Throwable $e) {
            report($e);

            return StreamStatus::unknown($e->getMessage());
        }
    }
}
