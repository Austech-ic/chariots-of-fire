<?php

namespace App\Models\Translations;

use Illuminate\Database\Eloquent\Model;

abstract class Translation extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_approved' => 'boolean'];

    protected $attributes = ['is_approved' => true];
}
