<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAttempt extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = ['verified_at' => 'datetime'];

    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }
}
