<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsCache;
use App\Models\Country;
use Illuminate\Http\Request;

class NewsApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * GET /api/news
     * Daftar berita dengan opsional filter negara & sentimen
     */
    public function index(Request $request)
    {
        $query = NewsCache::with('country')
            ->orderBy('fetched_at', 'desc');

        if ($request->filled('country_code')) {
            $query->whereHas('country', function ($q) use ($request) {
                $q->where('code', strtoupper($request->country_code));
            });
        }

        if ($request->filled('sentiment')) {
            $query->where('sentiment', ucfirst(strtolower($request->sentiment)));
        }

        $limit = (int) $request->get('limit', 20);
        $limit = min(max($limit, 5), 50);

        $news = $query->take($limit)->get()->map(function ($item) {
            return [
                'id'           => $item->id,
                'title'        => $item->title,
                'description'  => $item->description,
                'url'          => $item->url,
                'source'       => $item->source,
                'category'     => $item->category,
                'sentiment'    => $item->sentiment,
                'country_name' => $item->country?->name,
                'country_code' => $item->country?->code,
                'published_at' => $item->published_at?->toDateTimeString(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'count'  => $news->count(),
            'data'   => $news,
        ]);
    }
}
