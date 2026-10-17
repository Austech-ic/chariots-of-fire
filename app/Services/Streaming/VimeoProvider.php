<?php

namespace App\Services\Streaming;

class VimeoProvider implements StreamProvider
{
    public function key(): string
    {
        return 'vimeo';
    }

    public function label(): string
    {
        return 'Vimeo';
    }

    public function resolve(string $input): ?ResolvedStream
    {
        $input = trim($input);
        if (preg_match('#^\d+$#', $input)) {
            $id = $input;
        } elseif (preg_match('#vimeo\.com/(?:video/|event/)?(\d+)#', $input, $m)) {
            $id = $m[1];
        } else {
            return null;
        }

        $isEvent = str_contains($input, '/event/');

        return new ResolvedStream(
            'vimeo',
            $id,
            $isEvent ? "https://vimeo.com/event/{$id}/embed" : "https://player.vimeo.com/video/{$id}",
            $isEvent ? "https://vimeo.com/event/{$id}" : "https://vimeo.com/{$id}",
        );
    }

    public function supportsStatusChecks(): bool
    {
        return false;
    }

    public function status(string $externalId): StreamStatus
    {
        return StreamStatus::unknown('Status checks are not configured for Vimeo');
    }
}
