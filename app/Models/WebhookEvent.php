<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['provider', 'external_event_id', 'event_type', 'status', 'error', 'received_at', 'processed_at'];

    protected $casts = ['received_at' => 'datetime', 'processed_at' => 'datetime'];
}
