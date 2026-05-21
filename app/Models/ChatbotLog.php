<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'session_id',
        'message',
        'response',
        'tokens_used',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
