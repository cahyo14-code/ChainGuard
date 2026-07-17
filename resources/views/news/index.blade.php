@extends('layouts.app')

@section('title', 'News Intelligence')
@section('page-title', 'News Intelligence')

@push('styles')
<style>
.stat-card {
    background:var(--bg-card); border:1px solid var(--border-color);
    border-radius:10px; padding:16px 20px; margin-bottom:16px; text-align:center;
}
.stat-card .label { font-size:11px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:1px; margin-bottom:4px; }
.stat-card .value { font-size:26px; font-weight:700; }
.news-card {
    background:var(--bg-card); border:1px solid var(--border-color);
    border-radius:10px; padding:14px 18px; margin-bottom:10px;
    transition: border-color 0.2s;
}
.news-card:hover { border-color:var(--accent-light); }
.news-title { font-size:14px; color:var(--text-primary); margin-bottom:6px; line-height:1.5; }
.news-title a { color:var(--text-primary); text-decoration:none; }
.news-title a:hover { color:var(--accent-light); }
.news-meta { display:flex; align-items:center; gap:12px; flex-wrap:wrap; font-size:12px; color:var(--text-secondary); }
.flag-xs { width:18px; height:11px; object-fit:cover; border-radius:2px; }
.search-box {
    background:var(--bg-secondary); border:1px solid var(--border-color);
    color:var(--text-primary); border-radius:8px; padding:9px 14px;
    font-size:13px; width:100%;
}
.search-box:focus { outline:none; border-color:var(--accent-light); }
.search-box::placeholder { color:var(--text-secondary); }
.form-select-custom {
    background:var(--bg-secondary); border:1px solid var(--border-color);
    color:var(--text-primary); border-radius:8px; padding:9px 12px;
    font-size:13px; width:100%;
}
.form-select-custom:focus { outline:none; border-color:var(--accent-light); }
.form-select-custom option { background:var(--bg-secondary); }
.filter-btn {
    background:var(--bg-secondary); border:1px solid var(--border-color);
    color:var(--text-secondary); padding:6px 14px; border-radius:6px;
    font-size:12px; cursor:pointer; transition:all 0.2s;
}
.filter-btn:hover, .filter-btn.active { background:var(--accent); border-color:var(--accent); color:white; }
.chart-box { background:var(--bg-card); border:1px solid var(--border-color); border-radius:10px; padding:18px; margin-bottom:16px; }
.chart-box h6 { color:var(--text-primary); font-size:14px; margin-bottom:14px; }
.chart-sm { position:relative; height:180px; }
</style>
@endpush

@section('content')

<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h2>📰 News Intelligence</h2>
        <p>Analisis sentimen berita ekonomi, logistik & geopolitik global</p>
    </div>
    <div style="font-size:12px; color:var(--text-secondary); text-align:right">
        Sumber: <strong style="color:var(--accent-light)">GNews API</strong>
    </div>
</div>

{{-- Stat Cards --}}
<div class="row mb-3">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="label">Total Berita</div>
            <div class="value" style="color:var(--accent-light)">{{ number_format($totalNews) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="label">Positif</div>
            <div class="value" style="color:#28a745">{{ $positiveNews }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="label">Negatif</div>
            <div class="value" style="color:#dc3545">{{ $negativeNews }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="label">Netral</div>
            <div class="value" style="color:#ffc107">{{ $neutralNews }}</div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Sidebar: chart & top negatif --}}
    <div class="col-md-4">
        <div class="chart-box">
            <h6>🍩 Distribusi Sentimen</h6>
            <div class="chart-sm"><canvas id="sentimentChart"></canvas></div>
            <div class="d-flex justify-content-center gap-3 mt-3" style="font-size:11px">
                <span><span style="display:inline-block;width:10px;height:10px;background:#28a745;border-radius:50%;margin-right:3px"></span>Positif</span>
                <span><span style="display:inline-block;width:10px;height:10px;background:#ffc107;border-radius:50%;margin-right:3px"></span>Netral</span>
                <span><span style="display:inline-block;width:10px;height:10px;background:#dc3545;border-radius:50%;margin-right:3px"></span>Negatif</span>
            </div>
        </div>

        <div class="chart-box">
            <h6>🔴 Top Berita Negatif per Negara</h6>
            @forelse($topNegative as $item)
            <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--border-color); font-size:13px;">
                <div style="display:flex; align-items:center; gap:8px;">
                    @if($item->country?->flag_url)
                        <img class="flag-xs" src="{{ $item->country->flag_url }}" alt="">
                    @endif
                    <a href="{{ route('countries.show', $item->country?->code) }}"
                       style="color:var(--accent-light); text-decoration:none;">
                        {{ $item->country?->name ?? '-' }}
                    </a>
                </div>
                <span class="badge-high" style="font-size:11px">{{ $item->neg_count }} berita</span>
            </div>
            @empty
            <p style="color:var(--text-secondary); font-size:13px">Tidak ada data.</p>
            @endforelse
        </div>

        {{-- Filter --}}
        <div class="chart-box">
            <h6>🔽 Filter Berita</h6>
            <div class="mb-2">
                <label style="font-size:11px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:1px">Negara</label>
                <form method="GET" action="{{ route('news.index') }}">
                    <select name="country" class="form-select-custom mb-2" onchange="this.form.submit()">
                        <option value="">— Semua Negara —</option>
                        @foreach($countries as $c)
                        <option value="{{ $c->code }}" {{ request('country') === $c->code ? 'selected' : '' }}>
                            {{ $c->name }}
                        </option>
                        @endforeach
                    </select>
                    <select name="sentiment" class="form-select-custom mb-2" onchange="this.form.submit()">
                        <option value="">— Semua Sentimen —</option>
                        <option value="Positive" {{ request('sentiment') === 'Positive' ? 'selected' : '' }}>✅ Positif</option>
                        <option value="Neutral"  {{ request('sentiment') === 'Neutral'  ? 'selected' : '' }}>⬜ Netral</option>
                        <option value="Negative" {{ request('sentiment') === 'Negative' ? 'selected' : '' }}>❌ Negatif</option>
                    </select>
                    @if(request('country') || request('sentiment'))
                    <a href="{{ route('news.index') }}" style="color:var(--text-secondary); font-size:12px;">
                        <i class="fas fa-times"></i> Reset Filter
                    </a>
                    @endif
                </form>
            </div>
        </div>
    </div>

    {{-- Daftar berita --}}
    <div class="col-md-8">
        @forelse($news as $item)
        <div class="news-card">
            <div class="news-title">
                @if($item->url)
                    <a href="{{ $item->url }}" target="_blank">
                        {{ $item->title }}
                        <i class="fas fa-external-link-alt" style="font-size:10px; color:var(--text-secondary); margin-left:4px"></i>
                    </a>
                @else
                    {{ $item->title }}
                @endif
            </div>
            @if($item->description)
            <p style="font-size:12px; color:var(--text-secondary); margin-bottom:8px; line-height:1.5;">
                {{ Str::limit($item->description, 160) }}
            </p>
            @endif
            <div class="news-meta">
                <span>
                    @if($item->country?->flag_url)
                        <img class="flag-xs" src="{{ $item->country->flag_url }}" alt="">
                    @endif
                    <a href="{{ route('countries.show', $item->country?->code) }}"
                       style="color:var(--accent-light); text-decoration:none;">
                        {{ $item->country?->name ?? '-' }}
                    </a>
                </span>
                <span><i class="fas fa-globe" style="margin-right:3px"></i>{{ $item->source ?? '-' }}</span>
                <span><i class="fas fa-clock" style="margin-right:3px"></i>{{ $item->published_at?->diffForHumans() ?? '-' }}</span>
                <span>
                    @if($item->sentiment === 'Positive')
                        <span class="badge-low" style="font-size:10px">✅ Positif</span>
                    @elseif($item->sentiment === 'Negative')
                        <span class="badge-high" style="font-size:10px">❌ Negatif</span>
                    @else
                        <span class="badge-medium" style="font-size:10px">⬜ Netral</span>
                    @endif
                </span>
                @if($item->positive_score || $item->negative_score)
                <span style="color:var(--text-secondary); font-size:11px">
                    +{{ $item->positive_score }} / -{{ $item->negative_score }}
                </span>
                @endif
            </div>
        </div>
        @empty
        <div style="text-align:center; padding:60px 20px; color:var(--text-secondary);">
            <i class="fas fa-newspaper" style="font-size:36px; opacity:0.2; display:block; margin-bottom:12px"></i>
            Tidak ada berita ditemukan.
        </div>
        @endforelse

        <div class="d-flex justify-content-center mt-3">
            {{ $news->appends(request()->query())->links() }}
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('sentimentChart'), {
    type: 'doughnut',
    data: {
        labels: ['Positif', 'Netral', 'Negatif'],
        datasets: [{
            data: [{{ $positiveNews }}, {{ $neutralNews }}, {{ $negativeNews }}],
            backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
            borderColor: '#ffffff', borderWidth: 3, hoverOffset: 8,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false, cutout: '65%',
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#ffffff', titleColor: '#2d3748', bodyColor: '#718096',
                borderColor: '#e2e8f0', borderWidth: 1,
                callbacks: { label: ctx => ` ${ctx.label}: ${ctx.raw} berita` }
            }
        }
    }
});
</script>
@endpush
