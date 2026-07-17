@extends('layouts.app')

@section('title', 'Risk Detail — ' . $country->name)
@section('page-title', 'Risk Detail')

@push('styles')
<style>
    .risk-hero {
        background: linear-gradient(135deg, var(--bg-card) 0%, var(--border-color) 100%);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 20px;
    }
    .risk-score-big {
        font-size: 64px;
        font-weight: 800;
        line-height: 1;
    }
    .risk-level-badge {
        display: inline-block;
        padding: 6px 18px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 700;
        margin-top: 8px;
    }
    .component-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 16px;
        margin-bottom: 12px;
    }
    .component-card .comp-label {
        font-size: 11px;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 4px;
    }
    .component-card .comp-score {
        font-size: 22px;
        font-weight: 700;
    }
    .component-card .comp-desc {
        font-size: 12px;
        color: var(--text-secondary);
        margin-top: 6px;
        line-height: 1.5;
    }
    .progress-bar-custom {
        height: 8px;
        background: rgba(255,255,255,0.08);
        border-radius: 4px;
        overflow: hidden;
        margin-top: 8px;
    }
    .progress-bar-fill {
        height: 100%;
        border-radius: 4px;
        transition: width 0.6s ease;
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
    .chart-container-md { position: relative; height: 260px; }
    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid var(--border-color);
        font-size: 13px;
    }
    .info-row:last-child { border-bottom: none; }
    .info-row .key   { color: var(--text-secondary); }
    .info-row .val   { color: var(--text-primary); font-weight: 600; }
    .nav-country-btn {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        color: var(--text-secondary);
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 13px;
        text-decoration: none;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .nav-country-btn:hover {
        background: var(--accent);
        border-color: var(--accent);
        color: white;
    }
    .flag-big {
        width: 42px; height: 28px;
        object-fit: cover;
        border-radius: 4px;
        margin-right: 12px;
    }
    .news-item {
        padding: 10px 0;
        border-bottom: 1px solid var(--border-color);
    }
    .news-item:last-child { border-bottom: none; }
</style>
@endpush

@section('content')

{{-- Navigasi atas --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('risk.index') }}" style="color:var(--text-secondary); text-decoration:none; font-size:13px;">
        <i class="fas fa-arrow-left"></i> Kembali ke Risk Dashboard
    </a>
    <div class="d-flex gap-2">
        @if($prevCountry)
        <a href="{{ route('risk.show', $prevCountry->code) }}" class="nav-country-btn">
            <i class="fas fa-chevron-up"></i> Risiko lebih tinggi
        </a>
        @endif
        @if($nextCountry)
        <a href="{{ route('risk.show', $nextCountry->code) }}" class="nav-country-btn">
            Risiko lebih rendah <i class="fas fa-chevron-down"></i>
        </a>
        @endif
    </div>
</div>

{{-- Hero: Skor utama --}}
<div class="risk-hero">
    <div class="row align-items-center">
        <div class="col-md-6">
            <div class="d-flex align-items-center mb-2">
                @if($country->flag_url)
                    <img src="{{ $country->flag_url }}" alt="" class="flag-big">
                @endif
                <div>
                    <h3 style="margin:0; color:var(--text-primary)">{{ $country->name }}</h3>
                    <small style="color:var(--text-secondary)">{{ $country->capital ?? '-' }} · {{ $country->region }}</small>
                </div>
            </div>

            @if($riskScore)
            <div class="risk-score-big" style="color:
                @if($riskScore->risk_level === 'High') #dc3545
                @elseif($riskScore->risk_level === 'Medium') #ffc107
                @else #28a745 @endif">
                {{ $riskScore->total_risk }}
            </div>
            <div>
                <span class="risk-level-badge
                    @if($riskScore->risk_level === 'High') badge-high
                    @elseif($riskScore->risk_level === 'Medium') badge-medium
                    @else badge-low @endif">
                    {{ $riskScore->risk_level }} Risk
                </span>
            </div>
            <small style="color:var(--text-secondary); font-size:11px; display:block; margin-top:10px;">
                <i class="fas fa-clock"></i>
                Dihitung: {{ $riskScore->calculated_at?->diffForHumans() ?? '-' }}
            </small>
            @else
            <div style="color:var(--text-secondary); font-size:14px; margin-top:12px;">
                <i class="fas fa-exclamation-circle"></i> Risk score belum tersedia untuk negara ini.
            </div>
            @endif
        </div>

        @if($riskScore)
        <div class="col-md-6">
            {{-- Radar Chart --}}
            <div class="chart-container-sm">
                <canvas id="radarChart"></canvas>
            </div>
        </div>
        @endif
    </div>
</div>

@if($riskScore)
{{-- Breakdown 4 Komponen --}}
<div class="row mb-2">
    <div class="col-md-3">
        <div class="component-card">
            <div class="comp-label">🌦 Cuaca</div>
            <div class="comp-score" style="color:#64b5f6">{{ $riskScore->weather_risk }}</div>
            <div class="progress-bar-custom">
                <div class="progress-bar-fill" style="width:{{ min($riskScore->weather_risk,100) }}%; background:#64b5f6"></div>
            </div>
            <div class="comp-desc">{{ $riskScore->weather_description ?? '-' }}</div>
            <small style="color:var(--text-secondary); font-size:10px">bobot 30%</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="component-card">
            <div class="comp-label">📈 Inflasi</div>
            <div class="comp-score" style="color:#ffd54f">{{ $riskScore->inflation_risk }}</div>
            <div class="progress-bar-custom">
                <div class="progress-bar-fill" style="width:{{ min($riskScore->inflation_risk,100) }}%; background:#ffd54f"></div>
            </div>
            <div class="comp-desc">{{ $riskScore->inflation_description ?? '-' }}</div>
            <small style="color:var(--text-secondary); font-size:10px">bobot 20%</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="component-card">
            <div class="comp-label">📰 Sentimen Berita</div>
            <div class="comp-score" style="color:#ef9a9a">{{ $riskScore->news_risk }}</div>
            <div class="progress-bar-custom">
                <div class="progress-bar-fill" style="width:{{ min($riskScore->news_risk,100) }}%; background:#ef9a9a"></div>
            </div>
            <div class="comp-desc">{{ $riskScore->news_description ?? '-' }}</div>
            <small style="color:var(--text-secondary); font-size:10px">bobot 40%</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="component-card">
            <div class="comp-label">💱 Volatilitas Kurs</div>
            <div class="comp-score" style="color:#a5d6a7">{{ $riskScore->currency_risk }}</div>
            <div class="progress-bar-custom">
                <div class="progress-bar-fill" style="width:{{ min($riskScore->currency_risk,100) }}%; background:#a5d6a7"></div>
            </div>
            <div class="comp-desc">{{ $riskScore->currency_description ?? '-' }}</div>
            <small style="color:var(--text-secondary); font-size:10px">bobot 10%</small>
        </div>
    </div>
</div>

{{-- Charts Row --}}
<div class="row">
    {{-- Trend 30 hari --}}
    <div class="col-md-8">
        <div class="chart-box">
            <h6>📉 Trend Total Risk — 30 Hari Terakhir</h6>
            @if($riskHistory->count() >= 2)
            <div class="chart-container-md">
                <canvas id="trendChart"></canvas>
            </div>
            @else
            <div style="color:var(--text-secondary); font-size:13px; padding: 40px 0; text-align:center;">
                <i class="fas fa-database" style="font-size:28px; opacity:0.3"></i>
                <p style="margin-top:8px">Data historis belum cukup (minimal 2 hari).</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Pie: Sentimen Berita --}}
    <div class="col-md-4">
        <div class="chart-box">
            <h6>🗞 Sentimen Berita</h6>
            @if(($positiveNews + $negativeNews + $neutralNews) > 0)
            <div class="chart-container-sm">
                <canvas id="sentimentChart"></canvas>
            </div>
            <div class="d-flex justify-content-center gap-3 mt-3" style="font-size:11px">
                <span><span style="display:inline-block;width:10px;height:10px;background:#28a745;border-radius:50%;margin-right:3px"></span>Positif ({{ $positiveNews }})</span>
                <span><span style="display:inline-block;width:10px;height:10px;background:#ffc107;border-radius:50%;margin-right:3px"></span>Netral ({{ $neutralNews }})</span>
                <span><span style="display:inline-block;width:10px;height:10px;background:#dc3545;border-radius:50%;margin-right:3px"></span>Negatif ({{ $negativeNews }})</span>
            </div>
            @else
            <div style="color:var(--text-secondary); font-size:13px; padding:30px 0; text-align:center;">
                <i class="fas fa-newspaper" style="font-size:28px; opacity:0.3"></i>
                <p style="margin-top:8px">Belum ada data berita.</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endif

{{-- Info Negara & Berita --}}
<div class="row">
    {{-- Data Ekonomi & Cuaca --}}
    <div class="col-md-5">
        <div class="chart-box">
            <h6>🏛 Data Ekonomi & Kondisi Saat Ini</h6>

            @if($economic)
            <div class="info-row">
                <span class="key">GDP ({{ $economic->year }})</span>
                <span class="val">${{ $economic->gdp ? number_format($economic->gdp / 1e9, 1) . 'B' : '-' }}</span>
            </div>
            <div class="info-row">
                <span class="key">Inflasi ({{ $economic->year }})</span>
                <span class="val">{{ $economic->inflation ? number_format($economic->inflation, 2) . '%' : '-' }}</span>
            </div>
            <div class="info-row">
                <span class="key">Ekspor</span>
                <span class="val">${{ $economic->exports ? number_format($economic->exports / 1e9, 1) . 'B' : '-' }}</span>
            </div>
            <div class="info-row">
                <span class="key">Impor</span>
                <span class="val">${{ $economic->imports ? number_format($economic->imports / 1e9, 1) . 'B' : '-' }}</span>
            </div>
            @else
            <p style="color:var(--text-secondary); font-size:13px">Data ekonomi belum tersedia.</p>
            @endif

            @if($weather)
            <div style="margin-top:14px; padding-top:12px; border-top:1px solid var(--border-color);">
                <div style="font-size:12px; color:var(--text-secondary); margin-bottom:8px; text-transform:uppercase; letter-spacing:1px">Cuaca Terkini</div>
                <div class="info-row">
                    <span class="key">Kondisi</span>
                    <span class="val">{{ $weather->weather_condition ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="key">Suhu</span>
                    <span class="val">{{ $weather->temperature ? $weather->temperature . '°C' : '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="key">Curah Hujan</span>
                    <span class="val">{{ $weather->rainfall ? $weather->rainfall . ' mm' : '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="key">Angin</span>
                    <span class="val">{{ $weather->wind_speed ? $weather->wind_speed . ' km/h' : '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="key">Risiko Badai</span>
                    <span class="val" style="color:{{ $weather->storm_risk ? '#dc3545' : '#28a745' }}">
                        {{ $weather->storm_risk ? '⚡ Ya' : '✓ Tidak' }}
                    </span>
                </div>
            </div>
            @endif

            @if($currency)
            <div style="margin-top:14px; padding-top:12px; border-top:1px solid var(--border-color);">
                <div style="font-size:12px; color:var(--text-secondary); margin-bottom:8px; text-transform:uppercase; letter-spacing:1px">Kurs Terkini</div>
                <div class="info-row">
                    <span class="key">1 USD =</span>
                    <span class="val">{{ number_format($currency->rate, 2) }} {{ $currency->target_currency }}</span>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Berita Terkini --}}
    <div class="col-md-7">
        <div class="chart-box">
            <h6>📰 Berita Terkini — {{ $country->name }}</h6>
            @forelse($newsItems as $news)
            <div class="news-item">
                <div style="font-size:13px; color:var(--text-primary); margin-bottom:5px;">
                    @if($news->url)
                        <a href="{{ $news->url }}" target="_blank"
                           style="color:var(--text-primary); text-decoration:none;">
                            {{ Str::limit($news->title, 90) }}
                            <i class="fas fa-external-link-alt" style="font-size:10px; color:var(--text-secondary); margin-left:4px"></i>
                        </a>
                    @else
                        {{ Str::limit($news->title, 90) }}
                    @endif
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <small style="color:var(--text-secondary)">
                        {{ $news->source ?? '-' }} ·
                        {{ $news->published_at?->diffForHumans() ?? '-' }}
                    </small>
                    @if($news->sentiment === 'Positive')
                        <span class="badge-low" style="font-size:10px">Positive</span>
                    @elseif($news->sentiment === 'Negative')
                        <span class="badge-high" style="font-size:10px">Negative</span>
                    @else
                        <span class="badge-medium" style="font-size:10px">Neutral</span>
                    @endif
                </div>
            </div>
            @empty
            <div style="color:var(--text-secondary); font-size:13px; padding:30px 0; text-align:center;">
                <i class="fas fa-newspaper" style="font-size:28px; opacity:0.3"></i>
                <p style="margin-top:8px">Belum ada berita untuk negara ini.</p>
            </div>
            @endforelse

            <div class="mt-3 text-center">
                <a href="{{ route('countries.show', $country->code) }}"
                   style="color:var(--accent-light); font-size:13px; text-decoration:none;">
                    <i class="fas fa-globe"></i> Lihat Profil Lengkap {{ $country->name }}
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
@if($riskScore)
// ── Radar Chart ────────────────────────────────────────────────
new Chart(document.getElementById('radarChart'), {
    type: 'radar',
    data: {
        labels: ['Cuaca', 'Inflasi', 'Berita', 'Kurs'],
        datasets: [{
            label: '{{ $country->name }}',
            data: [
                {{ $riskScore->weather_risk }},
                {{ $riskScore->inflation_risk }},
                {{ $riskScore->news_risk }},
                {{ $riskScore->currency_risk }},
            ],
            backgroundColor: 'rgba(58,138,82,0.25)',
            borderColor: '#3a8a52',
            pointBackgroundColor: '#3a8a52',
            pointRadius: 5,
            borderWidth: 2,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { labels: { color: '#718096', font: { size: 12 } } }
        },
        scales: {
            r: {
                min: 0, max: 100,
                ticks: { display: false },
                grid:  { color: 'rgba(91,110,245,0.15)' },
                angleLines: { color: 'rgba(91,110,245,0.15)' },
                pointLabels: { color: '#718096', font: { size: 12 } },
            }
        }
    }
});
@endif

@if($riskHistory->count() >= 2)
// ── Trend Line Chart ───────────────────────────────────────────
const trendLabels = @json($riskHistory->pluck('recorded_date')->map(fn($d) => $d->format('d M'))->values());
const trendTotal  = @json($riskHistory->pluck('total_risk')->map(fn($v) => (float)$v)->values());
const trendWeather= @json($riskHistory->pluck('weather_risk')->map(fn($v) => (float)$v)->values());
const trendNews   = @json($riskHistory->pluck('news_risk')->map(fn($v) => (float)$v)->values());

const trendCtx = document.getElementById('trendChart').getContext('2d');
const grad = trendCtx.createLinearGradient(0, 0, 0, 260);
grad.addColorStop(0, 'rgba(58,138,82,0.35)');
grad.addColorStop(1, 'rgba(58,138,82,0.02)');

new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: trendLabels,
        datasets: [
            {
                label: 'Total Risk',
                data: trendTotal,
                borderColor: '#3a8a52',
                backgroundColor: grad,
                borderWidth: 2.5,
                pointRadius: trendLabels.length > 15 ? 2 : 4,
                tension: 0.3,
                fill: true,
            },
            {
                label: 'Cuaca',
                data: trendWeather,
                borderColor: '#64b5f6',
                backgroundColor: 'transparent',
                borderWidth: 1.5,
                borderDash: [4, 4],
                pointRadius: 0,
                tension: 0.3,
            },
            {
                label: 'Berita',
                data: trendNews,
                borderColor: '#ef9a9a',
                backgroundColor: 'transparent',
                borderWidth: 1.5,
                borderDash: [4, 4],
                pointRadius: 0,
                tension: 0.3,
            },
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { labels: { color: '#718096', font: { size: 11 } } },
            tooltip: {
                backgroundColor: '#ffffff',
                titleColor: '#2d3748',
                bodyColor: '#718096',
                borderColor: '#e2e8f0',
                borderWidth: 1,
            }
        },
        scales: {
            x: {
                ticks: { color: '#718096', font: { size: 10 } },
                grid:  { color: 'var(--border-color)' }
            },
            y: {
                min: 0, max: 100,
                ticks: { color: '#718096', font: { size: 11 } },
                grid:  { color: 'var(--border-color)' }
            }
        }
    }
});
@endif

@if(($positiveNews + $negativeNews + $neutralNews) > 0)
// ── Pie Chart Sentimen ─────────────────────────────────────────
new Chart(document.getElementById('sentimentChart'), {
    type: 'doughnut',
    data: {
        labels: ['Positif', 'Netral', 'Negatif'],
        datasets: [{
            data: [{{ $positiveNews }}, {{ $neutralNews }}, {{ $negativeNews }}],
            backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
            borderColor: '#ffffff',
            borderWidth: 3,
            hoverOffset: 6,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '60%',
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#ffffff',
                titleColor: '#2d3748',
                bodyColor: '#718096',
                borderColor: '#e2e8f0',
                borderWidth: 1,
                callbacks: {
                    label: ctx => ` ${ctx.label}: ${ctx.raw} berita`
                }
            }
        }
    }
});
@endif
</script>
@endpush
