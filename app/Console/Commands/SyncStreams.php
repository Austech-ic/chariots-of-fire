<?php

namespace App\Console\Commands;

use App\Models\Livestream;
use App\Services\Streaming\StreamManager;
use Illuminate\Console\Attributes\AsCommand;
use Illuminate\Console\Command;

#[AsCommand(name: 'fco:sync-streams', description: 'Refresh live status for provider-tracked streams around their scheduled time')]
class SyncStreams extends Command
{
    protected $signature = 'fco:sync-streams';

    public function handle(StreamManager $streams): int
    {
        Livestream::query()->where('status_source', 'provider')
            ->whereIn('status', ['scheduled', 'live'])
            ->where(fn ($q) => $q->where('status', 'live')->orWhereBetween('scheduled_at', [now()->subHours(3), now()->addMinutes(30)]))
            ->each(function (Livestream $stream) use ($streams) {
                $result = $streams->syncStatus($stream);
                $this->line("{$stream->id}: {$result->status}".($result->error ? " ({$result->error})" : ''));
            });

        return self::SUCCESS;
    }
}
