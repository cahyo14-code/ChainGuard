<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyHistory extends Model
{
    protected $fillable = [
        'country_id',
        'base_currency',
        'target_currency',
        'rate',
        'rate_date',
    ];

    protected $casts = [
        'rate_date' => 'date',
    ];

    // relasi ke negara (many-to-one)
    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}