<?php

namespace App\Services\Booking;

use Carbon\CarbonImmutable;

final class Slot
{
    public function __construct(
        public readonly int $counsellorId,
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
    ) {}

    public function key(): string
    {
        return $this->counsellorId.'|'.$this->start->utc()->format('Y-m-d\TH:i:s\Z');
    }
}
