<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Donation extends Model
{
    public const STATUSES = [
        'pending' => 'Pending',
        'paid' => 'Paid',
        'failed' => 'Failed',
        'abandoned' => 'Abandoned',
        'refunded' => 'Refunded',
        'partially_refunded' => 'Partially refunded',
        'reversed' => 'Reversed',
    ];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'paid_at' => 'datetime',
        'receipt_sent_at' => 'datetime',
        'reconciled_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(DonationCampaign::class, 'campaign_id');
    }

    public function paymentAttempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(DonationRefund::class);
    }

    public function formattedAmount(): string
    {
        return Money::format($this->amount_minor, $this->currency);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function refundedMinor(): int
    {
        return (int) $this->refunds()->whereIn('status', ['pending', 'processed'])->sum('amount_minor');
    }
}
