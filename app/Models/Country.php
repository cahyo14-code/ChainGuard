<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = [
    'code',
    'name',
    'capital',
    'currency_code',
    'currency_name',
    'flag_url',
    'population',
    'region',
    'subregion',
    'latitude',
    'longitude',
];


    public function economicIndicators()
    {
        return $this->hasMany(EconomicIndicator::class);
    }

    // 1 negara punya banyak data kurs
    public function currencyRates()
    {
        return $this->hasMany(CurrencyRate::class);
    }

    // 1 negara punya banyak riwayat kurs
    public function currencyHistories()
    {
        return $this->hasMany(CurrencyHistory::class);
    }

    // 1 negara punya banyak data cuaca
    public function weatherData()
    {
        return $this->hasMany(WeatherData::class);
    }

    // 1 negara punya banyak pelabuhan
    public function ports()
    {
        return $this->hasMany(Port::class);
    }

    // 1 negara punya banyak berita
    public function newsCache()
    {
        return $this->hasMany(NewsCache::class);
    }

    // 1 negara punya 1 skor risiko terkini
    public function riskScore()
    {
        return $this->hasOne(RiskScore::class);
    }

    // 1 negara punya banyak riwayat risiko
    public function riskHistories()
    {
        return $this->hasMany(RiskHistory::class);
    }

    // 1 negara bisa ada di banyak watchlist user
    public function watchlists()
    {
        return $this->hasMany(Watchlist::class);
    }

    // 1 negara bisa punya banyak artikel
    public function articles()
    {
        return $this->hasMany(Article::class);
    }
}