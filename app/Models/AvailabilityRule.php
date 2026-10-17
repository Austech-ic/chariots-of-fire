<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvailabilityRule extends Model
{
    public const WEEKDAYS = [0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = ['effective_from' => 'date', 'effective_until' => 'date'];

    public function counsellor(): BelongsTo
    {
        return $this->belongsTo(CounsellorProfile::class, 'counsellor_id');
    }
}
