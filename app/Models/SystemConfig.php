<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemConfig extends Model
{
    const CREATED_AT = null;

    protected $fillable = [
        'key',
        'value',
        'label',
        'updated_at',
    ];

    protected $casts = [
        'updated_at' => 'datetime',
    ];
}
