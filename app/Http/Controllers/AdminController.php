<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Article;
use App\Models\Country;
use App\Models\Port;
use App\Models\NewsCache;
use App\Models\RiskScore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
