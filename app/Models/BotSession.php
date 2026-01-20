<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotSession extends Model
{
    protected $fillable = [
        'phone_number',
        'state',
        'temp_data',
        'last_interaction_at'
    ];

    protected $casts = [
        'temp_data' => 'array',
        'last_interaction_at' => 'datetime'
    ];
}
