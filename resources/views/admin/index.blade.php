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
function switchTab(name, btn) {
    // sembunyikan semua tab
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    // tampilkan tab yang dipilih
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}
</script>
@endpush
