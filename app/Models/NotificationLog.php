<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = ['sent_at' => 'datetime'];
}
