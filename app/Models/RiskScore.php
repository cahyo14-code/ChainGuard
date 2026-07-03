<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiskScore extends Model
{
    protected $fillable = [
        'country_id',
        'weather_risk',
        'inflation_risk',
        'news_risk',
        'currency_risk',
        'total_risk',
        'risk_level',
        'weather_description',
        'inflation_description',
        'news_description',
        'currency_description',
        'calculated_at',
    ];

    protected $casts = [
        'calculated_at' => 'datetime',
        'weather_risk'  => 'decimal:2',
        'inflation_risk'=> 'decimal:2',
        'news_risk'     => 'decimal:2',
        'currency_risk' => 'decimal:2',
        'total_risk'    => 'decimal:2',
    ];

    // relasi ke negara (many-to-one)
    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}