<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // Cek apakah user sudah login dan punya role admin
        // Untuk sekarang semua user yang login bisa akses admin
        // (bisa diperketat nanti dengan Spatie roles)
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
