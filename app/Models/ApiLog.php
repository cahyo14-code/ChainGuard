<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    protected $fillable = [
        'api_name',
        'endpoint',
        'method',
        'status_code',
        'is_success',
        'error_message',
        'response_time',
        'called_at',
    ];

    protected $casts = [
        'is_success' => 'boolean',
        'called_at'  => 'datetime',
    ];
}