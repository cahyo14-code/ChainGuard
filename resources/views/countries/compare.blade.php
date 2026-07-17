@extends('layouts.app')

@section('title', 'Country Comparison Engine')
@section('page-title', 'Country Comparison Engine')

@push('styles')
<style>
:root { --chart-a: #3a8a52; --chart-b: #4a90d9; --chart-c: #f0a500; --chart-d: #e05c5c; }
.compare-select-card {
    background: var(--bg-card); border: 1px solid var(--border-color);
    border-radius: 12px; padding: 20px; margin-bottom: 20px;
}
.compare-select-card label { font-size:11px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:6px; }
.form-select-custom {
    background: var(--bg-secondary); border: 1px solid var(--border-color);
    color: var(--text-primary); border-radius: 8px; padding: 10px 12px;
    font-size: 13px; width: 100%;
}
.form-select-custom:focus { outline:none; border-color:var(--accent-light); }
.form-select-custom option { background: var(--bg-secondary); }
.flag-header { width:36px; height:22px; object-fit:cover; border-radius:4px; margin-right:10px; }
.compare-card {
    background: var(--bg-card); border: 1px solid var(--border-color);
    border-radius: 10px; padding: 18px; margin-bottom: 16px;
}
.compare-card h6 { color:var(--text-primary); font-size:13px; margin-bottom:14px; }
.chart-container { position:relative; height:220px; }
.chart-container-sm { position:relative; height:180px; }
.vs-divider {
    display:flex; align-items:center; justify-content:center;
    font-size:11px; font-weight:700; color:var(--text-secondary);
    letter-spacing:2px; padding:0 8px;
}
.metric-row {
    display:flex; justify-content:space-between; align-items:center;
    padding:9px 0; border-bottom:1px solid rgba(40,98,58,0.12); font-size:13px;
}
.metric-row:last-child { border-bottom:none; }
.metric-label { color:var(--text-secondary); font-size:12px; }
.metric-val { font-weight:700; font-size:14px; }
.winner { color:#3a8a52; }
.loser  { color:#dc3545; }
.neutral { color:var(--text-secondary); }
.btn-compare {
    background: var(--accent); border: none; color: white;
    padding: 10px 28px; border-radius: 8px; font-size: 14px;
    font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.btn-compare:hover { background: var(--accent-light); }
.btn-compare:disabled { opacity:0.5; cursor:not-allowed; }
.placeholder-box {
    text-align:center; padding:60px 20px;
    color:var(--text-secondary); font-size:13px;
}
.placeholder-box i { font-size:40px; opacity:0.2; margin-bottom:12px; display:block; }
.country-header-card {
    background: linear-gradient(135deg, var(--bg-card) 0%, rgba(40,98,58,0.12) 100%);
    border: 2px solid var(--border-color); border-radius: 12px; padding: 18px;
    text-align:center;
}
.country-header-card.team-a { border-color: rgba(58,138,82,0.5); }
.country-header-card.team-b { border-color: rgba(74,144,217,0.5); }
.risk-score-display {
    font-size:42px; font-weight:800; line-height:1;
}
.loading-overlay {
    text-align:center; padding:40px;
    color:var(--text-secondary);
}
</style>
@endpush

@section('content')

<div class="page-header">
    <h2>⚖️ Country Comparison Engine</h2>
    <p>Bandingkan indikator risiko rantai pasok antara dua negara secara side-by-side</p>
</div>

{{-- Selector --}}
<div class="compare-select-card">
    <div class="row align-items-end">
        <div class="col-md-5">
            <label>🌍 Negara A</label>
            <select id="selectA" class="form-select-custom" onchange="updateUrl()">
                <option value="">— Pilih Negara Pertama —</option>
                @foreach($countries as $c)
                <option value="{{ $c->code }}" {{ $codeA === $c->code ? 'selected' : '' }}>
                    {{ $c->name }} ({{ $c->currency_code ?? $c->code }})
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 text-center py-2">
            <div style="font-size:24px; color:var(--text-secondary); font-weight:700">VS</div>
        </div>
        <div class="col-md-5">
            <label>🌍 Negara B</label>
            <select id="selectB" class="form-select-custom" onchange="updateUrl()">
                <option value="">— Pilih Negara Kedua —</option>
                @foreach($countries as $c)
                <option value="{{ $c->code }}" {{ $codeB === $c->code ? 'selected' : '' }}>
                    {{ $c->name }} ({{ $c->currency_code ?? $c->code }})
                </option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-12 text-center">
            <button id="compareBtn" class="btn-compare" onclick="doCompare()" disabled>
                <i class="fas fa-balance-scale"></i> Bandingkan Sekarang
            </button>
        </div>
    </div>
</div>

{{-- Hasil perbandingan --}}
<div id="compareResult">
    <div class="placeholder-box">
        <i class="fas fa-balance-scale"></i>
        Pilih dua negara lalu klik <strong>Bandingkan</strong> untuk melihat perbandingan lengkap
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
let charts  = {};

// ── Aktifkan tombol saat keduanya dipilih ──────────────────────
function updateUrl() {
    const a = document.getElementById('selectA').value;
    const b = document.getElementById('selectB').value;
    document.getElementById('compareBtn').disabled = !(a && b && a !== b);
}

// ── Auto-compare jika sudah ada query string ───────────────────
document.addEventListener('DOMContentLoaded', () => {
    updateUrl();
    const a = document.getElementById('selectA').value;
    const b = document.getElementById('selectB').value;
    if (a && b && a !== b) doCompare();
});

// ── Ambil data & render ────────────────────────────────────────
async function doCompare() {
    const codeA = document.getElementById('selectA').value;
    const codeB = document.getElementById('selectB').value;
    if (!codeA || !codeB) return;

    document.getElementById('compareResult').innerHTML = `
        <div class="loading-overlay">
            <i class="fas fa-spinner fa-spin" style="font-size:32px; color:var(--accent-light)"></i>
            <p style="margin-top:12px">Memuat data perbandingan...</p>
        </div>`;

    // Destroy charts lama
    Object.values(charts).forEach(c => c?.destroy());
    charts = {};

    try {
        const res  = await fetch(`/api/compare?codes=${codeA},${codeB}`, {
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
        });
        const json = await res.json();
        if (json.status !== 'success' || json.data.length < 2) {
            throw new Error(json.message || 'Data tidak lengkap');
        }
        renderCompare(json.data[0], json.data[1]);
    } catch(e) {
        document.getElementById('compareResult').innerHTML = `
            <div class="placeholder-box" style="color:#dc3545">
                <i class="fas fa-exclamation-circle"></i>
                Gagal memuat data: ${e.message}
            </div>`;
    }
}

// ── Render HTML hasil perbandingan ─────────────────────────────
function renderCompare(a, b) {
    const html = `
    <div class="row mb-3">
        <div class="col-md-5">
            <div class="country-header-card team-a">
                ${a.flag_url ? `<img src="${a.flag_url}" style="width:50px;height:32px;object-fit:cover;border-radius:6px;margin-bottom:10px;">` : ''}
                <h4 style="color:var(--text-primary);margin:0">${a.name}</h4>
                <small style="color:var(--text-secondary)">${a.capital ?? ''} · ${a.region ?? ''}</small>
                <div class="risk-score-display mt-2" style="color:${riskColor(a.risk.level)}">${a.risk.total}</div>
                <span style="color:${riskColor(a.risk.level)};font-size:13px;font-weight:600">${a.risk.level} Risk</span>
            </div>
        </div>
        <div class="vs-divider col-md-2">VS</div>
        <div class="col-md-5">
            <div class="country-header-card team-b">
                ${b.flag_url ? `<img src="${b.flag_url}" style="width:50px;height:32px;object-fit:cover;border-radius:6px;margin-bottom:10px;">` : ''}
                <h4 style="color:var(--text-primary);margin:0">${b.name}</h4>
                <small style="color:var(--text-secondary)">${b.capital ?? ''} · ${b.region ?? ''}</small>
                <div class="risk-score-display mt-2" style="color:${riskColor(b.risk.level)}">${b.risk.total}</div>
                <span style="color:${riskColor(b.risk.level)};font-size:13px;font-weight:600">${b.risk.level} Risk</span>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Radar chart risk komponen -->
        <div class="col-md-5">
            <div class="compare-card">
                <h6>🎯 Profil Risiko</h6>
                <div class="chart-container"><canvas id="radarChart"></canvas></div>
            </div>
        </div>

        <!-- Metrik side-by-side -->
        <div class="col-md-7">
            <div class="compare-card">
                <h6>📊 Perbandingan Indikator</h6>
                ${metricTable(a, b)}
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Bar chart GDP & Inflasi -->
        <div class="col-md-6">
            <div class="compare-card">
                <h6>💰 Ekonomi (GDP Miliar USD & Inflasi %)</h6>
                <div class="chart-container-sm"><canvas id="econChart"></canvas></div>
            </div>
        </div>

        <!-- Bar chart risk breakdown -->
        <div class="col-md-6">
            <div class="compare-card">
                <h6>⚠️ Breakdown Komponen Risiko</h6>
                <div class="chart-container-sm"><canvas id="riskBarChart"></canvas></div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Trend risiko 14 hari -->
        <div class="col-md-6">
            <div class="compare-card">
                <h6>📈 Trend Risk Score — 14 Hari</h6>
                <div class="chart-container-sm"><canvas id="trendChart"></canvas></div>
            </div>
        </div>

        <!-- Trend kurs 14 hari -->
        <div class="col-md-6">
            <div class="compare-card">
                <h6>💱 Trend Kurs vs USD — 14 Hari</h6>
                <div class="chart-container-sm"><canvas id="currencyTrendChart"></canvas></div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Sentimen berita -->
        <div class="col-md-6">
            <div class="compare-card">
                <h6>🗞 Sentimen Berita</h6>
                <div class="chart-container-sm"><canvas id="newsChart"></canvas></div>
            </div>
        </div>

        <!-- Cuaca -->
        <div class="col-md-6">
            <div class="compare-card">
                <h6>🌦 Kondisi Cuaca Saat Ini</h6>
                ${weatherCard(a, b)}
            </div>
        </div>
    </div>`;

    document.getElementById('compareResult').innerHTML = html;

    // render semua charts setelah DOM tersedia
    setTimeout(() => {
        renderRadar(a, b);
        renderEcon(a, b);
        renderRiskBar(a, b);
        renderTrend(a, b);
        renderCurrencyTrend(a, b);
        renderNews(a, b);
    }, 50);
}

// ── Helper warna ───────────────────────────────────────────────
function riskColor(level) {
    return level === 'High' ? '#dc3545' : level === 'Medium' ? '#ffc107' : '#28a745';
}

// ── Tabel metrik ───────────────────────────────────────────────
function metricTable(a, b) {
    const rows = [
        { label: 'Total Risk Score',    va: a.risk.total,         vb: b.risk.total,         lower: true,  fmt: v => v },
        { label: 'GDP (Miliar USD)',     va: a.economy.gdp,        vb: b.economy.gdp,        lower: false, fmt: v => v ? '$' + v + 'B' : '-' },
        { label: 'Inflasi (%)',          va: a.economy.inflation,  vb: b.economy.inflation,  lower: true,  fmt: v => v !== null ? v + '%' : '-' },
        { label: 'Ekspor (Miliar USD)', va: a.economy.exports,    vb: b.economy.exports,    lower: false, fmt: v => v ? '$' + v + 'B' : '-' },
        { label: 'Weather Risk',         va: a.risk.weather,       vb: b.risk.weather,       lower: true,  fmt: v => v },
        { label: 'News Risk',            va: a.risk.news,          vb: b.risk.news,          lower: true,  fmt: v => v },
        { label: 'Kurs (1 USD)',         va: a.currency.rate,      vb: b.currency.rate,      lower: true,  fmt: (v, d) => v ? v + ' ' + d.currency.code : '-' },
        { label: 'Total Berita',         va: a.news.total,         vb: b.news.total,         lower: false, fmt: v => v },
    ];

    return rows.map(r => {
        let classA = 'neutral', classB = 'neutral';
        if (r.va !== null && r.vb !== null && r.va !== r.vb) {
            const aWins = r.lower ? r.va < r.vb : r.va > r.vb;
            classA = aWins ? 'winner' : 'loser';
            classB = aWins ? 'loser'  : 'winner';
        }
        return `<div class="metric-row">
            <span class="metric-val ${classA}">${r.fmt(r.va, a)}</span>
            <span class="metric-label">${r.label}</span>
            <span class="metric-val ${classB}">${r.fmt(r.vb, b)}</span>
        </div>`;
    }).join('');
}

// ── Cuaca card ─────────────────────────────────────────────────
function weatherCard(a, b) {
    const emoji = { 'Stormy':'⛈','Rainy':'🌧','Cloudy':'☁','Clear':'☀','Foggy':'🌫','Drizzle':'🌦','Snowy':'❄','Unknown':'🌡' };
    function row(w, name, flag) {
        return `<div style="padding:10px;background:var(--bg-secondary);border-radius:8px;margin-bottom:8px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                ${flag ? `<img src="${flag}" style="width:22px;height:14px;object-fit:cover;border-radius:2px;">` : ''}
                <strong style="font-size:13px">${name}</strong>
                <span style="font-size:18px">${emoji[w.condition] || '🌡'}</span>
                <span style="color:var(--text-secondary);font-size:12px">${w.condition}</span>
            </div>
            <div style="display:flex;gap:16px;font-size:12px;color:var(--text-secondary);">
                <span>🌡 ${w.temperature !== null ? w.temperature + '°C' : '-'}</span>
                <span>🌧 ${w.rainfall !== null ? w.rainfall + 'mm' : '-'}</span>
                <span>💨 ${w.wind_speed !== null ? w.wind_speed + ' km/h' : '-'}</span>
                <span style="color:${w.storm_risk ? '#dc3545' : '#28a745'}">${w.storm_risk ? '⚡ Badai' : '✓ Aman'}</span>
            </div>
        </div>`;
    }
    return row(a.weather, a.name, a.flag_url) + row(b.weather, b.name, b.flag_url);
}

// ── Chart Radar ────────────────────────────────────────────────
function renderRadar(a, b) {
    charts.radar = new Chart(document.getElementById('radarChart'), {
        type: 'radar',
        data: {
            labels: ['Cuaca', 'Inflasi', 'Berita', 'Kurs'],
            datasets: [
                {
                    label: a.name,
                    data: [a.risk.weather, a.risk.inflation, a.risk.news, a.risk.currency],
                    backgroundColor: 'rgba(58,138,82,0.2)', borderColor: '#3a8a52',
                    pointBackgroundColor: '#3a8a52', borderWidth: 2, pointRadius: 4,
                },
                {
                    label: b.name,
                    data: [b.risk.weather, b.risk.inflation, b.risk.news, b.risk.currency],
                    backgroundColor: 'rgba(74,144,217,0.2)', borderColor: '#4a90d9',
                    pointBackgroundColor: '#4a90d9', borderWidth: 2, pointRadius: 4,
                },
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { labels: { color: '#a8c5b5', font: { size: 11 } } } },
            scales: {
                r: {
                    min: 0, max: 100,
                    ticks: { display: false },
                    grid:  { color: 'rgba(40,98,58,0.25)' },
                    angleLines: { color: 'rgba(40,98,58,0.25)' },
                    pointLabels: { color: '#a8c5b5', font: { size: 11 } },
                }
            }
        }
    });
}

// ── Chart Ekonomi ──────────────────────────────────────────────
function renderEcon(a, b) {
    charts.econ = new Chart(document.getElementById('econChart'), {
        type: 'bar',
        data: {
            labels: ['GDP (Miliar USD)', 'Inflasi (%)'],
            datasets: [
                {
                    label: a.name,
                    data: [a.economy.gdp ?? 0, a.economy.inflation ?? 0],
                    backgroundColor: 'rgba(58,138,82,0.75)', borderRadius: 4,
                },
                {
                    label: b.name,
                    data: [b.economy.gdp ?? 0, b.economy.inflation ?? 0],
                    backgroundColor: 'rgba(74,144,217,0.75)', borderRadius: 4,
                },
            ]
        },
        options: chartOpts()
    });
}

// ── Chart Risk Breakdown ───────────────────────────────────────
function renderRiskBar(a, b) {
    charts.riskBar = new Chart(document.getElementById('riskBarChart'), {
        type: 'bar',
        data: {
            labels: ['Cuaca', 'Inflasi', 'Berita', 'Kurs'],
            datasets: [
                {
                    label: a.name,
                    data: [a.risk.weather, a.risk.inflation, a.risk.news, a.risk.currency],
                    backgroundColor: 'rgba(58,138,82,0.75)', borderRadius: 4,
                },
                {
                    label: b.name,
                    data: [b.risk.weather, b.risk.inflation, b.risk.news, b.risk.currency],
                    backgroundColor: 'rgba(74,144,217,0.75)', borderRadius: 4,
                },
            ]
        },
        options: { ...chartOpts(), scales: { ...chartOpts().scales, y: { ...chartOpts().scales.y, max: 100 } } }
    });
}

// ── Chart Trend Risk ───────────────────────────────────────────
function renderTrend(a, b) {
    const labelsA = a.risk_trend.labels;
    const labelsB = b.risk_trend.labels;
    const labels  = labelsA.length >= labelsB.length ? labelsA : labelsB;

    charts.trend = new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: a.name, data: a.risk_trend.values,
                    borderColor: '#3a8a52', backgroundColor: 'transparent',
                    borderWidth: 2, pointRadius: 2, tension: 0.3,
                },
                {
                    label: b.name, data: b.risk_trend.values,
                    borderColor: '#4a90d9', backgroundColor: 'transparent',
                    borderWidth: 2, pointRadius: 2, tension: 0.3,
                },
            ]
        },
        options: { ...chartOpts(), scales: { ...chartOpts().scales, y: { ...chartOpts().scales.y, min: 0, max: 100 } } }
    });
}

// ── Chart Trend Kurs ───────────────────────────────────────────
function renderCurrencyTrend(a, b) {
    const labelsA = a.currency_trend.labels;
    const labelsB = b.currency_trend.labels;
    const labels  = labelsA.length >= labelsB.length ? labelsA : labelsB;

    if (!labels.length) {
        document.getElementById('currencyTrendChart').closest('.compare-card').innerHTML =
            '<h6>💱 Trend Kurs vs USD — 14 Hari</h6><p style="color:var(--text-secondary);font-size:12px;text-align:center;padding:40px 0">Data historis belum tersedia</p>';
        return;
    }

    charts.currencyTrend = new Chart(document.getElementById('currencyTrendChart'), {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: `${a.name} (${a.currency.code})`, data: a.currency_trend.values,
                    borderColor: '#3a8a52', backgroundColor: 'transparent',
                    borderWidth: 2, pointRadius: 2, tension: 0.3, yAxisID: 'yA',
                },
                {
                    label: `${b.name} (${b.currency.code})`, data: b.currency_trend.values,
                    borderColor: '#4a90d9', backgroundColor: 'transparent',
                    borderWidth: 2, pointRadius: 2, tension: 0.3, yAxisID: 'yB',
                },
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { labels: { color: '#a8c5b5', font: { size: 10 } } }, tooltip: tooltipStyle() },
            scales: {
                x:  { ticks: { color: '#a8c5b5', font: { size: 10 } }, grid: { color: 'rgba(40,98,58,0.12)' } },
                yA: { position: 'left',  ticks: { color: '#3a8a52', font: { size: 10 } }, grid: { color: 'rgba(40,98,58,0.12)' } },
                yB: { position: 'right', ticks: { color: '#4a90d9', font: { size: 10 } }, grid: { display: false } },
            }
        }
    });
}

// ── Chart Sentimen ─────────────────────────────────────────────
function renderNews(a, b) {
    charts.news = new Chart(document.getElementById('newsChart'), {
        type: 'bar',
        data: {
            labels: ['Positif', 'Netral', 'Negatif'],
            datasets: [
                {
                    label: a.name,
                    data: [a.news.positive, a.news.neutral, a.news.negative],
                    backgroundColor: ['rgba(40,167,69,0.75)', 'rgba(255,193,7,0.75)', 'rgba(220,53,69,0.75)'],
                    borderRadius: 4,
                },
                {
                    label: b.name,
                    data: [b.news.positive, b.news.neutral, b.news.negative],
                    backgroundColor: ['rgba(40,167,69,0.45)', 'rgba(255,193,7,0.45)', 'rgba(220,53,69,0.45)'],
                    borderRadius: 4,
                },
            ]
        },
        options: chartOpts()
    });
}

// ── Chart options helper ───────────────────────────────────────
function tooltipStyle() {
    return {
        backgroundColor: '#162830', titleColor: '#fff', bodyColor: '#a8c5b5',
        borderColor: '#28623A', borderWidth: 1,
    };
}

function chartOpts() {
    return {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { labels: { color: '#a8c5b5', font: { size: 10 } } },
            tooltip: tooltipStyle(),
        },
        scales: {
            x: { ticks: { color: '#a8c5b5', font: { size: 10 } }, grid: { color: 'rgba(40,98,58,0.12)' } },
            y: { ticks: { color: '#a8c5b5', font: { size: 10 } }, grid: { color: 'rgba(40,98,58,0.12)' } },
        }
    };
}
</script>
@endpush
