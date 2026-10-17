<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactEnquiry extends Model
{
    use Concerns\Auditable;

    public const STATUSES = ['new' => 'New', 'in_progress' => 'In progress', 'resolved' => 'Resolved', 'spam' => 'Spam'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
