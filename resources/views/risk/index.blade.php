@extends('layouts.app')

@section('title', 'Risk Scoring Dashboard')
@section('page-title', 'Risk Scoring Dashboard')

@push('styles')
<style>
    .stat-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 18px 20px;
        margin-bottom: 20px;
        text-align: center;
    }
    .stat-card .label {
        font-size: 11px;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 6px;
    }
    .stat-card .value {
        font-size: 28px;
        font-weight: 700;
    }
    .chart-box {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .chart-box h6 {
        color: var(--text-primary);
        font-size: 14px;
        margin-bottom: 16px;
    }
    .chart-container-sm { position: relative; height: 220px; }
    .chart-container-md { position: relative; height: 300px; }
    .filter-bar {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }
    .filter-btn {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        color: var(--text-secondary);
        padding: 5px 14px;
        border-radius: 6px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .filter-btn:hover, .filter-btn.active {
        background: var(--accent);
        border-color: var(--accent);
        color: white;
    }
    .search-input {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
        border-radius: 8px;
        padding: 7px 14px;
        font-size: 13px;
        width: 220px;
    }
    .search-input:focus { outline: none; border-color: var(--accent-light); }
    .search-input::placeholder { color: var(--text-secondary); }
    .flag-img {
        width: 22px; height: 14px;
        object-fit: cover; border-radius: 2px;
        margin-right: 6px;
    }
    .progress-mini {
        height: 6px;
        background: rgba(255,255,255,0.08);
        border-radius: 3px;
        overflow: hidden;
    }
    .progress-mini-bar {
        height: 100%;
        border-radius: 3px;
        transition: width 0.5s ease;
    }
    .component-pill {
        display: inline-block;
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 10px;
        background: rgba(40,98,58,0.2);
        color: var(--text-secondary);
        margin-right: 4px;
    }
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h2>⚠️ Risk Scoring Dashboard</h2>
        <p>Skor risiko rantai pasok global — dihitung dari cuaca, inflasi, berita & kurs</p>
    </div>
    <div style="font-size:12px; color:var(--text-secondary); text-align:right;">
        <div>Total dinilai: <strong style="color:var(--accent-light)">{{ $totalScored }}</strong> negara</div>
        <div style="margin-top:4px">
            <span class="badge-high" style="font-size:10px">{{ $highCount }} High</span>
            <span class="badge-medium" style="font-size:10px; margin: 0 4px">{{ $mediumCount }} Med</span>
            <span class="badge-low" style="font-size:10px">{{ $lowCount }} Low</span>
        </div>
    </div>
</div>

{{-- Stat Cards --}}
<div class="row mb-2">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="label">Avg. Weather Risk</div>
            <div class="value" style="color:#64b5f6">{{ $avgWeather }}</div>
            <small style="color:var(--text-secondary)">bobot 30%</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="label">Avg. Inflation Risk</div>
            <div class="value" style="color:#ffd54f">{{ $avgInflation }}</div>
            <small style="color:var(--text-secondary)">bobot 20%</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="label">Avg. News Risk</div>
            <div class="value" style="color:#ef9a9a">{{ $avgNews }}</div>
            <small style="color:var(--text-secondary)">bobot 40%</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="label">Avg. Currency Risk</div>
            <div class="value" style="color:#a5d6a7">{{ $avgCurrency }}</div>
            <small style="color:var(--text-secondary)">bobot 10%</small>
        </div>
    </div>
</div>

{{-- Charts Row --}}
<div class="row">
    {{-- Doughnut: Distribusi --}}
    <div class="col-md-4">
        <div class="chart-box">
            <h6>🍩 Distribusi Level Risiko</h6>
            <div class="chart-container-sm">
                <canvas id="doughnutChart"></canvas>
            </div>
            <div class="d-flex justify-content-center gap-3 mt-3" style="font-size:12px">
                <span><span style="display:inline-block;width:10px;height:10px;background:#dc3545;border-radius:50%;margin-right:4px"></span>High ({{ $highCount }})</span>
                <span><span style="display:inline-block;width:10px;height:10px;background:#ffc107;border-radius:50%;margin-right:4px"></span>Medium ({{ $mediumCount }})</span>
                <span><span style="display:inline-block;width:10px;height:10px;background:#28a745;border-radius:50%;margin-right:4px"></span>Low ({{ $lowCount }})</span>
            </div>
        </div>
    </div>

    {{-- Bar: Top 10 --}}
    <div class="col-md-8">
        <div class="chart-box">
            <h6>📊 Top 10 Negara Risiko Tertinggi — Breakdown Komponen</h6>
            <div class="chart-container-md">
                <canvas id="top10Chart"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- Tabel lengkap --}}
<div class="chart-box">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 style="margin:0">🌍 Semua Negara — Skor Risiko Lengkap</h6>
        <div class="d-flex align-items-center gap-2">
            <input type="text" id="tableSearch" class="search-input" placeholder="🔍 Cari negara..." oninput="filterTable()">
        </div>
    </div>

    {{-- Filter level --}}
    <div class="filter-bar">
        <button class="filter-btn active" onclick="filterLevel('all', this)">Semua</button>
        <button class="filter-btn" onclick="filterLevel('High', this)">🔴 High</button>
        <button class="filter-btn" onclick="filterLevel('Medium', this)">🟡 Medium</button>
        <button class="filter-btn" onclick="filterLevel('Low', this)">🟢 Low</button>
    </div>

    <div style="overflow-x:auto;">
        <table class="table table-custom" style="font-size:13px;">
            <thead>
                <tr>
                    <th style="width:30px">#</th>
                    <th>Negara</th>
                    <th style="text-align:center">Cuaca</th>
                    <th style="text-align:center">Inflasi</th>
                    <th style="text-align:center">Berita</th>
                    <th style="text-align:center">Kurs</th>
                    <th style="text-align:center">Total</th>
                    <th style="text-align:center">Level</th>
                    <th style="text-align:center">Detail</th>
                </tr>
            </thead>
            <tbody id="riskTableBody">
                @foreach($riskScores as $index => $score)
                <tr class="risk-row" data-name="{{ strtolower($score->country?->name) }}" data-level="{{ $score->risk_level }}">
                    <td style="color:var(--text-secondary)">
                        {{ ($riskScores->currentPage() - 1) * $riskScores->perPage() + $index + 1 }}
                    </td>
                    <td>
                        @if($score->country?->flag_url)
                            <img class="flag-img" src="{{ $score->country->flag_url }}" alt="">
                        @endif
                        <a href="{{ route('risk.show', $score->country?->code) }}"
                           style="color:var(--accent-light); text-decoration:none;">
                            {{ $score->country?->name ?? '-' }}
                        </a>
                    </td>
                    <td style="text-align:center">
                        <div>{{ $score->weather_risk }}</div>
                        <div class="progress-mini mt-1">
                            <div class="progress-mini-bar" style="width:{{ min($score->weather_risk, 100) }}%; background:#64b5f6"></div>
                        </div>
                    </td>
                    <td style="text-align:center">
                        <div>{{ $score->inflation_risk }}</div>
                        <div class="progress-mini mt-1">
                            <div class="progress-mini-bar" style="width:{{ min($score->inflation_risk, 100) }}%; background:#ffd54f"></div>
                        </div>
                    </td>
                    <td style="text-align:center">
                        <div>{{ $score->news_risk }}</div>
                        <div class="progress-mini mt-1">
                            <div class="progress-mini-bar" style="width:{{ min($score->news_risk, 100) }}%; background:#ef9a9a"></div>
                        </div>
                    </td>
                    <td style="text-align:center">
                        <div>{{ $score->currency_risk }}</div>
                        <div class="progress-mini mt-1">
                            <div class="progress-mini-bar" style="width:{{ min($score->currency_risk, 100) }}%; background:#a5d6a7"></div>
                        </div>
                    </td>
                    <td style="text-align:center">
                        <strong style="font-size:15px; color:
                            @if($score->risk_level === 'High') #dc3545
                            @elseif($score->risk_level === 'Medium') #ffc107
                            @else #28a745 @endif">
                            {{ $score->total_risk }}
                        </strong>
                    </td>
                    <td style="text-align:center">
                        @if($score->risk_level === 'High')
                            <span class="badge-high">High</span>
                        @elseif($score->risk_level === 'Medium')
                            <span class="badge-medium">Medium</span>
                        @else
                            <span class="badge-low">Low</span>
                        @endif
                    </td>
                    <td style="text-align:center">
                        <a href="{{ route('risk.show', $score->country?->code) }}"
                           style="color:var(--accent-light); font-size:13px; text-decoration:none;"
                           title="Lihat detail">
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="d-flex justify-content-center mt-3">
        {{ $riskScores->links() }}
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Doughnut chart ─────────────────────────────────────────────
new Chart(document.getElementById('doughnutChart'), {
    type: 'doughnut',
    data: {
        labels: ['High Risk', 'Medium Risk', 'Low Risk'],
        datasets: [{
            data: [{{ $highCount }}, {{ $mediumCount }}, {{ $lowCount }}],
            backgroundColor: ['#dc3545', '#ffc107', '#28a745'],
            borderColor: '#1a3a2a',
            borderWidth: 3,
            hoverOffset: 8,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#162830',
                titleColor: '#fff',
                bodyColor: '#a8c5b5',
                borderColor: '#28623A',
                borderWidth: 1,
                callbacks: {
                    label: ctx => ` ${ctx.label}: ${ctx.raw} negara`
                }
            }
        }
    }
});

// ── Bar chart Top 10 ───────────────────────────────────────────
const top10Labels  = @json($top10->map(fn($s) => $s->country?->name ?? '-')->values());
const top10Weather = @json($top10->pluck('weather_risk')->map(fn($v) => (float)$v)->values());
const top10Inflasi = @json($top10->pluck('inflation_risk')->map(fn($v) => (float)$v)->values());
const top10News    = @json($top10->pluck('news_risk')->map(fn($v) => (float)$v)->values());
const top10Curr    = @json($top10->pluck('currency_risk')->map(fn($v) => (float)$v)->values());

new Chart(document.getElementById('top10Chart'), {
    type: 'bar',
    data: {
        labels: top10Labels,
        datasets: [
            {
                label: 'Cuaca (30%)',
                data: top10Weather.map(v => +(v * 0.30).toFixed(2)),
                backgroundColor: 'rgba(100,181,246,0.8)',
                borderRadius: 3,
            },
            {
                label: 'Inflasi (20%)',
                data: top10Inflasi.map(v => +(v * 0.20).toFixed(2)),
                backgroundColor: 'rgba(255,213,79,0.8)',
                borderRadius: 3,
            },
            {
                label: 'Berita (40%)',
                data: top10News.map(v => +(v * 0.40).toFixed(2)),
                backgroundColor: 'rgba(239,154,154,0.8)',
                borderRadius: 3,
            },
            {
                label: 'Kurs (10%)',
                data: top10Curr.map(v => +(v * 0.10).toFixed(2)),
                backgroundColor: 'rgba(165,214,167,0.8)',
                borderRadius: 3,
            },
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: { color: '#a8c5b5', font: { size: 11 } }
            },
            tooltip: {
                backgroundColor: '#162830',
                titleColor: '#fff',
                bodyColor: '#a8c5b5',
                borderColor: '#28623A',
                borderWidth: 1,
            }
        },
        scales: {
            x: {
                stacked: true,
                ticks: { color: '#a8c5b5', font: { size: 10 }, maxRotation: 35 },
                grid:  { color: 'rgba(40,98,58,0.12)' }
            },
            y: {
                stacked: true,
                max: 100,
                ticks: { color: '#a8c5b5', font: { size: 11 } },
                grid:  { color: 'rgba(40,98,58,0.12)' }
            }
        }
    }
});

// ── Filter tabel ───────────────────────────────────────────────
let activeLevel = 'all';

function filterTable() {
    applyFilters();
}

function filterLevel(level, btn) {
    activeLevel = level;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    applyFilters();
}

function applyFilters() {
    const q = (document.getElementById('tableSearch').value || '').toLowerCase();
    document.querySelectorAll('#riskTableBody .risk-row').forEach(row => {
        const nameMatch  = (row.dataset.name || '').includes(q);
        const levelMatch = activeLevel === 'all' || row.dataset.level === activeLevel;
        row.style.display = (nameMatch && levelMatch) ? '' : 'none';
    });
}
</script>
@endpush
