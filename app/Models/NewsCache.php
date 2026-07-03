<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsCache extends Model
{
    protected $fillable = [
        'country_id',
        'title',
        'description',
        'url',
        'source',
        'category',
        'sentiment',
        'positive_score',
        'negative_score',
        'published_at',
        'fetched_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'fetched_at'   => 'datetime',
    ];

    // relasi ke negara (many-to-one)
    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}