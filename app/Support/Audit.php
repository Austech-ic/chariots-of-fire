<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class Audit
{
    /**
     * Never pass confidential values (counselling narratives, payment secrets,
     * personal contact data) in $metadata.
     */
    public static function log(string $action, ?Model $subject = null, array $metadata = [], ?int $actorId = null): void
    {
        AuditLog::create([
            'actor_user_id' => $actorId ?? auth()->id(),
            'action' => $subject ? class_basename($subject).'.'.$action : $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'metadata' => $metadata ?: null,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }
}
