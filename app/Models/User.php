<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, InteractsWithAppAuthentication, InteractsWithAppAuthenticationRecovery, Notifiable;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_DEACTIVATED = 'deactivated';

    protected $fillable = ['name', 'email', 'password', 'phone', 'locale', 'status', 'is_staff'];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = ['status' => self::STATUS_ACTIVE, 'locale' => 'en', 'is_staff' => false];

    /** @var array<string, bool>|null */
    private ?array $permissionCache = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'suspended_at' => 'datetime',
            'password' => 'hashed',
            'is_staff' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_staff && $this->isActive() && $this->hasVerifiedEmail();
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles->contains('slug', $slug);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    public function hasPermission(string $slug): bool
    {
        if (! $this->is_staff || ! $this->isActive()) {
            return false;
        }
        if ($this->isSuperAdmin()) {
            return true;
        }

        $this->permissionCache ??= $this->roles()->with('permissions')->get()
            ->flatMap->permissions->pluck('slug')->unique()->flip()->map(fn () => true)->all();

        return isset($this->permissionCache[$slug]);
    }

    public function flushPermissionCache(): void
    {
        $this->permissionCache = null;
        $this->unsetRelation('roles');
    }

    public function counsellorProfile(): HasOne
    {
        return $this->hasOne(CounsellorProfile::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function eventRegistrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function counsellingRequests(): HasMany
    {
        return $this->hasMany(CounsellingRequest::class, 'requester_user_id');
    }

    public function resourceGrants(): BelongsToMany
    {
        return $this->belongsToMany(MemberResource::class, 'member_resource_access', 'user_id', 'resource_id')->withPivot('granted_at');
    }

    public function accountRequests(): HasMany
    {
        return $this->hasMany(AccountRequest::class);
    }

    public function notificationConsents(): HasMany
    {
        return $this->hasMany(NotificationConsent::class);
    }
}
