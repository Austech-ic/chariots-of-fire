<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationConsent extends Model
{
    public const CHANNELS = ['sms' => 'SMS', 'whatsapp' => 'WhatsApp'];

    public const PURPOSES = [
        'appointment_reminders' => 'Appointment reminders',
        'event_updates' => 'Programme and event updates',
        'ministry_news' => 'Ministry news',
    ];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['contact_value'];

    protected $casts = ['contact_value' => 'encrypted', 'consented_at' => 'datetime', 'withdrawn_at' => 'datetime'];

    public static function hashContact(string $value): string
    {
        return hash_hmac('sha256', preg_replace('/[^0-9+]/', '', $value), config('app.key'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
