<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CounsellingService extends Model
{
    use Concerns\Auditable;

    public const MODES = ['request' => 'Staff-approved requests only', 'self_service' => 'Self-service booking only', 'both' => 'Both'];

    public const METHODS = ['in_person' => 'In person', 'phone' => 'Phone call', 'online' => 'Online video'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'approval_required' => 'boolean',
        'requires_account' => 'boolean',
        'appointment_methods' => 'array',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function counsellors(): BelongsToMany
    {
        return $this->belongsToMany(CounsellorProfile::class, 'counselling_service_counsellor');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')->orderBy('sort_order');
    }

    public function allowsSelfService(): bool
    {
        return in_array($this->booking_mode, ['self_service', 'both'], true);
    }

    public function allowsRequests(): bool
    {
        return in_array($this->booking_mode, ['request', 'both'], true);
    }

    public function methods(): array
    {
        return array_values(array_intersect(array_keys(self::METHODS), $this->appointment_methods ?: ['in_person']));
    }
}
