<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PositiveWord extends Model
{
    protected $fillable = [
        'word',
        'weight',
    ];
}