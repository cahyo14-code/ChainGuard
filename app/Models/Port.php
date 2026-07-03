<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Port extends Model
{
    protected $fillable = [
        'country_id',
        'name',
        'code',
        'city',
        'latitude',
        'longitude',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude'  => 'decimal:6',
        'longitude' => 'decimal:6',
    ];

    // relasi ke negara (many-to-one)
    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}