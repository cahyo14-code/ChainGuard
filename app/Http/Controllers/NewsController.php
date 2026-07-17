<?php

namespace App\Http\Controllers;

use App\Models\NewsCache;
use App\Models\Country;

class NewsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // statistik sentimen global
        $totalNews    = NewsCache::count();
        $positiveNews = NewsCache::where('sentiment', 'Positive')->count();
        $negativeNews = NewsCache::where('sentiment', 'Negative')->count();
        $neutralNews  = NewsCache::where('sentiment', 'Neutral')->count();

        // berita terbaru dengan paginasi
        $news = NewsCache::with('country')
            ->when(request('country'), function ($q) {
                $q->whereHas('country', fn($c) => $c->where('code', strtoupper(request('country'))));
            })
            ->when(request('sentiment'), function ($q) {
                $q->where('sentiment', ucfirst(strtolower(request('sentiment'))));
            })
            ->orderBy('fetched_at', 'desc')
            ->paginate(20);

        // negara yang punya berita (untuk filter dropdown)
        $countries = Country::whereHas('newsCache')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'flag_url']);

        // top 5 negara berita negatif terbanyak
        $topNegative = NewsCache::selectRaw('country_id, COUNT(*) as neg_count')
            ->where('sentiment', 'Negative')
            ->groupBy('country_id')
            ->orderByDesc('neg_count')
            ->take(5)
            ->with('country')
            ->get();

        return view('news.index', compact(
            'totalNews',
            'positiveNews',
            'negativeNews',
            'neutralNews',
            'news',
            'countries',
            'topNegative'
        ));
    }
}
