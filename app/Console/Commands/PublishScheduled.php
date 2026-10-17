<?php

namespace App\Console\Commands;

use App\Enums\ContentStatus;
use App\Models\Event;
use App\Models\GalleryAlbum;
use App\Models\HeroSlide;
use App\Models\Mandate;
use App\Models\Page;
use App\Models\Sermon;
use App\Models\SermonSeries;
use Illuminate\Console\Attributes\AsCommand;
use Illuminate\Console\Command;

#[AsCommand(name: 'fco:publish-scheduled', description: 'Publish scheduled content whose publication time has arrived')]
class PublishScheduled extends Command
{
    protected $signature = 'fco:publish-scheduled';

    public function handle(): int
    {
        $count = 0;
        foreach ([Page::class, HeroSlide::class, Mandate::class, SermonSeries::class, Sermon::class, Event::class, GalleryAlbum::class] as $model) {
            $model::query()->where('status', ContentStatus::Scheduled->value)->where('published_at', '<=', now())
                ->each(function ($record) use (&$count) {
                    $record->status = ContentStatus::Published;
                    $record->save(); // also publishes attached media
                    $count++;
                });
        }

        $this->info("Published {$count} scheduled item(s).");

        return self::SUCCESS;
    }
}
