<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EconomicIndicator extends Model
{
    protected $fillable = [
        'country_id',
        'year',
        'gdp',
        'inflation',
        'population',
        'exports',
        'imports',
    ];

    // relasi ke negara (many-to-one)
    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}