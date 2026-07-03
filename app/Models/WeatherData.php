<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeatherData extends Model
{
    protected $fillable = [
        'country_id',
        'temperature',
        'rainfall',
        'wind_speed',
        'storm_risk',
        'weather_condition',
        'risk_level',
        'fetched_at',
    ];

    protected $casts = [
        'storm_risk' => 'boolean',
        'fetched_at' => 'datetime',
    ];

    // relasi ke negara (many-to-one)
    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}