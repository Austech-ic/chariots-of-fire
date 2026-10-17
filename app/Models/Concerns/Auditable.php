<?php

namespace App\Models\Concerns;

use App\Support\Audit;

/**
 * Records create/update/delete actions. Only the names of changed attributes
 * are stored, never their values, so confidential content stays out of logs.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn ($model) => Audit::log('created', $model));
        static::updated(function ($model) {
            $changed = array_values(array_diff(array_keys($model->getChanges()), ['updated_at', 'updated_by']));
            if ($changed !== []) {
                Audit::log('updated', $model, ['fields' => $changed]);
            }
        });
        static::deleted(fn ($model) => Audit::log('deleted', $model));
    }
}
