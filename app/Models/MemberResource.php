<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MemberResource extends Model
{
    use Concerns\Auditable;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = ['published_at' => 'datetime'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_asset_id');
    }

    public function grantedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'member_resource_access', 'resource_id', 'user_id')->withPivot('granted_at');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    /** Resources the given member may open. */
    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        return $query->published()->where(function ($q) use ($user) {
            $q->where('access_policy', 'all_members')
                ->orWhereHas('grantedUsers', fn ($g) => $g->whereKey($user->getKey()));
        });
    }

    public function isAccessibleBy(User $user): bool
    {
        return static::query()->whereKey($this->getKey())->accessibleBy($user)->exists();
    }
}
