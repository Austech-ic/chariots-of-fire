<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsletterConsent extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = ['granted_at' => 'datetime', 'withdrawn_at' => 'datetime'];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(NewsletterSubscription::class, 'subscription_id');
    }
}
