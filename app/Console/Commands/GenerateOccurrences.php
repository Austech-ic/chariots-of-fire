<?php

namespace App\Console\Commands;

use App\Models\Event;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\AsCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Expands recurring events into individual occurrences so each one can be
 * edited, cancelled or registered for separately.
 *
 * recurrence = {"freq": "daily|weekly|monthly", "interval": 1, "until": "2027-06-30", "count": 12}
 */
#[AsCommand(name: 'fco:generate-occurrences', description: 'Create upcoming occurrences for recurring events')]
class GenerateOccurrences extends Command
{
    protected $signature = 'fco:generate-occurrences {--days=90 : How far ahead to generate}';

    public function handle(): int
    {
        $horizon = CarbonImmutable::now()->addDays((int) $this->option('days'));
        $created = 0;

        Event::query()->whereNull('parent_event_id')->whereNotNull('recurrence')->with('translations')
            ->each(function (Event $parent) use ($horizon, &$created) {
                $created += $this->expand($parent, $horizon);
            });

        $this->info("Created {$created} occurrence(s).");

        return self::SUCCESS;
    }

    public function expand(Event $parent, CarbonImmutable $horizon): int
    {
        $rule = $parent->recurrence ?? [];
        $freq = $rule['freq'] ?? null;
        if (! in_array($freq, ['daily', 'weekly', 'monthly'], true)) {
            return 0;
        }

        $interval = max(1, (int) ($rule['interval'] ?? 1));
        $until = ! empty($rule['until']) ? CarbonImmutable::parse($rule['until'], $parent->timezone)->endOfDay() : null;
        $maxCount = ! empty($rule['count']) ? (int) $rule['count'] : null;
        $duration = $parent->ends_at ? $parent->starts_at->diffInMinutes($parent->ends_at) : null;

        // Step in the event's local time so the wall-clock start survives DST changes.
        $localStart = CarbonImmutable::parse($parent->starts_at)->setTimezone($parent->timezone);
        $created = 0;

        for ($n = 1; $n <= 500; $n++) {
            if ($maxCount && $n >= $maxCount) {
                break;
            }
            $next = match ($freq) {
                'daily' => $localStart->addDays($interval * $n),
                'weekly' => $localStart->addWeeks($interval * $n),
                'monthly' => $localStart->addMonthsNoOverflow($interval * $n),
            };
            if ($next->gt($horizon) || ($until && $next->gt($until))) {
                break;
            }
            if ($next->lt(now())) {
                continue;
            }

            $slug = $parent->slug.'-'.$next->format('Ymd');
            if (Event::query()->where('slug', $slug)->exists()) {
                continue;
            }

            DB::transaction(function () use ($parent, $next, $slug, $duration) {
                $child = $parent->replicate(['recurrence', 'slug', 'starts_at', 'ends_at', 'featured']);
                $child->slug = $slug;
                $child->parent_event_id = $parent->id;
                $child->starts_at = $next->utc();
                $child->ends_at = $duration !== null ? $next->addMinutes($duration)->utc() : null;
                $child->recurrence = null;
                $child->save();

                foreach ($parent->translations as $translation) {
                    $child->translations()->create($translation->replicate(['event_id'])->toArray());
                }
            });
            $created++;
        }

        return $created;
    }
}
