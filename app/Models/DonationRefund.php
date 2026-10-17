<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DonationRefund extends Model
{
    use Concerns\Auditable;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
