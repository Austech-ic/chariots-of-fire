<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CounsellorProfile extends Model
{
    use Concerns\Auditable;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = ['booking_enabled' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(CounsellingService::class, 'counselling_service_counsellor');
    }

    public function availabilityRules(): HasMany
    {
        return $this->hasMany(AvailabilityRule::class, 'counsellor_id');
    }

    public function availabilityExceptions(): HasMany
    {
        return $this->hasMany(AvailabilityException::class, 'counsellor_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'counsellor_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(CounsellingRequest::class, 'assigned_to');
    }

    public function isBookable(): bool
    {
        return $this->status === 'active' && $this->booking_enabled;
    }
}
