<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentPhoto extends Model
{
    protected $fillable = [
        'shipment_id', 'condition_type', 'title',
        'description', 'condition_data',
        'latitude', 'longitude', 'location_name', 'recorded_at',
    ];

    protected $casts = [
        'condition_data' => 'array',
        'recorded_at'    => 'datetime',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }
}
