<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $fillable = [
        'user_id', 'origin_country_id', 'destination_country_id',
        'origin_port', 'destination_port', 'nautical_miles',
        'origin_point_lat', 'origin_point_lng',
        'destination_point_lat', 'destination_point_lng',
        'normal_days', 'normal_eta', 'risk_adjusted_days',
        'risk_adjusted_eta', 'total_delay_days', 'factors',
        'recommendation', 'recommendation_level',
        'status', 'completed_at', 'notes',
    ];

    protected $casts = [
        'factors'      => 'array',
        'normal_eta'   => 'date',
        'risk_adjusted_eta' => 'date',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function originCountry()
    {
        return $this->belongsTo(Country::class, 'origin_country_id');
    }

    public function destinationCountry()
    {
        return $this->belongsTo(Country::class, 'destination_country_id');
    }

    public function photos()
    {
        return $this->hasMany(ShipmentPhoto::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}