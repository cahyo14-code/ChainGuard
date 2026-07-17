@extends('layouts.app')

@section('title', 'Port Location Dashboard')
@section('page-title', 'Port Location Dashboard')

@push('styles')
<style>
    #portMap {
        height: 520px;
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
        color: var(--text-primary);
    }
    .control-box {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 16px;
        margin-bottom: 16px;
    }
    .control-box label {
        font-size: 11px;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 1px;
        display: block;
        margin-bottom: 6px;
    }
    .form-select-custom,
    .form-input-custom {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
        border-radius: 8px;
        padding: 8px 12px;
        font-size: 13px;
        width: 100%;
    }
    .form-select-custom:focus,
    .form-input-custom:focus {
        outline: none;
        border-color: var(--accent-light);
    }
    .form-select-custom option { background: var(--bg-secondary); }
    .port-list-item {
        padding: 8px 0;
        border-bottom: 1px solid rgba(40,98,58,0.15);
        font-size: 12px;
        cursor: pointer;
        transition: all 0.15s;
    }
    .port-list-item:hover {
        color: var(--accent-light);
        padding-left: 4px;
    }
    .port-list-item:last-child { border-bottom: none; }
    .flag-xs {
        width: 18px; height: 11px;
        object-fit: cover; border-radius: 2px;
        margin-right: 5px;
    }
    .port-count-badge {
        background: rgba(40,98,58,0.3);
        color: var(--accent-light);
        font-size: 10px;
        padding: 2px 7px;
        border-radius: 10px;
        float: right;
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
    .popup-title { font-weight: 700; color: #3a8a52; margin-bottom: 4px; }
    .popup-row { color: #a8c5b5; font-size: 12px; margin-bottom: 2px; }
    .popup-row span { color: #fff; }
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h2>⚓ Port Location Dashboard</h2>
        <p>Pantau lokasi pelabuhan seluruh dunia secara interaktif di atas peta</p>
    </div>
    <div style="font-size:12px; color:var(--text-secondary); text-align:right;">
        <i class="fas fa-anchor" style="color:var(--accent-light)"></i>
        <strong style="color:var(--accent-light)">{{ number_format($totalPorts) }}</strong> pelabuhan terdaftar
    </div>
</div>

{{-- Stat cards --}}
<div class="row mb-3">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="label">Total Pelabuhan</div>
            <div class="value">{{ number_format($totalPorts) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="label">Pelabuhan Aktif</div>
            <div class="value" style="color:#28a745">{{ number_format($activePorts) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="label">Negara Tercakup</div>
            <div class="value" style="color:var(--accent-light)">{{ $totalCountries }}</div>
        </div>
    </div>
</div>

<div class="row">

    {{-- Sidebar kiri: kontrol & daftar --}}
    <div class="col-md-3">

        {{-- Search pelabuhan --}}
        <div class="control-box">
            <label>🔍 Cari Pelabuhan</label>
            <input type="text" id="portSearch" class="form-input-custom"
                   placeholder="Nama pelabuhan / kota..."
                   oninput="searchPorts()">
        </div>

        {{-- Filter negara --}}
        <div class="control-box">
            <label>🌍 Filter Negara</label>
            <select id="countryFilter" class="form-select-custom" onchange="filterByCountry()">
                <option value="">— Semua Negara —</option>
                @foreach($countries as $country)
                <option value="{{ $country->code }}">
                    {{ $country->name }}
                </option>
                @endforeach
            </select>
        </div>

        {{-- Tombol aksi --}}
        <div class="control-box">
            <label>🗺 Kontrol Peta</label>
            <button onclick="resetMap()" class="btn-accent w-100 mb-2" style="font-size:12px; padding:8px">
                <i class="fas fa-globe"></i> Reset Tampilan
            </button>
            <button onclick="clusterToggle()" id="clusterBtn" class="btn-accent w-100" style="font-size:12px; padding:8px; background:var(--bg-secondary); border-color:var(--border-color);">
                <i class="fas fa-object-group"></i> Cluster: ON
            </button>
        </div>

        {{-- Daftar port (live update) --}}
        <div class="control-box" style="max-height: 280px; overflow-y: auto;">
            <label>📋 Daftar (<span id="portListCount">{{ count($ports) }}</span>)</label>
            <div id="portListContainer">
                @foreach($ports->take(50) as $port)
                <div class="port-list-item"
                     onclick="flyToPort({{ $port['lat'] }}, {{ $port['lng'] }}, '{{ addslashes($port['name']) }}')"
                     data-name="{{ strtolower($port['name']) }}"
                     data-city="{{ strtolower($port['city'] ?? '') }}"
                     data-country="{{ $port['country_code'] }}">
                    @if($port['flag_url'])
                        <img class="flag-xs" src="{{ $port['flag_url'] }}" alt="">
                    @endif
                    <strong>{{ $port['name'] }}</strong>
                    @if($port['city'])
                        <span style="color:var(--text-secondary)"> — {{ $port['city'] }}</span>
                    @endif
                    <br>
                    <span style="color:var(--text-secondary); font-size:11px">{{ $port['country_name'] }}</span>
                </div>
                @endforeach
            </div>
        </div>

    </div>

    {{-- Peta Leaflet --}}
    <div class="col-md-9">
        <div id="portMap"></div>
        <div style="font-size:11px; color:var(--text-secondary); margin-top:8px; text-align:right;">
            <i class="fas fa-info-circle"></i>
            Klik marker untuk info pelabuhan · Scroll untuk zoom · Drag untuk geser peta
        </div>
    </div>
</div>

@endsection

@push('scripts')
{{-- Leaflet MarkerCluster --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<script>
// ── Data dari Laravel ──────────────────────────────────────────
const ALL_PORTS = @json($ports->values());

// ── Inisialisasi peta ──────────────────────────────────────────
const map = L.map('portMap', {
    center: [20, 0],
    zoom: 2,
    zoomControl: true,
    preferCanvas: true,
});

// Tile layer OpenStreetMap dengan warna gelap
L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/">OpenStreetMap</a> · © <a href="https://carto.com/">CARTO</a>',
    maxZoom: 18,
}).addTo(map);

// ── Custom icon pelabuhan ──────────────────────────────────────
const portIcon = L.divIcon({
    className: '',
    html: `<div style="
        width: 10px; height: 10px;
        background: #3a8a52;
        border: 2px solid #28623A;
        border-radius: 50%;
        box-shadow: 0 0 6px rgba(58,138,82,0.8);
    "></div>`,
    iconSize: [10, 10],
    iconAnchor: [5, 5],
});

// ── Cluster layer ──────────────────────────────────────────────
let clusterGroup = L.markerClusterGroup({
    maxClusterRadius: 40,
    iconCreateFunction: function(cluster) {
        const count = cluster.getChildCount();
        const size  = count > 100 ? 42 : count > 30 ? 34 : 26;
        return L.divIcon({
            html: `<div style="
                width:${size}px; height:${size}px;
                background:rgba(40,98,58,0.85);
                border:2px solid #3a8a52;
                border-radius:50%;
                color:#fff;
                display:flex; align-items:center; justify-content:center;
                font-size:${size > 34 ? 13 : 11}px; font-weight:700;
                box-shadow:0 2px 8px rgba(0,0,0,0.4);
            ">${count}</div>`,
            className: '',
            iconSize: [size, size],
            iconAnchor: [size/2, size/2],
        });
    }
});

let rawLayer   = L.layerGroup();
let useCluster = true;
let allMarkers = [];

// ── Build markers ──────────────────────────────────────────────
function buildMarkers(ports) {
    clusterGroup.clearLayers();
    rawLayer.clearLayers();
    allMarkers = [];

    ports.forEach(port => {
        if (!port.lat || !port.lng) return;

        const marker = L.marker([port.lat, port.lng], { icon: portIcon });

        const flagHtml = port.flag_url
            ? `<img src="${port.flag_url}" style="width:18px;height:11px;object-fit:cover;border-radius:2px;margin-right:5px;">`
            : '';

        marker.bindPopup(`
            <div class="popup-title">⚓ ${port.name}</div>
            <div class="popup-row">Kode: <span>${port.code ?? '-'}</span></div>
            <div class="popup-row">Kota: <span>${port.city ?? '-'}</span></div>
            <div class="popup-row">Tipe: <span>${port.type ?? 'Seaport'}</span></div>
            <div class="popup-row" style="margin-top:6px;">${flagHtml}<span>${port.country_name ?? '-'}</span></div>
        `, { maxWidth: 220 });

        marker._portData = port;
        allMarkers.push(marker);
        clusterGroup.addLayer(marker);
    });

    if (useCluster) {
        map.addLayer(clusterGroup);
    } else {
        allMarkers.forEach(m => rawLayer.addLayer(m));
        map.addLayer(rawLayer);
    }
}

buildMarkers(ALL_PORTS);

// ── Search ─────────────────────────────────────────────────────
function searchPorts() {
    const q       = document.getElementById('portSearch').value.toLowerCase();
    const country = document.getElementById('countryFilter').value;
    applyFilters(q, country);
}

// ── Filter negara ──────────────────────────────────────────────
function filterByCountry() {
    const q       = document.getElementById('portSearch').value.toLowerCase();
    const country = document.getElementById('countryFilter').value;
    applyFilters(q, country);
}

function applyFilters(q, country) {
    const filtered = ALL_PORTS.filter(p => {
        const nameMatch    = !q || (p.name?.toLowerCase().includes(q) || p.city?.toLowerCase().includes(q));
        const countryMatch = !country || p.country_code === country;
        return nameMatch && countryMatch;
    });

    buildMarkers(filtered);
    document.getElementById('portListCount').textContent = filtered.length;

    // Update daftar sidebar
    const container = document.getElementById('portListContainer');
    container.innerHTML = filtered.slice(0, 80).map(port => `
        <div class="port-list-item"
             onclick="flyToPort(${port.lat}, ${port.lng}, '${(port.name || '').replace(/'/g, "\\'")}')"
             style="padding:8px 0; border-bottom:1px solid rgba(40,98,58,0.15); font-size:12px; cursor:pointer;">
            ${port.flag_url ? `<img class="flag-xs" src="${port.flag_url}" style="width:18px;height:11px;object-fit:cover;border-radius:2px;margin-right:5px;">` : ''}
            <strong style="color:var(--text-primary)">${port.name}</strong>
            ${port.city ? `<span style="color:var(--text-secondary)"> — ${port.city}</span>` : ''}
            <br>
            <span style="color:var(--text-secondary); font-size:11px">${port.country_name ?? ''}</span>
        </div>
    `).join('');

    // Zoom ke negara jika difilter
    if (country && filtered.length > 0) {
        const lats = filtered.map(p => p.lat);
        const lngs = filtered.map(p => p.lng);
        map.fitBounds([
            [Math.min(...lats) - 2, Math.min(...lngs) - 2],
            [Math.max(...lats) + 2, Math.max(...lngs) + 2],
        ]);
    }
}

// ── Fly to port ────────────────────────────────────────────────
function flyToPort(lat, lng, name) {
    map.flyTo([lat, lng], 10, { duration: 1.2 });
}

// ── Reset peta ─────────────────────────────────────────────────
function resetMap() {
    document.getElementById('portSearch').value    = '';
    document.getElementById('countryFilter').value = '';
    buildMarkers(ALL_PORTS);
    document.getElementById('portListCount').textContent = ALL_PORTS.length;
    map.setView([20, 0], 2);
}

// ── Toggle cluster ─────────────────────────────────────────────
function clusterToggle() {
    useCluster = !useCluster;
    const btn  = document.getElementById('clusterBtn');

    map.removeLayer(clusterGroup);
    map.removeLayer(rawLayer);
    clusterGroup.clearLayers();
    rawLayer.clearLayers();

    if (useCluster) {
        allMarkers.forEach(m => clusterGroup.addLayer(m));
        map.addLayer(clusterGroup);
        btn.innerHTML = '<i class="fas fa-object-group"></i> Cluster: ON';
        btn.style.background = 'var(--bg-secondary)';
    } else {
        allMarkers.forEach(m => rawLayer.addLayer(m));
        map.addLayer(rawLayer);
        btn.innerHTML = '<i class="fas fa-circle"></i> Cluster: OFF';
        btn.style.background = 'var(--accent)';
    }
}
</script>
@endpush
