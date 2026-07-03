<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
        'user_id',
        'country_id',
        'title',
        'content',
        'category',
        'thumbnail',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    // relasi ke user/admin yang membuat artikel (many-to-one)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // relasi ke negara (many-to-one, opsional)
    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}