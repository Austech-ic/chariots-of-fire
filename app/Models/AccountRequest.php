<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountRequest extends Model
{
    use Concerns\Auditable;

    public const TYPES = ['deactivation' => 'Deactivate account', 'deletion' => 'Delete account and personal data', 'data_export' => 'Copy of my data', 'correction' => 'Correct my data'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = ['handled_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
