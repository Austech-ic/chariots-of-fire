<?php

namespace App\Support;

use Illuminate\Support\Str;

/** Human-friendly, unguessable public references, e.g. DON-7KQ2-M9XA4T. */
class Reference
{
    public static function make(string $prefix): string
    {
        return strtoupper($prefix.'-'.Str::random(4).'-'.Str::random(6));
    }
}
