<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Watchlist extends Model
{
    protected $fillable = [
        'user_id',
        'country_id',
        'notes',
    ];

    // relasi ke user (many-to-one)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // relasi ke negara (many-to-one)
    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}