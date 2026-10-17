<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Confidential. Narrative, contact details, preferred times and notes are
 * encrypted at rest with the application key; access is restricted by policy.
 */
class CounsellingRequest extends Model
{
    public const STATUSES = [
        'new' => 'New',
        'assigned' => 'Assigned',
        'offered' => 'Time offered',
        'scheduled' => 'Scheduled',
        'closed' => 'Closed',
    ];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['contact_data', 'encrypted_message', 'preferred_times', 'internal_notes', 'response_token'];

    protected $casts = [
        'contact_data' => 'encrypted:array',
        'encrypted_message' => 'encrypted',
        'preferred_times' => 'encrypted',
        'internal_notes' => 'encrypted',
        'closed_at' => 'datetime',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(CounsellingService::class, 'service_id');
    }

    public function assignedCounsellor(): BelongsTo
    {
        return $this->belongsTo(CounsellorProfile::class, 'assigned_to');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function contactName(): ?string
    {
        return $this->contact_data['name'] ?? null;
    }

    public function contactEmail(): ?string
    {
        return $this->contact_data['email'] ?? null;
    }
}
