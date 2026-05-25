<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemConfig extends Model
{
    protected $fillable = [
        'key',
        'value',
        'label',
        'group',
    ];

    protected $casts = [
        'updated_at' => 'datetime',
    ];
}
