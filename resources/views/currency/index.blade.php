@extends('layouts.app')

@section('title', 'Currency Impact Dashboard')
@section('page-title', 'Currency Impact Dashboard')

@push('styles')
<style>
    .currency-stat-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 18px 20px;
        margin-bottom: 20px;
    }
    .currency-stat-card .label {
        font-size: 11px;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 6px;
    }
    .currency-stat-card .value {
        font-size: 26px;
        font-weight: 700;
        color: var(--text-primary);
    }
    .volatile-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 14px 16px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .volatile-card .country-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .volatile-card img {
        width: 28px;
        height: 18px;
        object-fit: cover;
        border-radius: 3px;
    }
    .change-up {
        color: #dc3545;
        font-weight: 700;
    }
    .change-down {
        color: #28a745;
        font-weight: 700;
    }
    .chart-container {
        position: relative;
        height: 280px;
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
    .flag-img {
        width: 22px;
        height: 14px;
        object-fit: cover;
        border-radius: 2px;
        margin-right: 6px;
    }
    .search-box {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
        border-radius: 8px;
        padding: 8px 14px;
        font-size: 13px;
        width: 100%;
    }
    .search-box:focus {
        outline: none;
        border-color: var(--accent-light);
    }
    .search-box::placeholder {
        color: var(--text-secondary);
    }
    .rate-row:hover {
        background: #f8fafc !important;
    }
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h2>💱 Currency Impact Dashboard</h2>
        <p>Pantau kurs mata uang real-time dan dampaknya terhadap rantai pasok global</p>
    </div>
    <div style="font-size: 12px; color: var(--text-secondary); text-align:right;">
        <i class="fas fa-sync-alt"></i> Data diperbarui otomatis<br>
        <span style="color: var(--accent-light)">Base: USD (US Dollar)</span>
    </div>
</div>

{{-- Statistik --}}
<div class="row mb-4">
    <div class="col-md-3">
        <div class="currency-stat-card text-center">
            <div class="label">Total Mata Uang</div>
            <div class="value">{{ $totalCurrencies }}</div>
            <small style="color:var(--text-secondary)">Mata uang dipantau</small>
        </div>
    </div>
    <div class="col-md-9">
        <div class="card-custom">
            <div style="font-size: 13px; color: var(--text-secondary); margin-bottom: 12px;">
                <i class="fas fa-fire" style="color:#dc3545"></i>
                <strong style="color:var(--text-primary)"> Top 5 Mata Uang Paling Volatil</strong>
                <span style="font-size:11px; margin-left:8px">(perubahan 7 hari terakhir)</span>
            </div>
            <div class="row">
                @forelse($volatileCountries as $item)
                <div class="col-md-4 col-lg-2" style="flex: 0 0 20%; max-width: 20%;">
                    <div class="volatile-card flex-column align-items-start">
                        <div class="country-info mb-1">
                            @if($item['country']?->flag_url)
                                <img src="{{ $item['country']->flag_url }}" alt="">
                            @endif
                            <span style="font-size:13px; color:var(--text-primary)">{{ $item['country']?->name }}</span>
                        </div>
                        <div style="font-size:12px; color:var(--text-secondary)">{{ $item['currency'] }}</div>
                        <div class="mt-1 {{ $item['direction'] === 'up' ? 'change-up' : 'change-down' }}">
                            <i class="fas fa-arrow-{{ $item['direction'] === 'up' ? 'up' : 'down' }}"></i>
                            {{ abs($item['change_pct']) }}%
                        </div>
                        <div style="font-size:11px; color:var(--text-secondary)">
                            1 USD = {{ number_format($item['rate'], 2) }}
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <p style="color:var(--text-secondary); font-size:13px;">Data historis belum cukup untuk menghitung volatilitas.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Chart: Historis Kurs --}}
    <div class="col-md-7">
        <div class="card-custom">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 style="margin:0; color:var(--text-primary); font-size:15px;">
                        📈 Grafik Historis Kurs
                    </h5>
                    <small style="color:var(--text-secondary)">Pilih negara untuk melihat pergerakan kurs</small>
                </div>
                <div class="d-flex gap-2">
                    <button class="filter-btn active" onclick="setDays(30, this)">30 Hari</button>
                    <button class="filter-btn" onclick="setDays(14, this)">14 Hari</button>
                    <button class="filter-btn" onclick="setDays(7, this)">7 Hari</button>
                </div>
            </div>

            {{-- Dropdown pilih negara --}}
            <select id="countrySelect" class="search-box mb-3" onchange="loadChart()">
                <option value="">— Pilih Negara —</option>
                @foreach($countries as $country)
                <option value="{{ $country->code }}">
                    {{ $country->name }} ({{ $country->currency_code }})
                </option>
                @endforeach
            </select>

            {{-- Info perubahan --}}
            <div id="changeInfo" style="display:none; margin-bottom:12px;">
                <div class="d-flex gap-3 align-items-center">
                    <div>
                        <small style="color:var(--text-secondary)">Kurs Saat Ini</small>
                        <div id="currentRate" style="font-size:18px; font-weight:700; color:var(--text-primary)">-</div>
                    </div>
                    <div>
                        <small style="color:var(--text-secondary)">Perubahan</small>
                        <div id="changePct" style="font-size:18px; font-weight:700;">-</div>
                    </div>
                    <div>
                        <small style="color:var(--text-secondary)">Mata Uang</small>
                        <div id="currencyCode" style="font-size:18px; font-weight:700; color:var(--accent-light)">-</div>
                    </div>
                </div>
            </div>

            <div class="chart-container">
                <canvas id="currencyChart"></canvas>
                <div id="chartPlaceholder" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);color:var(--text-secondary);text-align:center;">
                    <i class="fas fa-chart-line" style="font-size:36px; opacity:0.3"></i>
                    <p style="font-size:13px; margin-top:8px;">Pilih negara untuk menampilkan grafik</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabel Kurs --}}
    <div class="col-md-5">
        <div class="card-custom" style="max-height: 520px; overflow-y: auto;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 style="margin:0; color:var(--text-primary); font-size:15px;">
                    💴 Kurs Terkini (1 USD =)
                </h5>
                <span style="font-size:11px; color:var(--text-secondary)">{{ $currencyRates->total() }} mata uang</span>
            </div>
            <input type="text" id="tableSearch" class="search-box mb-3" placeholder="🔍 Cari negara atau mata uang..." oninput="filterTable()">
            <table class="table table-custom" style="font-size:13px;">
                <thead>
                    <tr>
                        <th>Negara</th>
                        <th>Mata Uang</th>
                        <th style="text-align:right">Kurs</th>
                    </tr>
                </thead>
                <tbody id="rateTableBody">
                    @foreach($currencyRates as $rate)
                    <tr class="rate-row" data-name="{{ strtolower($rate->country?->name) }}" data-currency="{{ strtolower($rate->target_currency) }}">
                        <td>
                            @if($rate->country?->flag_url)
                                <img class="flag-img" src="{{ $rate->country->flag_url }}" alt="">
                            @endif
                            <a href="{{ route('countries.show', $rate->country?->code) }}"
                               style="color:var(--accent-light); text-decoration:none;">
                                {{ $rate->country?->name ?? '-' }}
                            </a>
                        </td>
                        <td style="color:var(--text-secondary)">{{ $rate->target_currency }}</td>
                        <td style="text-align:right; font-weight:600; color:var(--text-primary)">
                            {{ number_format($rate->rate, 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Pagination --}}
            <div class="d-flex justify-content-center mt-2">
                {{ $currencyRates->links() }}
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
let currencyChart = null;
let currentDays   = 30;
const CSRF        = document.querySelector('meta[name="csrf-token"]').content;

// Filter tabel dengan search box
function filterTable() {
    const q = document.getElementById('tableSearch').value.toLowerCase();
    document.querySelectorAll('#rateTableBody .rate-row').forEach(row => {
        const name     = row.dataset.name || '';
        const currency = row.dataset.currency || '';
        row.style.display = (name.includes(q) || currency.includes(q)) ? '' : 'none';
    });
}

// Set periode hari
function setDays(days, btn) {
    currentDays = days;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    if (document.getElementById('countrySelect').value) {
        loadChart();
    }
}

// Load data chart via AJAX
function loadChart() {
    const code = document.getElementById('countrySelect').value;
    if (!code) return;

    document.getElementById('chartPlaceholder').style.display = 'flex';
    document.getElementById('changeInfo').style.display = 'none';

    fetch(`/api/currency/${code}?days=${currentDays}`, {
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.status !== 'success') return;

        const labels = data.chart.labels;
        const rates  = data.chart.rates;

        // Update info
        document.getElementById('currentRate').textContent =
            parseFloat(data.current_rate).toLocaleString('id-ID', { minimumFractionDigits: 2 });
        document.getElementById('currencyCode').textContent = data.currency;

        const changePctEl = document.getElementById('changePct');
        const pct = parseFloat(data.change_pct);
        changePctEl.textContent = (pct >= 0 ? '▲ +' : '▼ ') + pct.toFixed(2) + '%';
        changePctEl.style.color = pct >= 0 ? '#dc3545' : '#28a745';

        document.getElementById('changeInfo').style.display = 'block';
        document.getElementById('chartPlaceholder').style.display = 'none';

        // Render chart
        renderChart(labels, rates, data.country, data.currency);
    })
    .catch(() => {
        document.getElementById('chartPlaceholder').innerHTML =
            '<p style="color:#dc3545">Gagal memuat data grafik.</p>';
    });
}

function renderChart(labels, rates, countryName, currency) {
    const ctx = document.getElementById('currencyChart').getContext('2d');

    if (currencyChart) currencyChart.destroy();

    // Warna gradient
    const gradient = ctx.createLinearGradient(0, 0, 0, 280);
    gradient.addColorStop(0, 'rgba(58,138,82,0.4)');
    gradient.addColorStop(1, 'rgba(58,138,82,0.02)');

    currencyChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: `1 USD → ${currency} (${countryName})`,
                data: rates,
                borderColor: '#3a8a52',
                backgroundColor: gradient,
                borderWidth: 2,
                pointRadius: labels.length > 20 ? 2 : 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#3a8a52',
                tension: 0.3,
                fill: true,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: { color: '#718096', font: { size: 12 } }
                },
                tooltip: {
                    backgroundColor: '#ffffff',
                    titleColor: '#2d3748',
                    bodyColor: '#718096',
                    borderColor: '#e2e8f0',
                    borderWidth: 1,
                    callbacks: {
                        label: ctx =>
                            ` 1 USD = ${parseFloat(ctx.raw).toLocaleString('id-ID', { minimumFractionDigits: 2 })} ${currency}`
                    }
                }
            },
            scales: {
                x: {
                    ticks: { color: '#718096', font: { size: 11 } },
                    grid:  { color: 'var(--border-color)' }
                },
                y: {
                    ticks: {
                        color: '#718096',
                        font: { size: 11 },
                        callback: v => parseFloat(v).toLocaleString('id-ID')
                    },
                    grid: { color: 'var(--border-color)' }
                }
            }
        }
    });
}
</script>
@endpush
