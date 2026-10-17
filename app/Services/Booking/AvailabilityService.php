<?php

namespace App\Services\Booking;

use App\Models\Appointment;
use App\Models\CounsellingService;
use App\Models\CounsellorProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Computes bookable slots from weekly rules, minus leave/blocked periods,
 * existing appointments (plus buffers), notice and advance limits.
 * Results are advisory: BookingService re-checks inside a transaction.
 */
class AvailabilityService
{
    /** @return Collection<int, Slot> */
    public function slots(CounsellingService $service, ?CarbonImmutable $from = null, ?CarbonImmutable $to = null, ?int $counsellorId = null): Collection
    {
        $now = CarbonImmutable::now('UTC');
        $earliest = $now->addHours($service->min_notice_hours);
        $latest = $now->addDays($service->max_advance_days)->endOfDay();

        $from = ($from ?? $now)->utc();
        $to = ($to ?? $latest)->utc();
        if ($from->lt($earliest)) {
            $from = $earliest;
        }
        if ($to->gt($latest)) {
            $to = $latest;
        }
        if ($from->gte($to)) {
            return collect();
        }

        $counsellors = $service->counsellors()
            ->where('status', 'active')->where('booking_enabled', true)
            ->when($counsellorId, fn ($q) => $q->whereKey($counsellorId))
            ->with(['availabilityRules', 'availabilityExceptions' => fn ($q) => $q->where('ends_at', '>', $from)->where('starts_at', '<', $to)])
            ->get();

        return $counsellors
            ->flatMap(fn (CounsellorProfile $c) => $this->slotsFor($c, $service, $from, $to))
            ->sortBy(fn (Slot $s) => $s->start->getTimestamp())
            ->values();
    }

    /** @return list<Slot> */
    private function slotsFor(CounsellorProfile $counsellor, CounsellingService $service, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $buffer = $service->buffer_minutes;
        $busy = Appointment::query()->blocking()
            ->where('counsellor_id', $counsellor->id)
            ->where('starts_at', '<', $to->addMinutes($buffer))
            ->where('ends_at', '>', $from->subMinutes($buffer))
            ->get(['starts_at', 'ends_at'])
            ->map(fn ($a) => [
                CarbonImmutable::parse($a->starts_at)->subMinutes($buffer),
                CarbonImmutable::parse($a->ends_at)->addMinutes($buffer),
            ]);

        $blocked = $counsellor->availabilityExceptions
            ->map(fn ($e) => [CarbonImmutable::parse($e->starts_at), CarbonImmutable::parse($e->ends_at)]);

        $slots = [];
        foreach ($counsellor->availabilityRules as $rule) {
            $tz = $rule->timezone ?: $counsellor->timezone;
            $day = $from->setTimezone($tz)->startOfDay();
            $lastDay = $to->setTimezone($tz)->startOfDay();

            for (; $day->lte($lastDay); $day = $day->addDay()) {
                if ($day->dayOfWeek !== (int) $rule->weekday) {
                    continue;
                }
                if ($rule->effective_from && $day->lt(CarbonImmutable::parse($rule->effective_from, $tz)->startOfDay())) {
                    continue;
                }
                if ($rule->effective_until && $day->gt(CarbonImmutable::parse($rule->effective_until, $tz)->startOfDay())) {
                    continue;
                }

                $windowStart = CarbonImmutable::parse($day->format('Y-m-d').' '.$rule->start_time, $tz);
                $windowEnd = CarbonImmutable::parse($day->format('Y-m-d').' '.$rule->end_time, $tz);

                for ($start = $windowStart; $start->addMinutes($service->duration_minutes)->lte($windowEnd); $start = $start->addMinutes($service->duration_minutes + $buffer)) {
                    $end = $start->addMinutes($service->duration_minutes);
                    $startUtc = $start->utc();
                    $endUtc = $end->utc();

                    if ($startUtc->lt($from) || $endUtc->gt($to)) {
                        continue;
                    }
                    if ($this->overlapsAny($startUtc, $endUtc, $busy) || $this->overlapsAny($startUtc, $endUtc, $blocked)) {
                        continue;
                    }

                    $slot = new Slot($counsellor->id, $startUtc, $endUtc);
                    $slots[$slot->key()] = $slot;
                }
            }
        }

        return array_values($slots);
    }

    private function overlapsAny(CarbonImmutable $start, CarbonImmutable $end, Collection $ranges): bool
    {
        return $ranges->contains(fn ($r) => $r[0]->lt($end) && $r[1]->gt($start));
    }

    public function isAvailable(CounsellingService $service, int $counsellorId, CarbonImmutable $start): bool
    {
        $start = $start->utc();

        return $this->slots($service, $start->subMinute(), $start->addMinutes($service->duration_minutes + 1), $counsellorId)
            ->contains(fn (Slot $s) => $s->start->equalTo($start));
    }
}
