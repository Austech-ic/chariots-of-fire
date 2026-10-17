<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvailabilityException extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime'];

    public function counsellor(): BelongsTo
    {
        return $this->belongsTo(CounsellorProfile::class, 'counsellor_id');
    }
}
