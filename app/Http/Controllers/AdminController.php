<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Article;
use App\Models\Country;
use App\Models\Port;
use App\Models\NewsCache;
use App\Models\RiskScore;
use App\Models\WeatherData;
use App\Models\CurrencyRate;
use App\Services\WeatherService;
use App\Services\ExchangeRateService;
use App\Services\NewsService;
use App\Services\SentimentService;
use App\Services\RiskScoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // statistik sistem
        $stats = [
            'total_users'     => User::count(),
            'total_countries' => \App\Models\Country::count(),
            'total_ports'     => Port::count(),
            'total_news'      => NewsCache::count(),
            'total_articles'  => Article::count(),
            'high_risk'       => RiskScore::where('risk_level', 'High')->count(),
        ];

        // daftar user
        $users = User::orderBy('created_at', 'desc')->paginate(10, ['*'], 'users_page');

        // daftar artikel
        $articles = Article::with(['user', 'country'])
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'articles_page');

        // negara untuk dropdown artikel
        $countries = Country::orderBy('name')->get(['id', 'code', 'name']);

        // data port terbaru
        $recentPorts = Port::with('country')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('admin.index', compact(
            'stats',
            'users',
            'articles',
            'countries',
            'recentPorts'
        ));
    }

    // ── Data Status (untuk panel refresh) ─────────────────────
    public function dataStatus()
    {
        $latestWeather  = WeatherData::max('fetched_at');
        $latestCurrency = CurrencyRate::max('fetched_at');
        $latestNews     = NewsCache::max('fetched_at');
        $latestRisk     = RiskScore::max('calculated_at');

        return response()->json([
            'weather'  => [
                'last_update' => $latestWeather  ? \Carbon\Carbon::parse($latestWeather)->diffForHumans()  : 'Belum pernah',
                'count'       => WeatherData::count(),
            ],
            'currency' => [
                'last_update' => $latestCurrency ? \Carbon\Carbon::parse($latestCurrency)->diffForHumans() : 'Belum pernah',
                'count'       => CurrencyRate::count(),
            ],
            'news'     => [
                'last_update' => $latestNews     ? \Carbon\Carbon::parse($latestNews)->diffForHumans()     : 'Belum pernah',
                'count'       => NewsCache::count(),
            ],
            'risk'     => [
                'last_update' => $latestRisk     ? \Carbon\Carbon::parse($latestRisk)->diffForHumans()     : 'Belum pernah',
                'count'       => RiskScore::count(),
            ],
        ]);
    }

    // ── Manual Refresh (trigger fetch via AJAX) ────────────────
    public function refresh(Request $request)
    {
        $type = $request->input('type');

        $allowed = ['weather', 'currency', 'news', 'risk', 'all'];
        if (!in_array($type, $allowed)) {
            return response()->json(['status' => 'error', 'message' => 'Tipe tidak valid.'], 422);
        }

        try {
            switch ($type) {
                case 'weather':
                    Artisan::call('chainguard:fetch-weather');
                    $msg = 'Data cuaca berhasil diperbarui.';
                    break;

                case 'currency':
                    Artisan::call('chainguard:fetch-currency');
                    $msg = 'Kurs mata uang berhasil diperbarui.';
                    break;

                case 'news':
                    Artisan::call('chainguard:fetch-news');
                    $msg = 'Berita berhasil diperbarui + sentimen dianalisis.';
                    break;

                case 'risk':
                    Artisan::call('chainguard:calculate-risk');
                    $msg = 'Risk score berhasil dihitung ulang.';
                    break;

                case 'all':
                    Artisan::call('chainguard:fetch-currency');
                    Artisan::call('chainguard:fetch-weather');
                    Artisan::call('chainguard:fetch-news');
                    Artisan::call('chainguard:calculate-risk');
                    $msg = 'Semua data berhasil diperbarui.';
                    break;
            }

            return response()->json(['status' => 'success', 'message' => $msg]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    // ── User Management ────────────────────────────────────────

    public function storeUser(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'User berhasil ditambahkan.');
    }

    public function destroyUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }
        $user->delete();
        return back()->with('success', 'User berhasil dihapus.');
    }

    // ── Article Management ─────────────────────────────────────
    public function storeArticle(Request $request)
    {
        $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
            'category'=> 'nullable|string|max:100',
        ]);

        Article::create([
            'user_id'      => auth()->id(),
            'country_id'   => $request->country_id ?: null,
            'title'        => $request->title,
            'content'      => $request->content,
            'category'     => $request->category ?? 'general',
            'status'       => $request->input('status', 'draft'),
            'published_at' => $request->status === 'published' ? now() : null,
        ]);

        return back()->with('success', 'Artikel berhasil disimpan.');
    }

    public function destroyArticle(Article $article)
    {
        $article->delete();
        return back()->with('success', 'Artikel berhasil dihapus.');
    }

    public function updateArticleStatus(Request $request, Article $article)
    {
        $status = $request->input('status');
        $article->update([
            'status'       => $status,
            'published_at' => $status === 'published' ? now() : null,
        ]);
        return back()->with('success', 'Status artikel diperbarui.');
    }
}
