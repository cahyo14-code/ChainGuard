@extends('layouts.app')

@section('title', 'Admin Dashboard')
@section('page-title', 'Admin Dashboard')

@push('styles')
<style>
.stat-card {
    background:var(--bg-card); border:1px solid var(--border-color);
    border-radius:10px; padding:16px 20px; margin-bottom:16px; text-align:center;
}
.stat-card .label { font-size:11px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:1px; margin-bottom:4px; }
.stat-card .value { font-size:28px; font-weight:700; color:var(--text-primary); }
.admin-card { background:var(--bg-card); border:1px solid var(--border-color); border-radius:10px; padding:20px; margin-bottom:20px; }
.admin-card h5 { color:var(--text-primary); font-size:15px; margin-bottom:16px; border-bottom:1px solid var(--border-color); padding-bottom:10px; }

/* Data Refresh Panel */
.refresh-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}
.refresh-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 0;
    border-bottom: 1px solid var(--border-color);
    gap: 12px;
    flex-wrap: wrap;
}
.refresh-item:last-child { border-bottom: none; }
.refresh-item .info { flex: 1; min-width: 180px; }
.refresh-item .info .name { font-size: 14px; font-weight: 600; color: var(--text-primary); margin-bottom: 3px; }
.refresh-item .info .meta { font-size: 12px; color: var(--text-secondary); }
.refresh-item .status-badge {
    font-size: 11px; padding: 4px 10px; border-radius: 20px;
    font-weight: 600; white-space: nowrap;
}
.status-fresh   { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.status-stale   { background: #fef9c3; color: #854d0e; border: 1px solid #fef08a; }
.status-unknown { background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb; }
.btn-refresh {
    background: var(--accent-soft); border: 1px solid var(--accent);
    color: var(--accent); padding: 6px 14px; border-radius: 8px;
    font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s;
    white-space: nowrap;
}
.btn-refresh:hover { background: var(--accent); color: white; }
.btn-refresh:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-refresh-all {
    background: var(--accent); border: none; color: white;
    padding: 10px 22px; border-radius: 8px; font-size: 13px;
    font-weight: 600; cursor: pointer; transition: all 0.2s;
    box-shadow: 0 2px 8px rgba(91,110,245,0.3);
}
.btn-refresh-all:hover { background: var(--accent-light); transform: translateY(-1px); }
.btn-refresh-all:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
.progress-bar-refresh {
    height: 3px; border-radius: 2px;
    background: var(--border-color); overflow: hidden; margin-top: 6px;
}
.progress-bar-fill-refresh {
    height: 100%; background: var(--accent);
    border-radius: 2px; width: 0%;
    transition: width 0.4s ease;
}
.form-input {
    background:var(--bg-secondary); border:1px solid var(--border-color);
    color:var(--text-primary); border-radius:8px; padding:9px 12px;
    font-size:13px; width:100%;
}
.form-input:focus { outline:none; border-color:var(--accent-light); }
.form-input option { background:var(--bg-secondary); }
.btn-sm-accent {
    background:var(--accent); border:none; color:white;
    padding:5px 12px; border-radius:6px; font-size:12px; cursor:pointer; transition:all 0.2s;
}
.btn-sm-accent:hover { background:var(--accent-light); }
.btn-sm-danger {
    background:rgba(220,53,69,0.15); border:1px solid rgba(220,53,69,0.3);
    color:#dc3545; padding:5px 10px; border-radius:6px; font-size:12px; cursor:pointer; transition:all 0.2s;
}
.btn-sm-danger:hover { background:rgba(220,53,69,0.3); }
.btn-sm-warning {
    background:rgba(255,193,7,0.15); border:1px solid rgba(255,193,7,0.3);
    color:#ffc107; padding:5px 10px; border-radius:6px; font-size:12px; cursor:pointer; transition:all 0.2s;
}
.btn-sm-warning:hover { background:rgba(255,193,7,0.3); }
.tab-btn {
    background:var(--bg-secondary); border:1px solid var(--border-color);
    color:var(--text-secondary); padding:8px 20px; border-radius:8px;
    font-size:13px; cursor:pointer; transition:all 0.2s;
}
.tab-btn.active, .tab-btn:hover { background:var(--accent); border-color:var(--accent); color:white; }
.tab-content { display:none; }
.tab-content.active { display:block; }
.flag-xs { width:20px; height:13px; object-fit:cover; border-radius:2px; margin-right:6px; }
</style>
@endpush

@section('content')

<div class="page-header">
    <h2>⚙️ Admin Dashboard</h2>
    <p>Kelola user, artikel analisis, dan dataset sistem ChainGuard</p>
</div>

{{-- Flash messages --}}
@if(session('success'))
<div style="background:rgba(40,167,69,0.15); border:1px solid rgba(40,167,69,0.3); color:#28a745; border-radius:8px; padding:12px 16px; margin-bottom:16px; font-size:13px;">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="background:rgba(220,53,69,0.15); border:1px solid rgba(220,53,69,0.3); color:#dc3545; border-radius:8px; padding:12px 16px; margin-bottom:16px; font-size:13px;">
    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
</div>
@endif

{{-- Stat Cards --}}
<div class="row mb-3">
    @foreach([
        ['label'=>'Total User',    'value'=>$stats['total_users'],     'color'=>'var(--accent-light)', 'icon'=>'fa-users'],
        ['label'=>'Total Negara',  'value'=>$stats['total_countries'], 'color'=>'#64b5f6', 'icon'=>'fa-globe'],
        ['label'=>'Total Port',    'value'=>number_format($stats['total_ports']), 'color'=>'#ffd54f', 'icon'=>'fa-anchor'],
        ['label'=>'Total Berita',  'value'=>number_format($stats['total_news']), 'color'=>'#a5d6a7', 'icon'=>'fa-newspaper'],
        ['label'=>'Artikel',       'value'=>$stats['total_articles'],  'color'=>'#ce93d8', 'icon'=>'fa-file-alt'],
        ['label'=>'High Risk',     'value'=>$stats['high_risk'],       'color'=>'#dc3545', 'icon'=>'fa-exclamation-triangle'],
    ] as $s)
    <div class="col-6 col-md-2">
        <div class="stat-card">
            <div class="label"><i class="fas {{ $s['icon'] }}"></i> {{ $s['label'] }}</div>
            <div class="value" style="color:{{ $s['color'] }}">{{ $s['value'] }}</div>
        </div>
    </div>
    @endforeach
</div>

{{-- ═══════════════════════════════════════════════════════ --}}
{{-- PANEL: Real-Time Data Refresh                          --}}
{{-- ═══════════════════════════════════════════════════════ --}}
<div class="refresh-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 style="margin:0; color:var(--text-primary); font-size:15px;">
                🔄 Real-Time Data Refresh
            </h5>
            <small style="color:var(--text-secondary)">Update data dari API eksternal secara manual atau otomatis via scheduler</small>
        </div>
        <button class="btn-refresh-all" id="refreshAllBtn" onclick="refreshData('all')">
            <i class="fas fa-sync-alt"></i> Refresh Semua
        </button>
    </div>

    {{-- Global progress bar --}}
    <div class="progress-bar-refresh" id="globalProgress" style="display:none">
        <div class="progress-bar-fill-refresh" id="globalProgressFill"></div>
    </div>

    {{-- Alert area --}}
    <div id="refreshAlert" style="display:none; margin-top:10px; padding:10px 14px; border-radius:8px; font-size:13px;"></div>

    {{-- Data items --}}
    <div style="margin-top:16px;">

        {{-- Cuaca --}}
        <div class="refresh-item" id="item-weather">
            <div class="d-flex align-items-center gap-12" style="gap:12px">
                <div style="width:38px; height:38px; background:#e0f2fe; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0;">
                    🌦
                </div>
                <div class="info">
                    <div class="name">Data Cuaca</div>
                    <div class="meta">
                        Open-Meteo API · Update setiap <strong>1 jam</strong><br>
                        <span id="weather-count">—</span> negara ·
                        Terakhir: <span id="weather-last">memuat...</span>
                    </div>
                    <div class="progress-bar-refresh" id="progress-weather"><div class="progress-bar-fill-refresh" id="fill-weather"></div></div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2" style="gap:8px">
                <span class="status-badge status-unknown" id="badge-weather">—</span>
                <button class="btn-refresh" id="btn-weather" onclick="refreshData('weather')">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>

        {{-- Kurs --}}
        <div class="refresh-item" id="item-currency">
            <div class="d-flex align-items-center" style="gap:12px">
                <div style="width:38px; height:38px; background:#ede9fe; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0;">
                    💱
                </div>
                <div class="info">
                    <div class="name">Kurs Mata Uang</div>
                    <div class="meta">
                        ExchangeRate API · Update setiap <strong>6 jam</strong><br>
                        <span id="currency-count">—</span> mata uang ·
                        Terakhir: <span id="currency-last">memuat...</span>
                    </div>
                    <div class="progress-bar-refresh" id="progress-currency"><div class="progress-bar-fill-refresh" id="fill-currency"></div></div>
                </div>
            </div>
            <div class="d-flex align-items-center" style="gap:8px">
                <span class="status-badge status-unknown" id="badge-currency">—</span>
                <button class="btn-refresh" id="btn-currency" onclick="refreshData('currency')">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>

        {{-- Berita --}}
        <div class="refresh-item" id="item-news">
            <div class="d-flex align-items-center" style="gap:12px">
                <div style="width:38px; height:38px; background:#fce7f3; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0;">
                    📰
                </div>
                <div class="info">
                    <div class="name">Berita & Sentimen</div>
                    <div class="meta">
                        GNews API · Update setiap <strong>3 jam</strong><br>
                        <span id="news-count">—</span> berita ·
                        Terakhir: <span id="news-last">memuat...</span>
                    </div>
                    <div class="progress-bar-refresh" id="progress-news"><div class="progress-bar-fill-refresh" id="fill-news"></div></div>
                </div>
            </div>
            <div class="d-flex align-items-center" style="gap:8px">
                <span class="status-badge status-unknown" id="badge-news">—</span>
                <button class="btn-refresh" id="btn-news" onclick="refreshData('news')">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>

        {{-- Risk Score --}}
        <div class="refresh-item" id="item-risk">
            <div class="d-flex align-items-center" style="gap:12px">
                <div style="width:38px; height:38px; background:#fee2e2; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0;">
                    ⚠️
                </div>
                <div class="info">
                    <div class="name">Risk Score</div>
                    <div class="meta">
                        Weighted Algorithm · Dihitung ulang setiap <strong>1 jam (jam:30)</strong><br>
                        <span id="risk-count">—</span> negara dinilai ·
                        Terakhir: <span id="risk-last">memuat...</span>
                    </div>
                    <div class="progress-bar-refresh" id="progress-risk"><div class="progress-bar-fill-refresh" id="fill-risk"></div></div>
                </div>
            </div>
            <div class="d-flex align-items-center" style="gap:8px">
                <span class="status-badge status-unknown" id="badge-risk">—</span>
                <button class="btn-refresh" id="btn-risk" onclick="refreshData('risk')">
                    <i class="fas fa-sync-alt"></i> Hitung Ulang
                </button>
            </div>
        </div>

    </div>

    <div style="margin-top:14px; padding:10px 14px; background:#f8fafc; border-radius:8px; font-size:12px; color:var(--text-secondary);">
        <i class="fas fa-info-circle" style="color:var(--accent)"></i>
        <strong>Scheduler otomatis</strong> berjalan di background. Jalankan
        <code style="background:#e2e8f0; padding:1px 6px; border-radius:4px;">php artisan schedule:work</code>
        di terminal untuk mengaktifkan scheduler saat development.
    </div>
</div>

{{-- Tab Navigation --}}
<div class="d-flex gap-2 mb-4">
    <button class="tab-btn active" onclick="switchTab('users', this)">👥 User Management</button>
    <button class="tab-btn" onclick="switchTab('articles', this)">📝 Artikel Analisis</button>
    <button class="tab-btn" onclick="switchTab('ports', this)">⚓ Dataset Port</button>
</div>

{{-- TAB: Users --}}
<div class="tab-content active" id="tab-users">
    <div class="row">
        <div class="col-md-8">
            <div class="admin-card">
                <h5>👥 Daftar User</h5>
                <table class="table table-custom" style="font-size:13px;">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Terdaftar</th>
                            <th style="text-align:center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $i => $user)
                        <tr>
                            <td style="color:var(--text-secondary)">{{ ($users->currentPage()-1)*$users->perPage()+$i+1 }}</td>
                            <td>
                                <i class="fas fa-user" style="color:var(--accent-light); margin-right:6px"></i>
                                {{ $user->name }}
                                @if($user->id === auth()->id())
                                    <span style="font-size:10px; color:var(--accent-light); margin-left:4px">(kamu)</span>
                                @endif
                            </td>
                            <td style="color:var(--text-secondary)">{{ $user->email }}</td>
                            <td style="color:var(--text-secondary)">{{ $user->created_at->format('d M Y') }}</td>
                            <td style="text-align:center">
                                @if($user->id !== auth()->id())
                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" style="display:inline"
                                      onsubmit="return confirm('Hapus user {{ $user->name }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-sm-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @else
                                <span style="color:var(--text-secondary); font-size:11px">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="d-flex justify-content-center mt-2">{{ $users->links() }}</div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="admin-card">
                <h5>➕ Tambah User Baru</h5>
                <form action="{{ route('admin.users.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label style="font-size:11px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:5px">Nama</label>
                        <input type="text" name="name" class="form-input" required placeholder="Nama lengkap">
                        @error('name')<small style="color:#dc3545">{{ $message }}</small>@enderror
                    </div>
                    <div class="mb-3">
                        <label style="font-size:11px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:5px">Email</label>
                        <input type="email" name="email" class="form-input" required placeholder="email@example.com">
                        @error('email')<small style="color:#dc3545">{{ $message }}</small>@enderror
                    </div>
                    <div class="mb-3">
                        <label style="font-size:11px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:5px">Password</label>
                        <input type="password" name="password" class="form-input" required placeholder="Min. 6 karakter">
                        @error('password')<small style="color:#dc3545">{{ $message }}</small>@enderror
                    </div>
                    <button type="submit" class="btn-sm-accent w-100" style="padding:10px">
                        <i class="fas fa-user-plus"></i> Tambah User
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- TAB: Artikel --}}
<div class="tab-content" id="tab-articles">
    <div class="row">
        <div class="col-md-7">
            <div class="admin-card">
                <h5>📝 Daftar Artikel</h5>
                <table class="table table-custom" style="font-size:12px;">
                    <thead>
                        <tr>
                            <th>Judul</th>
                            <th>Kategori</th>
                            <th>Negara</th>
                            <th>Status</th>
                            <th style="text-align:center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($articles as $article)
                        <tr>
                            <td style="max-width:200px">{{ Str::limit($article->title, 50) }}</td>
                            <td style="color:var(--text-secondary)">{{ $article->category ?? '-' }}</td>
                            <td>
                                @if($article->country?->flag_url)
                                    <img class="flag-xs" src="{{ $article->country->flag_url }}" alt="">
                                @endif
                                {{ $article->country?->name ?? 'Global' }}
                            </td>
                            <td>
                                @if($article->status === 'published')
                                    <span class="badge-low" style="font-size:10px">Published</span>
                                @else
                                    <span class="badge-medium" style="font-size:10px">Draft</span>
                                @endif
                            </td>
                            <td style="text-align:center; white-space:nowrap;">
                                {{-- Toggle status --}}
                                <form action="{{ route('admin.articles.status', $article) }}" method="POST" style="display:inline">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $article->status === 'published' ? 'draft' : 'published' }}">
                                    <button type="submit" class="btn-sm-warning" style="margin-right:4px">
                                        <i class="fas fa-exchange-alt"></i>
                                    </button>
                                </form>
                                {{-- Hapus --}}
                                <form action="{{ route('admin.articles.destroy', $article) }}" method="POST" style="display:inline"
                                      onsubmit="return confirm('Hapus artikel ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-sm-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" style="text-align:center; color:var(--text-secondary); padding:30px">
                                Belum ada artikel.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="d-flex justify-content-center mt-2">{{ $articles->links() }}</div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="admin-card">
                <h5>✍️ Buat Artikel Baru</h5>
                <form action="{{ route('admin.articles.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label style="font-size:11px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:5px">Judul</label>
                        <input type="text" name="title" class="form-input" required placeholder="Judul artikel">
                    </div>
                    <div class="mb-3">
                        <label style="font-size:11px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:5px">Kategori</label>
                        <select name="category" class="form-input">
                            <option value="analysis">Analysis</option>
                            <option value="geopolitics">Geopolitics</option>
                            <option value="economy">Economy</option>
                            <option value="logistics">Logistics</option>
                            <option value="general">General</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label style="font-size:11px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:5px">Negara Terkait (opsional)</label>
                        <select name="country_id" class="form-input">
                            <option value="">— Global —</option>
                            @foreach($countries as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label style="font-size:11px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:5px">Konten</label>
                        <textarea name="content" class="form-input" rows="5" required placeholder="Isi artikel..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label style="font-size:11px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:5px">Status</label>
                        <select name="status" class="form-input">
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-sm-accent w-100" style="padding:10px">
                        <i class="fas fa-save"></i> Simpan Artikel
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- TAB: Ports --}}
<div class="tab-content" id="tab-ports">
    <div class="admin-card">
        <h5>⚓ Dataset Pelabuhan — 10 Terbaru</h5>
        <div style="margin-bottom:12px; font-size:13px; color:var(--text-secondary)">
            Total: <strong style="color:var(--text-primary)">{{ number_format($stats['total_ports']) }}</strong> pelabuhan aktif terdaftar.
            Data diambil dari <strong style="color:var(--accent-light)">World Port Index (GitHub)</strong>.
        </div>
        <table class="table table-custom" style="font-size:13px;">
            <thead>
                <tr>
                    <th>Nama Port</th>
                    <th>Kode</th>
                    <th>Kota</th>
                    <th>Negara</th>
                    <th>Tipe</th>
                    <th>Koordinat</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentPorts as $port)
                <tr>
                    <td>{{ $port->name }}</td>
                    <td style="color:var(--accent-light)">{{ $port->code ?? '-' }}</td>
                    <td style="color:var(--text-secondary)">{{ $port->city ?? '-' }}</td>
                    <td>
                        @if($port->country?->flag_url)
                            <img class="flag-xs" src="{{ $port->country->flag_url }}" alt="">
                        @endif
                        {{ $port->country?->name ?? '-' }}
                    </td>
                    <td style="color:var(--text-secondary)">{{ $port->type ?? 'Seaport' }}</td>
                    <td style="font-size:11px; color:var(--text-secondary)">
                        {{ $port->latitude }}, {{ $port->longitude }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-3 text-center">
            <a href="{{ route('ports.index') }}" style="color:var(--accent-light); font-size:13px; text-decoration:none;">
                <i class="fas fa-anchor"></i> Lihat Semua di Port Dashboard
            </a>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// ── Tab switching ──────────────────────────────────────────────
function switchTab(name, btn) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}

// ── Data Refresh Panel ─────────────────────────────────────────
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

// Label & icon per tipe
const TYPE_META = {
    weather:  { label: 'Data Cuaca',       icon: '🌦' },
    currency: { label: 'Kurs Mata Uang',   icon: '💱' },
    news:     { label: 'Berita & Sentimen',icon: '📰' },
    risk:     { label: 'Risk Score',       icon: '⚠️' },
    all:      { label: 'Semua Data',       icon: '🔄' },
};

// Load status awal saat halaman dibuka
document.addEventListener('DOMContentLoaded', loadDataStatus);

async function loadDataStatus() {
    try {
        const res  = await fetch('{{ route("admin.data.status") }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
        });
        const data = await res.json();

        updateStatusUI('weather',  data.weather);
        updateStatusUI('currency', data.currency);
        updateStatusUI('news',     data.news);
        updateStatusUI('risk',     data.risk);
    } catch (e) {
        console.error('Gagal load status:', e);
    }
}

function updateStatusUI(type, info) {
    const lastEl  = document.getElementById(`${type}-last`);
    const countEl = document.getElementById(`${type}-count`);
    const badge   = document.getElementById(`badge-${type}`);

    if (lastEl)  lastEl.textContent  = info.last_update ?? '—';
    if (countEl) countEl.textContent = info.count ? info.count.toLocaleString('id-ID') : '0';

    // tentukan status badge
    if (badge) {
        const text = info.last_update ?? '';
        if (text === 'Belum pernah' || text === '—') {
            badge.textContent = 'Belum ada data';
            badge.className   = 'status-badge status-unknown';
        } else if (text.includes('detik') || text.includes('menit') || text.includes('1 jam')) {
            badge.textContent = '● Fresh';
            badge.className   = 'status-badge status-fresh';
        } else {
            badge.textContent = '○ Perlu update';
            badge.className   = 'status-badge status-stale';
        }
    }
}

// ── Trigger refresh ────────────────────────────────────────────
let isRefreshing = false;

async function refreshData(type) {
    if (isRefreshing) return;
    isRefreshing = true;

    const meta    = TYPE_META[type] || { label: type, icon: '🔄' };
    const allBtns = document.querySelectorAll('.btn-refresh, .btn-refresh-all');
    const progBar = document.getElementById(`progress-${type}`);
    const fill    = document.getElementById(`fill-${type}`);
    const globalProg = document.getElementById('globalProgress');
    const globalFill = document.getElementById('globalProgressFill');
    const alertEl    = document.getElementById('refreshAlert');

    // Disable semua tombol
    allBtns.forEach(b => { b.disabled = true; });

    // Tampilkan progress bar
    if (type === 'all') {
        globalProg.style.display = 'block';
        animateProgress(globalFill, 90, 8000);
    } else if (progBar && fill) {
        progBar.style.display = 'block';
        animateProgress(fill, 90, 5000);
    }

    // Sembunyikan alert lama
    alertEl.style.display = 'none';

    // Update badge jadi "Memuat..."
    const badge = document.getElementById(`badge-${type}`);
    if (badge) { badge.textContent = '⟳ Memuat...'; badge.className = 'status-badge status-unknown'; }
    if (type === 'all') {
        ['weather','currency','news','risk'].forEach(t => {
            const b = document.getElementById(`badge-${t}`);
            if (b) { b.textContent = '⟳ Memuat...'; b.className = 'status-badge status-unknown'; }
        });
    }

    try {
        const res  = await fetch('{{ route("admin.refresh") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json',
                'X-CSRF-TOKEN': CSRF,
            },
            body: JSON.stringify({ type }),
        });
        const data = await res.json();

        // Selesaikan progress
        if (type === 'all') {
            globalFill.style.width = '100%';
            setTimeout(() => { globalProg.style.display = 'none'; globalFill.style.width = '0%'; }, 600);
        } else if (fill) {
            fill.style.width = '100%';
            setTimeout(() => { progBar.style.display = 'none'; fill.style.width = '0%'; }, 600);
        }

        if (data.status === 'success') {
            showAlert('success', `${meta.icon} ${data.message}`);
        } else {
            showAlert('error', `❌ ${data.message}`);
        }

        // Reload status setelah refresh
        await loadDataStatus();

    } catch (e) {
        showAlert('error', `❌ Koneksi gagal. Pastikan server Laravel berjalan.`);
        if (fill) { fill.style.width = '0%'; }
        if (globalFill) { globalFill.style.width = '0%'; }
    } finally {
        allBtns.forEach(b => { b.disabled = false; });
        isRefreshing = false;
    }
}

// ── Progress bar animasi ───────────────────────────────────────
function animateProgress(el, targetPct, durationMs) {
    let current = 0;
    const step  = targetPct / (durationMs / 50);
    const timer = setInterval(() => {
        current += step;
        if (current >= targetPct) { current = targetPct; clearInterval(timer); }
        el.style.width = current + '%';
    }, 50);
}

// ── Alert helper ───────────────────────────────────────────────
function showAlert(type, msg) {
    const el = document.getElementById('refreshAlert');
    el.style.display   = 'block';
    el.style.background = type === 'success' ? '#dcfce7' : '#fee2e2';
    el.style.border     = `1px solid ${type === 'success' ? '#bbf7d0' : '#fecaca'}`;
    el.style.color      = type === 'success' ? '#15803d' : '#b91c1c';
    el.innerHTML        = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${msg}`;
    // auto hide setelah 6 detik
    setTimeout(() => { el.style.display = 'none'; }, 6000);
}

// ── Auto-refresh status setiap 2 menit ────────────────────────
setInterval(loadDataStatus, 120000);
</script>
@endpush
