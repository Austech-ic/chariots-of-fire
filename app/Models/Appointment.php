<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use Concerns\Auditable;

    public const STATUSES = [
        'offered' => 'Offered (awaiting visitor)',
        'pending' => 'Pending approval',
        'confirmed' => 'Confirmed',
        'cancelled' => 'Cancelled',
        'completed' => 'Completed',
        'no_show' => 'No-show',
    ];

    /** Statuses that occupy a counsellor's time. */
    public const BLOCKING = ['offered', 'pending', 'confirmed'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['contact_data', 'manage_token'];

    protected $casts = [
        'contact_data' => 'encrypted:array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
    ];

    public function counsellor(): BelongsTo
    {
        return $this->belongsTo(CounsellorProfile::class, 'counsellor_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(CounsellingService::class, 'service_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function counsellingRequest(): BelongsTo
    {
        return $this->belongsTo(CounsellingRequest::class);
    }

    public function scopeBlocking(Builder $query): Builder
    {
        return $query->whereIn('status', self::BLOCKING);
    }

    /** Overlap test: existing.start < new.end AND existing.end > new.start. */
    public function scopeOverlapping(Builder $query, $start, $end): Builder
    {
        return $query->where('starts_at', '<', $end)->where('ends_at', '>', $start);
    }

    public function localStart()
    {
        return $this->starts_at->copy()->setTimezone($this->timezone);
    }

    public function contactEmail(): ?string
    {
        return $this->contact_data['email'] ?? $this->user?->email;
    }

    public function contactName(): ?string
    {
        return $this->contact_data['name'] ?? $this->user?->name;
    }

    public function isChangeable(): bool
    {
        return in_array($this->status, ['offered', 'pending', 'confirmed'], true) && $this->starts_at->isFuture();
    }
}
