<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Cek apakah user memiliki role admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin' || (method_exists($this, 'hasRole') && $this->hasRole('admin'));
    }

    /**
     * Cek apakah user memiliki role pengguna biasa
     */
    public function isUser(): bool
    {
        return $this->role === 'user' || (method_exists($this, 'hasRole') && $this->hasRole('user'));
    }

    // 1 user punya banyak watchlist
    public function watchlists()
    {
        return $this->hasMany(Watchlist::class);
    }

    // 1 user (admin) bisa punya banyak artikel
    public function articles()
    {
        return $this->hasMany(Article::class);
    }
}

