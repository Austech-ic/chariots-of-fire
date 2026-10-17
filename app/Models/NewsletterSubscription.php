<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsletterSubscription extends Model
{
    public const STATUSES = ['pending' => 'Pending confirmation', 'active' => 'Active', 'unsubscribed' => 'Unsubscribed', 'suppressed' => 'Suppressed'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['confirmation_token'];

    protected $casts = ['subscribed_at' => 'datetime', 'unsubscribed_at' => 'datetime'];

    public static function normalize(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    public function consents(): HasMany
    {
        return $this->hasMany(NewsletterConsent::class, 'subscription_id');
    }

    public function canReceiveMarketing(): bool
    {
        return $this->status === 'active';
    }
}
