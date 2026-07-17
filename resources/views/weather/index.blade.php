@extends('layouts.app')

@section('title', 'Global Weather Monitor')
@section('page-title', 'Global Weather Monitor')

@push('styles')
<style>
    #weatherMap {
        height: 500px;
        border-radius: 10px;
        border: 1px solid var(--border-color);
        z-index: 1;
    }
    .stat-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 16px 20px;
        margin-bottom: 16px;
        text-align: center;
    }
    .stat-card .label {
        font-size: 11px;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 4px;
    }
    .stat-card .value {
        font-size: 26px;
        font-weight: 700;
    }
    .legend-box {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 14px 16px;
        margin-bottom: 14px;
    }
    .legend-box label {
        font-size: 11px;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 1px;
        display: block;
        margin-bottom: 8px;
    }
    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        color: var(--text-secondary);
        margin-bottom: 6px;
    }
    .legend-dot {
        width: 12px; height: 12px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .chart-box {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 18px;
        margin-bottom: 16px;
    }
    .chart-box h6 {
        color: var(--text-primary);
        font-size: 14px;
        margin-bottom: 14px;
    }
    .chart-container { position: relative; height: 180px; }
    .weather-table-row:hover td {
        background: #f8fafc !important;
    }
    .flag-img {
        width: 22px; height: 14px;
        object-fit: cover; border-radius: 2px;
        margin-right: 6px;
    }
    .filter-btn {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        color: var(--text-secondary);
        padding: 5px 12px;
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

    /* Leaflet popup dark theme */
    .leaflet-popup-content-wrapper {
        background: #1a3a2a !important;
        border: 1px solid #28623A !important;
        color: #fff !important;
        border-radius: 8px !important;
        box-shadow: 0 4px 20px rgba(0,0,0,0.5) !important;
    }
    .leaflet-popup-tip { background: #1a3a2a !important; }
    .leaflet-popup-content { margin: 10px 14px !important; font-size: 13px !important; }
    .popup-title { font-weight: 700; color: #3a8a52; margin-bottom: 6px; font-size: 14px; }
    .popup-row { color: #a8c5b5; font-size: 12px; margin-bottom: 3px; }
    .popup-row span { color: #fff; font-weight: 600; }
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h2>🌦 Global Weather Monitor</h2>
        <p>Pantau kondisi cuaca ekstrem yang dapat mengganggu rantai pasok global</p>
    </div>
    <div style="font-size:12px; color:var(--text-secondary); text-align:right">
        <i class="fas fa-satellite-dish" style="color:var(--accent-light)"></i>
        Data dari <strong style="color:var(--accent-light)">Open-Meteo API</strong>
    </div>
</div>

{{-- Stat Cards --}}
<div class="row mb-3">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="label">Dipantau</div>
            <div class="value" style="color:var(--accent-light)">{{ $totalMonitored }}</div>
            <small style="color:var(--text-secondary)">negara</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="label">Risiko Tinggi</div>
            <div class="value" style="color:#dc3545">{{ $highRisk }}</div>
            <small style="color:var(--text-secondary)">negara</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="label">Risiko Sedang</div>
            <div class="value" style="color:#ffc107">{{ $mediumRisk }}</div>
            <small style="color:var(--text-secondary)">negara</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="label">⚡ Badai Aktif</div>
            <div class="value" style="color:#ff6b35">{{ $stormCount }}</div>
            <small style="color:var(--text-secondary)">negara</small>
        </div>
    </div>
</div>

<div class="row">
    {{-- Sidebar kiri --}}
    <div class="col-md-3">

        {{-- Legenda warna --}}
        <div class="legend-box">
            <label>🎨 Legenda Risiko</label>
            <div class="legend-item">
                <div class="legend-dot" style="background:#dc3545; box-shadow:0 0 8px rgba(220,53,69,0.6)"></div>
                <span>High Risk — cuaca ekstrem</span>
            </div>
            <div class="legend-item">
                <div class="legend-dot" style="background:#ffc107; box-shadow:0 0 8px rgba(255,193,7,0.6)"></div>
                <span>Medium Risk — perlu dipantau</span>
            </div>
            <div class="legend-item">
                <div class="legend-dot" style="background:#28a745; box-shadow:0 0 8px rgba(40,167,69,0.6)"></div>
                <span>Low Risk — kondisi normal</span>
            </div>
            <div class="legend-item">
                <div class="legend-dot" style="background:#6c757d"></div>
                <span>Tidak ada data</span>
            </div>
        </div>

        {{-- Filter level --}}
        <div class="legend-box">
            <label>🔽 Filter Level</label>
            <div class="d-flex flex-column gap-2">
                <button class="filter-btn active" onclick="filterWeather('all', this)">Semua</button>
                <button class="filter-btn" onclick="filterWeather('High', this)">🔴 High Risk</button>
                <button class="filter-btn" onclick="filterWeather('Medium', this)">🟡 Medium Risk</button>
                <button class="filter-btn" onclick="filterWeather('Low', this)">🟢 Low Risk</button>
                <button class="filter-btn" onclick="filterWeather('storm', this)">⚡ Badai Aktif</button>
            </div>
        </div>

        {{-- Chart distribusi kondisi --}}
        <div class="chart-box">
            <h6>☁ Distribusi Kondisi</h6>
            <div class="chart-container">
                <canvas id="conditionChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Peta --}}
    <div class="col-md-9">
        <div id="weatherMap"></div>
        <div style="font-size:11px; color:var(--text-secondary); margin-top:8px; text-align:right;">
            <i class="fas fa-info-circle"></i>
            Klik marker untuk detail cuaca negara · Warna marker = level risiko
        </div>
    </div>
</div>

{{-- Tabel Cuaca Ekstrem --}}
<div class="chart-box mt-4">
    <h6>⛈ Negara dengan Cuaca Ekstrem (High Risk)</h6>
    @if($extremeWeather->isEmpty())
        <p style="color:var(--text-secondary); font-size:13px; text-align:center; padding:20px 0">
            <i class="fas fa-check-circle" style="color:#28a745"></i>
            Tidak ada cuaca ekstrem saat ini. Kondisi global relatif aman.
        </p>
    @else
    <div style="overflow-x:auto">
        <table class="table table-custom" style="font-size:13px;">
            <thead>
                <tr>
                    <th>Negara</th>
                    <th style="text-align:center">Kondisi</th>
                    <th style="text-align:center">Suhu</th>
                    <th style="text-align:center">Curah Hujan</th>
                    <th style="text-align:center">Angin</th>
                    <th style="text-align:center">Badai</th>
                    <th style="text-align:center">Level</th>
                </tr>
            </thead>
            <tbody>
                @foreach($extremeWeather as $w)
                <tr class="weather-table-row">
                    <td>
                        @if($w->country?->flag_url)
                            <img class="flag-img" src="{{ $w->country->flag_url }}" alt="">
                        @endif
                        <a href="{{ route('countries.show', $w->country?->code) }}"
                           style="color:var(--accent-light); text-decoration:none;">
                            {{ $w->country?->name ?? '-' }}
                        </a>
                    </td>
                    <td style="text-align:center">{{ $w->weather_condition ?? '-' }}</td>
                    <td style="text-align:center">
                        {{ $w->temperature ? $w->temperature . '°C' : '-' }}
                    </td>
                    <td style="text-align:center">
                        @if($w->rainfall)
                            <span style="color:{{ $w->rainfall > 50 ? '#dc3545' : ($w->rainfall > 20 ? '#ffc107' : 'inherit') }}">
                                {{ $w->rainfall }} mm
                            </span>
                        @else -
                        @endif
                    </td>
                    <td style="text-align:center">
                        @if($w->wind_speed)
                            <span style="color:{{ $w->wind_speed > 60 ? '#dc3545' : ($w->wind_speed > 30 ? '#ffc107' : 'inherit') }}">
                                {{ $w->wind_speed }} km/h
                            </span>
                        @else -
                        @endif
                    </td>
                    <td style="text-align:center">
                        @if($w->storm_risk)
                            <span style="color:#dc3545; font-weight:700">⚡ Ya</span>
                        @else
                            <span style="color:#28a745">✓ Tidak</span>
                        @endif
                    </td>
                    <td style="text-align:center">
                        <span class="badge-high">High</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Data dari Laravel ──────────────────────────────────────────
const WEATHER_DATA = @json($weatherData->values());

// ── Warna per risk level ───────────────────────────────────────
const RISK_COLORS = {
    'High':    { fill: '#dc3545', glow: 'rgba(220,53,69,0.7)',   size: 14 },
    'Medium':  { fill: '#ffc107', glow: 'rgba(255,193,7,0.6)',   size: 11 },
    'Low':     { fill: '#28a745', glow: 'rgba(40,167,69,0.5)',   size: 9  },
    'Unknown': { fill: '#6c757d', glow: 'rgba(108,117,125,0.4)', size: 8  },
};

// ── Inisialisasi peta ──────────────────────────────────────────
const map = L.map('weatherMap', {
    center: [20, 0],
    zoom: 2,
    zoomControl: true,
    preferCanvas: true,
});

L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/">OpenStreetMap</a> · © <a href="https://carto.com/">CARTO</a>',
    maxZoom: 18,
}).addTo(map);

// ── Layer untuk markers ────────────────────────────────────────
let markerLayer = L.layerGroup().addTo(map);

function makeWeatherIcon(level, isStorm) {
    const c    = RISK_COLORS[level] || RISK_COLORS['Unknown'];
    const size = isStorm ? c.size + 4 : c.size;
    return L.divIcon({
        className: '',
        html: `<div style="
            width:${size}px; height:${size}px;
            background:${c.fill};
            border:2px solid rgba(255,255,255,0.3);
            border-radius:50%;
            box-shadow:0 0 ${isStorm ? 12 : 6}px ${c.glow};
            ${isStorm ? 'animation:pulse 1.5s infinite;' : ''}
        ">${isStorm ? '<span style="position:absolute;top:-10px;left:50%;transform:translateX(-50%);font-size:10px">⚡</span>' : ''}</div>`,
        iconSize: [size, size],
        iconAnchor: [size/2, size/2],
    });
}

function buildWeatherMarkers(data) {
    markerLayer.clearLayers();

    data.forEach(w => {
        if (!w.lat || !w.lng) return;

        const level  = w.risk_level || 'Unknown';
        const icon   = makeWeatherIcon(level, w.storm_risk);
        const marker = L.marker([w.lat, w.lng], { icon });

        const conditionEmoji = {
            'Stormy': '⛈', 'Rainy': '🌧', 'Cloudy': '☁',
            'Clear': '☀', 'Foggy': '🌫', 'Drizzle': '🌦',
            'Snowy': '❄', 'Shower': '🌦', 'Unknown': '❓',
        };
        const emoji = conditionEmoji[w.weather_condition] || '🌡';

        const flagHtml = w.flag_url
            ? `<img src="${w.flag_url}" style="width:18px;height:11px;object-fit:cover;border-radius:2px;margin-right:5px;">`
            : '';

        const riskColor = level === 'High' ? '#dc3545' : level === 'Medium' ? '#ffc107' : '#28a745';

        marker.bindPopup(`
            <div class="popup-title">${flagHtml}${w.country_name}</div>
            <div style="font-size:18px; margin-bottom:6px">${emoji} ${w.weather_condition ?? '-'}</div>
            <div class="popup-row">🌡 Suhu: <span>${w.temperature !== null ? w.temperature + '°C' : '-'}</span></div>
            <div class="popup-row">🌧 Curah hujan: <span>${w.rainfall !== null ? w.rainfall + ' mm' : '-'}</span></div>
            <div class="popup-row">💨 Angin: <span>${w.wind_speed !== null ? w.wind_speed + ' km/h' : '-'}</span></div>
            <div class="popup-row">⚡ Badai: <span style="color:${w.storm_risk ? '#dc3545' : '#28a745'}">${w.storm_risk ? 'YA' : 'Tidak'}</span></div>
            <div style="margin-top:8px">
                <span style="
                    background:${riskColor}22; color:${riskColor};
                    padding:2px 10px; border-radius:10px; font-size:12px; font-weight:700;
                    border:1px solid ${riskColor}44;
                ">${level} Risk</span>
            </div>
        `, { maxWidth: 240 });

        markerLayer.addLayer(marker);
    });
}

buildWeatherMarkers(WEATHER_DATA);

// ── Filter ─────────────────────────────────────────────────────
function filterWeather(level, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    let filtered;
    if (level === 'all') {
        filtered = WEATHER_DATA;
    } else if (level === 'storm') {
        filtered = WEATHER_DATA.filter(w => w.storm_risk);
    } else {
        filtered = WEATHER_DATA.filter(w => w.risk_level === level);
    }

    buildWeatherMarkers(filtered);

    // Zoom ke area jika ada data
    if (filtered.length > 0 && level !== 'all') {
        const lats = filtered.filter(w => w.lat).map(w => w.lat);
        const lngs = filtered.filter(w => w.lng).map(w => w.lng);
        if (lats.length > 0) {
            map.fitBounds([
                [Math.min(...lats) - 5, Math.min(...lngs) - 5],
                [Math.max(...lats) + 5, Math.max(...lngs) + 5],
            ]);
        }
    } else {
        map.setView([20, 0], 2);
    }
}

// ── Chart distribusi kondisi ───────────────────────────────────
const condLabels = @json($conditionStats->pluck('weather_condition')->values());
const condCounts = @json($conditionStats->pluck('count')->values());

const condColors = condLabels.map(c => ({
    'Stormy':'#dc3545','Rainy':'#6baed6','Cloudy':'#74c476',
    'Clear':'#ffd54f','Foggy':'#b0b0b0','Drizzle':'#9ecae1',
    'Snowy':'#c6dbef','Shower':'#41b3d9','Unknown':'#6c757d',
}[c] || '#3a8a52'));

new Chart(document.getElementById('conditionChart'), {
    type: 'bar',
    data: {
        labels: condLabels,
        datasets: [{
            label: 'Jumlah Negara',
            data: condCounts,
            backgroundColor: condColors.map(c => c + 'cc'),
            borderColor: condColors,
            borderWidth: 1,
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#ffffff',
                titleColor: '#2d3748',
                bodyColor: '#718096',
                borderColor: '#e2e8f0',
                borderWidth: 1,
                callbacks: {
                    label: ctx => ` ${ctx.raw} negara`
                }
            }
        },
        scales: {
            x: {
                ticks: { color: '#718096', font: { size: 10 } },
                grid:  { color: 'var(--border-color)' }
            },
            y: {
                ticks: { color: '#718096', font: { size: 10 } },
                grid:  { display: false }
            }
        }
    }
});

// Animasi pulse untuk badai
const style = document.createElement('style');
style.textContent = `
@keyframes pulse {
    0%   { box-shadow: 0 0 6px rgba(220,53,69,0.7); }
    50%  { box-shadow: 0 0 18px rgba(220,53,69,1); }
    100% { box-shadow: 0 0 6px rgba(220,53,69,0.7); }
}`;
document.head.appendChild(style);
</script>
@endpush
