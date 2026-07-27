@extends('layouts.app')

@section('title', 'Route Simulator')
@section('page-title', '🚢 Route Simulator — Impor & Estimasi Kendala Logistik')

@push('styles')
<style>
    .simulator-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .simulator-title {
        font-size: 15px;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    #map-simulator {
        height: 460px;
        border-radius: 10px;
        border: 1px solid var(--border-color);
        z-index: 1;
        width: 100%;
    }
    .eta-box {
        border-radius: 10px;
        padding: 16px 12px;
        text-align: center;
        margin-bottom: 12px;
    }
    .eta-normal { background: rgba(40,167,69,0.1); border: 1px solid #28a745; }
    .eta-risk   { background: rgba(220,53,69,0.1);  border: 1px solid #dc3545; }
    .eta-value  { font-size: 28px; font-weight: 700; margin: 4px 0; }
    .factor-item {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 11px 13px;
        margin-bottom: 9px;
    }
    .factor-item .fi-title {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-primary);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .factor-item .fi-desc {
        font-size: 12px;
        color: var(--text-secondary);
        margin-top: 4px;
        line-height: 1.5;
    }
    .delay-badge {
        font-size: 11px;
        padding: 2px 9px;
        border-radius: 12px;
        font-weight: 600;
        white-space: nowrap;
    }
    .delay-red    { background: rgba(220,53,69,0.2);  color: #dc3545; }
    .delay-green  { background: rgba(40,167,69,0.2);  color: #28a745; }
    .delay-yellow { background: rgba(255,193,7,0.2);  color: #d39e00; }
    .delay-orange { background: rgba(253,126,20,0.2); color: #fd7e14; }

    /* Active shipments */
    .active-shipment-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 14px 16px;
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    .active-shipment-card:hover { border-color: var(--accent); }
    .btn-complete {
        background: #dcfce7; border: 1px solid #bbf7d0;
        color: #15803d; padding: 6px 14px; border-radius: 8px;
        font-size: 12px; font-weight: 600; cursor: pointer;
        transition: all 0.2s;
    }
    .btn-complete:hover { background: #15803d; color: white; }
    .flag-sm { width: 20px; height: 13px; object-fit: cover; border-radius: 2px; margin-right: 5px; }
    .modal-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,0.5); z-index: 9999;
        align-items: center; justify-content: center;
    }
    .modal-overlay.open { display: flex; }
    .modal-box {
        background: var(--bg-card); border: 1px solid var(--border-color);
        border-radius: 12px; padding: 28px; width: 100%; max-width: 420px; margin: 20px;
    }
    .form-input-sim {
        background: #f8fafc; border: 1.5px solid var(--border-color);
        color: var(--text-primary); border-radius: 8px;
        padding: 9px 12px; font-size: 13px; width: 100%;
    }
    .form-input-sim:focus { outline: none; border-color: var(--accent); }
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h2>🚢 Route Simulator</h2>
        <p>Simulasi rute pengiriman impor dengan estimasi ETA dan analisis 5 faktor kendala</p>
    </div>
    <a href="{{ route('simulator.history') }}" style="background: var(--accent-soft); border: 1px solid var(--accent); color: var(--accent); padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none;">
        <i class="fas fa-history"></i> Histori Pengiriman
    </a>
</div>

{{-- Pengiriman Aktif --}}
@if($activeShipments->isNotEmpty())
<div class="simulator-card" style="margin-bottom: 20px;">
    <div class="simulator-title">
        <i class="fas fa-ship" style="color: #1d4ed8;"></i>
        Pengiriman Aktif ({{ $activeShipments->count() }})
    </div>
    @foreach($activeShipments as $shipment)
    <div class="active-shipment-card"
         style="cursor: pointer;"
         onclick="loadActiveShipmentRoute(
             {{ $shipment->id }},
             {{ $shipment->originCountry?->latitude ?? 0 }},
             {{ $shipment->originCountry?->longitude ?? 0 }},
             {{ $shipment->destinationCountry?->latitude ?? 0 }},
             {{ $shipment->destinationCountry?->longitude ?? 0 }},
             '{{ addslashes($shipment->originCountry?->name) }}',
             '{{ addslashes($shipment->destinationCountry?->name) }}',
             '{{ addslashes($shipment->origin_port) }}',
             '{{ addslashes($shipment->destination_port) }}',
             {{ $shipment->risk_adjusted_days }},
             '{{ $shipment->created_at->toISOString() }}',
             {{ $shipment->total_delay_days }}
         )">
        <div>
            <div class="d-flex align-items-center mb-1">
                @if($shipment->originCountry?->flag_url)
                    <img class="flag-sm" src="{{ $shipment->originCountry->flag_url }}" alt="">
                @endif
                <strong style="font-size: 13px;">{{ $shipment->originCountry?->name }}</strong>
                <span style="color: var(--accent); margin: 0 6px;"><i class="fas fa-arrow-right"></i></span>
                @if($shipment->destinationCountry?->flag_url)
                    <img class="flag-sm" src="{{ $shipment->destinationCountry->flag_url }}" alt="">
                @endif
                <strong style="font-size: 13px;">{{ $shipment->destinationCountry?->name }}</strong>
            </div>
            <div style="font-size: 12px; color: var(--text-secondary);">
                <i class="fas fa-route"></i> {{ number_format($shipment->nautical_miles) }} NM ·
                <i class="fas fa-clock"></i> ETA {{ $shipment->risk_adjusted_eta?->format('d M Y') }} ·
                @if($shipment->total_delay_days > 0)
                    <span style="color: #dc3545;">+{{ $shipment->total_delay_days }} hari keterlambatan</span>
                @else
                    <span style="color: #28a745;">Tidak ada kendala</span>
                @endif
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span style="font-size: 11px; color: var(--text-secondary);">{{ $shipment->created_at->diffForHumans() }}</span>
            <span style="font-size: 11px; color: var(--accent); display:flex; align-items:center; gap:4px;">
                <i class="fas fa-map-marker-alt"></i> Klik untuk lihat di peta
            </span>
            <button class="btn-complete" onclick="event.stopPropagation(); openCompleteModal({{ $shipment->id }}, '{{ addslashes($shipment->originCountry?->name) }} → {{ addslashes($shipment->destinationCountry?->name) }}')">
                <i class="fas fa-check"></i> Tandai Selesai
            </button>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- Modal Tandai Selesai --}}
<div class="modal-overlay" id="completeModal">
    <div class="modal-box">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 style="margin:0; color:var(--text-primary); font-size:15px;">
                <i class="fas fa-check-circle" style="color:#15803d"></i> Tandai Pengiriman Selesai
            </h5>
            <button onclick="closeCompleteModal()" style="background:none; border:none; font-size:20px; cursor:pointer; color:var(--text-secondary);">×</button>
        </div>
        <p id="modal-route-label" style="font-size:13px; color:var(--text-secondary); margin-bottom:14px;"></p>
        <div class="mb-3">
            <label style="font-size:11px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:6px;">Catatan (opsional)</label>
            <textarea id="complete-notes" class="form-input-sim" rows="3" placeholder="Contoh: Barang tiba tepat waktu, kondisi baik..."></textarea>
        </div>
        <button onclick="submitComplete()" class="btn-accent w-100" style="padding:10px;">
            <i class="fas fa-check"></i> Konfirmasi Selesai
        </button>
    </div>
</div>

<div class="row mb-3">
    {{-- Kolom Kiri: Form Input --}}
    <div class="col-md-4">
        <div class="simulator-card" style="height: calc(100% - 20px);">
            <div class="simulator-title">
                <i class="fas fa-ship" style="color: var(--accent-light);"></i>
                Pilih Rute Pengiriman Impor
            </div>

            <form id="form-simulator">
                @csrf
                {{-- Negara Asal --}}
                <div class="mb-2">
                    <label style="font-size: 12px; color: var(--text-secondary); margin-bottom: 6px; display: block;">
                        📍 Negara Asal (Ekspor Kargo)
                    </label>
                    <select name="origin_country_id" id="origin_country_id"
                            class="form-input" style="width: 100%; font-size: 13px;" required>
                        <option value="">-- Pilih Negara Asal --</option>
                        @foreach($countries as $c)
                            <option value="{{ $c->id }}">
                                {{ $c->name }} ({{ $c->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Pelabuhan Asal --}}
                <div class="mb-3" id="origin-port-wrapper" style="display:none;">
                    <label style="font-size: 12px; color: var(--text-secondary); margin-bottom: 6px; display: block;">
                        ⚓ Pelabuhan Asal
                    </label>
                    <select name="origin_port_id" id="origin_port_id"
                            class="form-input" style="width: 100%; font-size: 13px;">
                        <option value="">-- Pilih Pelabuhan --</option>
                    </select>
                    <div id="origin-port-loading" style="display:none; font-size:11px; color:var(--text-secondary); margin-top:4px;">
                        <i class="fas fa-spinner fa-spin"></i> Memuat pelabuhan...
                    </div>
                </div>

                {{-- Arrow --}}
                <div class="text-center mb-2" style="color: var(--accent-light); font-size: 18px;">
                    <i class="fas fa-arrow-down"></i>
                </div>

                {{-- Negara Tujuan --}}
                <div class="mb-2">
                    <label style="font-size: 12px; color: var(--text-secondary); margin-bottom: 6px; display: block;">
                        🏁 Negara Tujuan (Impor Kargo)
                    </label>
                    <select name="destination_country_id" id="destination_country_id"
                            class="form-input" style="width: 100%; font-size: 13px;" required>
                        <option value="">-- Pilih Negara Tujuan --</option>
                        @foreach($countries as $c)
                            <option value="{{ $c->id }}" {{ $c->code === 'IDN' ? 'selected' : '' }}>
                                {{ $c->name }} ({{ $c->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Pelabuhan Tujuan --}}
                <div class="mb-4" id="dest-port-wrapper" style="display:none;">
                    <label style="font-size: 12px; color: var(--text-secondary); margin-bottom: 6px; display: block;">
                        ⚓ Pelabuhan Tujuan
                    </label>
                    <select name="destination_port_id" id="destination_port_id"
                            class="form-input" style="width: 100%; font-size: 13px;">
                        <option value="">-- Pilih Pelabuhan --</option>
                    </select>
                    <div id="dest-port-loading" style="display:none; font-size:11px; color:var(--text-secondary); margin-top:4px;">
                        <i class="fas fa-spinner fa-spin"></i> Memuat pelabuhan...
                    </div>
                </div>

                <button type="submit" class="btn-accent w-100" id="btn-calculate"
                        style="padding: 10px; font-size: 13px; letter-spacing: 0.5px;">
                    <i class="fas fa-route"></i> &nbsp;Simulasikan Rute
                </button>
            </form>

            {{-- Info Rute (muncul setelah hitung) --}}
            <div id="route-info" style="display:none; margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--border-color);">
                <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px;">Info Rute</div>
                <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px;">
                    <span style="color: var(--text-secondary);">📍 Asal</span>
                    <strong id="info-origin" style="color: var(--text-primary);">-</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px;">
                    <span style="color: var(--text-secondary);">🏁 Tujuan</span>
                    <strong id="info-dest" style="color: var(--text-primary);">-</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px;">
                    <span style="color: var(--text-secondary);">⚓ Pelabuhan Asal</span>
                    <span id="info-port-origin" style="color: var(--text-secondary); font-size: 12px; text-align: right; max-width: 60%;">-</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 13px;">
                    <span style="color: var(--text-secondary);">🚢 Jarak Laut</span>
                    <strong id="info-distance" style="color: var(--accent-light);">-</strong>
                </div>
            </div>
        </div>
    </div>

    {{-- Kolom Kanan: Peta (SELALU TAMPIL) --}}
    <div class="col-md-8">
        <div class="simulator-card" style="padding: 15px;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="simulator-title mb-0">
                    <i class="fas fa-map-marked-alt" style="color: var(--accent-light);"></i>
                    Visualisasi Jalur Kapal Pelayaran Laut
                </div>
                <div id="map-status-badge"
                     style="font-size: 12px; color: var(--text-secondary); background: var(--bg-secondary); padding: 4px 12px; border-radius: 20px; border: 1px solid var(--border-color);">
                    <i class="fas fa-info-circle"></i> Pilih negara untuk melihat rute
                </div>
            </div>
            {{-- Peta ditampilkan LANGSUNG, tidak di dalam display:none --}}
            <div id="map-simulator"></div>
        </div>
    </div>
</div>

{{-- Baris 2: Hasil ETA & 5 Faktor (muncul setelah hitung) --}}
<div id="results-wrapper" style="display: none;">
    <div class="row">
        {{-- ETA Cards & Rekomendasi --}}
        <div class="col-md-4">
            <div class="simulator-card">
                <div class="simulator-title">
                    <i class="fas fa-clock" style="color: var(--accent-light);"></i>
                    Estimasi Waktu Tiba (ETA)
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="eta-box eta-normal">
                            <small style="color: #28a745; font-size: 11px; font-weight: 600; text-transform: uppercase;">🟢 Tanpa Kendala</small>
                            <div class="eta-value" style="color: #28a745;" id="val-normal-days">-</div>
                            <small style="color: var(--text-secondary); font-size: 11px;" id="val-normal-date">-</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="eta-box eta-risk">
                            <small style="color: #dc3545; font-size: 11px; font-weight: 600; text-transform: uppercase;">🔴 Dengan Kendala</small>
                            <div class="eta-value" style="color: #dc3545;" id="val-risk-days">-</div>
                            <small style="color: var(--text-secondary); font-size: 11px;" id="val-risk-date">-</small>
                        </div>
                    </div>
                </div>

                <div style="text-align: center; margin-bottom: 14px;">
                    <span style="background: rgba(220,53,69,0.1); border: 1px solid #dc3545; color: #dc3545; padding: 4px 14px; border-radius: 20px; font-size: 13px; font-weight: 600;" id="val-delay-days">
                        +0 Hari Keterlambatan
                    </span>
                </div>

                {{-- Rekomendasi --}}
                <div id="recommendation-card" style="border-radius: 8px; padding: 13px; font-size: 12px; line-height: 1.6;">
                    <div style="font-weight: 600; margin-bottom: 5px; font-size: 13px;">💡 Rekomendasi Impor:</div>
                    <div id="val-recommendation-text">-</div>
                </div>
            </div>
        </div>

        {{-- 5 Faktor Kendala --}}
        <div class="col-md-8">
            <div class="simulator-card">
                <div class="simulator-title">
                    <i class="fas fa-exclamation-triangle" style="color: var(--accent-light);"></i>
                    Analisis 5 Faktor Kendala Pengiriman Impor
                </div>

                <div class="factor-item">
                    <div class="fi-title">
                        <span>⛈ 1. Cuaca Ekstrem (Weather Risk)</span>
                        <span class="delay-badge delay-green" id="badge-weather-delay">+0 Hari</span>
                    </div>
                    <div class="fi-desc" id="desc-weather">—</div>
                </div>

                <div class="factor-item">
                    <div class="fi-title">
                        <span>💱 2. Perubahan Nilai Tukar (Currency)</span>
                        <span class="delay-badge delay-green" id="badge-currency-impact">0%</span>
                    </div>
                    <div class="fi-desc" id="desc-currency">—</div>
                </div>

                <div class="factor-item">
                    <div class="fi-title">
                        <span>🛡 3. Konflik Geopolitik</span>
                        <span class="delay-badge delay-green" id="badge-geo-delay">+0 Hari</span>
                    </div>
                    <div class="fi-desc" id="desc-geopolitics">—</div>
                </div>

                <div class="factor-item">
                    <div class="fi-title">
                        <span>⚓ 4. Kemacetan Pelabuhan</span>
                        <span class="delay-badge delay-green" id="badge-port-delay">+0 Hari</span>
                    </div>
                    <div class="fi-desc" id="desc-port">—</div>
                </div>

                <div class="factor-item">
                    <div class="fi-title">
                        <span>📈 5. Inflasi Negara Asal</span>
                        <span class="delay-badge delay-yellow" id="badge-inflation-rate">0%</span>
                    </div>
                    <div class="fi-desc" id="desc-inflation">—</div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    let map = null;
    let originMarker = null;
    let destMarker = null;
    let seaRouteLine = null;
    let shipMarker = null;
    let animationTimer = null;

    document.addEventListener('DOMContentLoaded', function () {
        initMap();

        document.getElementById('form-simulator').addEventListener('submit', function (e) {
            e.preventDefault();
            calculateSimulatorRoute();
        });

        // Load pelabuhan saat negara asal dipilih
        document.getElementById('origin_country_id').addEventListener('change', function() {
            loadPorts(this.value, 'origin');
        });

        // Load pelabuhan saat negara tujuan dipilih
        document.getElementById('destination_country_id').addEventListener('change', function() {
            loadPorts(this.value, 'dest');
        });
    });

    // ── Load ports via AJAX ────────────────────────────────────
    // requestSeq mencegah race condition: kalau user ganti negara
    // dengan cepat, hanya respons dari request TERAKHIR yang dipakai.
    const portRequestSeq = { origin: 0, dest: 0 };

    // Pemetaan eksplisit ID select — jangan ditebak dari `type`,
    // karena ID di HTML untuk tujuan adalah "destination_port_id", bukan "dest_port_id".
    const portSelectIdMap = { origin: 'origin_port_id', dest: 'destination_port_id' };

    async function loadPorts(countryId, type) {
        const wrapper  = document.getElementById(`${type}-port-wrapper`);
        const select   = document.getElementById(portSelectIdMap[type]);
        const loading  = document.getElementById(`${type}-port-loading`);

        if (!wrapper || !select || !loading) {
            console.error(`loadPorts(${type}): elemen DOM tidak ditemukan`, { wrapper, select, loading });
            return;
        }

        if (!countryId) {
            wrapper.style.display = 'none';
            return;
        }

        const mySeq = ++portRequestSeq[type];

        loading.style.display = 'block';
        wrapper.style.display = 'block';
        select.innerHTML = '<option value="">-- Pilih Pelabuhan --</option>';

        // Batasi waktu tunggu 15 detik — kalau server tidak merespons,
        // jangan biarkan spinner muter selamanya.
        const controller = new AbortController();
        const timeoutId  = setTimeout(() => controller.abort(), 15000);

        try {
            const res = await fetch(`/api/route-simulator/ports/${countryId}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                signal: controller.signal
            });

            if (!res.ok) {
                throw new Error(`Server merespons status ${res.status}`);
            }

            const data = await res.json();

            // Kalau ada request lebih baru yang sudah jalan (user ganti pilihan lagi), abaikan hasil ini.
            if (mySeq !== portRequestSeq[type]) return;

            if (data.data && data.data.length > 0) {
                data.data.forEach(port => {
                    const opt = document.createElement('option');
                    opt.value = port.id;
                    opt.textContent = port.name + (port.city ? ` — ${port.city}` : '');
                    opt.dataset.lat = port.latitude;
                    opt.dataset.lng = port.longitude;
                    select.appendChild(opt);
                });
                select.value = data.data[0].id;
            } else {
                select.innerHTML = '<option value="">Tidak ada pelabuhan tersedia</option>';
            }
        } catch (e) {
            if (mySeq !== portRequestSeq[type]) return;
            console.error(`loadPorts(${type}) gagal:`, e);
            select.innerHTML = e.name === 'AbortError'
                ? '<option value="">Server terlalu lama merespons — coba lagi</option>'
                : '<option value="">Gagal memuat pelabuhan</option>';
        } finally {
            clearTimeout(timeoutId);
            if (mySeq === portRequestSeq[type]) {
                loading.style.display = 'none';
            }
        }
    }

    function initMap() {
        // Peta default center di Asia Tenggara
        map = L.map('map-simulator', {
            center: [10, 100],
            zoom: 3,
            zoomControl: true
        });

        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/">OpenStreetMap</a> © <a href="https://carto.com/">CARTO</a>',
            maxZoom: 18
        }).addTo(map);

        // Pastikan peta render dengan benar
        setTimeout(() => { map.invalidateSize(); }, 200);
    }

    async function calculateSimulatorRoute() {
        const originId    = document.getElementById('origin_country_id').value;
        const destId      = document.getElementById('destination_country_id').value;
        const originPortId = document.getElementById('origin_port_id').value;
        const destPortId   = document.getElementById('destination_port_id').value;
        const btn         = document.getElementById('btn-calculate');

        if (!originId || !destId) {
            alert('Silakan pilih negara asal dan negara tujuan terlebih dahulu.');
            return;
        }
        if (originId === destId) {
            alert('Negara asal dan tujuan tidak boleh sama.');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghitung Rute...';

        try {
            const response = await fetch('{{ route("api.simulator.calculate") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    origin_country_id:      originId,
                    destination_country_id: destId,
                    origin_port_id:         originPortId  || null,
                    destination_port_id:    destPortId    || null,
                })
            });

            const result = await response.json();

            if (result.status === 'success') {
                renderSimulatorData(result.data);
            } else {
                alert(result.message || 'Gagal menghitung rute. Coba lagi.');
            }
        } catch (error) {
            console.error('Simulator Error:', error);
            alert('Terjadi kesalahan koneksi ke server.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-route"></i> &nbsp;Simulasikan Rute';
        }
    }

    function renderSimulatorData(data) {
        // Tampilkan panel hasil
        document.getElementById('results-wrapper').style.display = 'block';
        document.getElementById('route-info').style.display = 'block';

        // ── Info Rute ────────────────────────────────────────────────
        document.getElementById('info-origin').innerText      = data.origin.name;
        document.getElementById('info-dest').innerText        = data.destination.name;
        document.getElementById('info-port-origin').innerText = data.origin.port_name;
        document.getElementById('info-distance').innerText    = data.route.nautical_miles + ' NM';

        // Update map status badge
        document.getElementById('map-status-badge').innerHTML =
            `🚢 ${data.origin.name} → ${data.destination.name} &nbsp;|&nbsp; ${data.route.nautical_miles} NM`;

        // ── ETA ──────────────────────────────────────────────────────
        document.getElementById('val-normal-days').innerText = data.eta.normal_days + ' Hari';
        document.getElementById('val-normal-date').innerText = data.eta.normal_date;
        document.getElementById('val-risk-days').innerText   = data.eta.risk_adjusted_days + ' Hari';
        document.getElementById('val-risk-date').innerText   = data.eta.risk_adjusted_date;
        document.getElementById('val-delay-days').innerText  = '+' + data.eta.total_delay_days + ' Hari Keterlambatan';

        // ── 5 Faktor ─────────────────────────────────────────────────
        // 1. Cuaca
        const wBadge = document.getElementById('badge-weather-delay');
        wBadge.innerText = '+' + data.factors.weather.delay + ' Hari';
        wBadge.className = 'delay-badge ' + (data.factors.weather.delay > 0 ? 'delay-red' : 'delay-green');
        document.getElementById('desc-weather').innerText = data.factors.weather.desc;

        // 2. Kurs
        const cBadge = document.getElementById('badge-currency-impact');
        const pct    = data.factors.currency.impact_pct;
        cBadge.innerText = (pct > 0 ? '+' : '') + pct + '%';
        cBadge.className = 'delay-badge ' + (Math.abs(pct) > 3 ? 'delay-red' : (Math.abs(pct) > 1.5 ? 'delay-yellow' : 'delay-green'));
        document.getElementById('desc-currency').innerText = data.factors.currency.desc;

        // 3. Geopolitik
        const gBadge = document.getElementById('badge-geo-delay');
        gBadge.innerText = '+' + data.factors.geopolitics.delay + ' Hari';
        gBadge.className = 'delay-badge ' + (data.factors.geopolitics.delay >= 5 ? 'delay-red' : (data.factors.geopolitics.delay > 0 ? 'delay-orange' : 'delay-green'));
        document.getElementById('desc-geopolitics').innerText = data.factors.geopolitics.desc;

        // 4. Pelabuhan
        const pBadge = document.getElementById('badge-port-delay');
        pBadge.innerText = '+' + data.factors.port.delay + ' Hari';
        pBadge.className = 'delay-badge ' + (data.factors.port.delay > 0 ? 'delay-red' : 'delay-green');
        document.getElementById('desc-port').innerText = data.factors.port.desc;

        // 5. Inflasi
        const inflation = data.factors.inflation.rate;
        const iBadge = document.getElementById('badge-inflation-rate');
        iBadge.innerText = inflation + '%';
        iBadge.className = 'delay-badge ' + (inflation > 8 ? 'delay-red' : (inflation > 4 ? 'delay-yellow' : 'delay-green'));
        document.getElementById('desc-inflation').innerText = data.factors.inflation.desc;

        // ── Rekomendasi ──────────────────────────────────────────────
        const recCard = document.getElementById('recommendation-card');
        document.getElementById('val-recommendation-text').innerHTML = data.recommendation.text;
        const lvl = data.recommendation.level;
        if (lvl === 'High') {
            recCard.style.cssText = 'border-radius:8px;padding:13px;font-size:12px;line-height:1.6;background:rgba(220,53,69,0.15);border:1px solid #dc3545;color:#dc3545;';
        } else if (lvl === 'Medium') {
            recCard.style.cssText = 'border-radius:8px;padding:13px;font-size:12px;line-height:1.6;background:rgba(255,193,7,0.15);border:1px solid #ffc107;color:#d39e00;';
        } else if (lvl === 'Low-Medium') {
            recCard.style.cssText = 'border-radius:8px;padding:13px;font-size:12px;line-height:1.6;background:rgba(253,126,20,0.15);border:1px solid #fd7e14;color:#fd7e14;';
        } else {
            recCard.style.cssText = 'border-radius:8px;padding:13px;font-size:12px;line-height:1.6;background:rgba(40,167,69,0.15);border:1px solid #28a745;color:#28a745;';
        }

        // ── Peta: Hapus layer lama ────────────────────────────────────
        if (originMarker) map.removeLayer(originMarker);
        if (destMarker)   map.removeLayer(destMarker);
        if (seaRouteLine) map.removeLayer(seaRouteLine);
        if (shipMarker)   map.removeLayer(shipMarker);
        if (animationTimer) clearInterval(animationTimer);

        const oLat = parseFloat(data.origin.latitude);
        const oLon = parseFloat(data.origin.longitude);
        const dLat = parseFloat(data.destination.latitude);
        const dLon = parseFloat(data.destination.longitude);

        // Icon kustom negara asal (merah)
        const originIcon = L.divIcon({
            html: `<div style="background:#dc3545;width:14px;height:14px;border-radius:50%;border:3px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.4);"></div>`,
            iconSize: [14, 14], iconAnchor: [7, 7], className: ''
        });
        // Icon kustom negara tujuan (hijau)
        const destIcon = L.divIcon({
            html: `<div style="background:#28a745;width:14px;height:14px;border-radius:50%;border:3px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.4);"></div>`,
            iconSize: [14, 14], iconAnchor: [7, 7], className: ''
        });

        originMarker = L.marker([oLat, oLon], { icon: originIcon }).addTo(map)
            .bindPopup(`<b>📍 ${data.origin.name}</b><br><small>${data.origin.port_name}</small>`)
            .openPopup();

        destMarker = L.marker([dLat, dLon], { icon: destIcon }).addTo(map)
            .bindPopup(`<b>🏁 ${data.destination.name}</b><br><small>${data.destination.port_name}</small>`);

        // Garis rute laut
        const waypoints = data.route.waypoints;
        seaRouteLine = L.polyline(waypoints, {
            color: '#5b6ef5',
            weight: 3,
            dashArray: '10, 6',
            lineCap: 'round',
            opacity: 0.85
        }).addTo(map);

        // Fit map ke bounds rute
        map.fitBounds(seaRouteLine.getBounds(), { padding: [50, 50] });
        map.invalidateSize();

        // ── Animasi kapal berdasarkan posisi WAKTU NYATA ──────────────
        // Kapal berada di posisi sesuai persentase waktu yang sudah berlalu
        const shipIcon = L.divIcon({
            html: '<div style="font-size:22px;line-height:1;filter:drop-shadow(0 2px 4px rgba(0,0,0,0.4));">🚢</div>',
            iconSize: [28, 28], iconAnchor: [14, 14], className: ''
        });

        const startedAt        = new Date(data.started_at);
        const totalDurationMs  = data.eta.risk_adjusted_days * 24 * 60 * 60 * 1000;
        const totalPoints      = waypoints.length;

        // Hitung posisi kapal saat ini berdasarkan waktu
        function getShipPositionIndex() {
            const now       = new Date();
            const elapsedMs = now - startedAt;
            const pct       = Math.min(1, Math.max(0, elapsedMs / totalDurationMs));
            return Math.floor(pct * (totalPoints - 1));
        }

        const initialIdx = getShipPositionIndex();
        shipMarker = L.marker(waypoints[initialIdx], { icon: shipIcon, zIndexOffset: 1000 }).addTo(map);
        shipMarker.bindTooltip(`🚢 Posisi Kapal<br><small>Progres: ${(getShipPositionIndex() / (totalPoints-1) * 100).toFixed(1)}%</small>`, {
            permanent: false, direction: 'top'
        });

        // Update posisi kapal setiap 30 detik (mengikuti waktu nyata)
        if (animationTimer) clearInterval(animationTimer);
        animationTimer = setInterval(() => {
            const idx = getShipPositionIndex();
            if (idx < totalPoints) {
                shipMarker.setLatLng(waypoints[idx]);
                const pct = (idx / (totalPoints - 1) * 100).toFixed(1);
                shipMarker.setTooltipContent(`🚢 Posisi Kapal<br><small>Progres: ${pct}%</small>`);
            } else {
                // Sudah sampai tujuan
                clearInterval(animationTimer);
                shipMarker.setLatLng(waypoints[totalPoints - 1]);
                shipMarker.setTooltipContent('🏁 Kapal sudah tiba di tujuan!');
            }
        }, 30000);

        // Scroll ke hasil
        document.getElementById('results-wrapper').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // ── Load rute pengiriman aktif ke peta ─────────────────────
    async function loadActiveShipmentRoute(shipmentId, oLat, oLon, dLat, dLon, originName, destName, originPort, destPort, totalDays, startedAt, delayDays) {
        // Hapus layer lama
        if (originMarker) map.removeLayer(originMarker);
        if (destMarker)   map.removeLayer(destMarker);
        if (seaRouteLine) map.removeLayer(seaRouteLine);
        if (shipMarker)   map.removeLayer(shipMarker);
        if (animationTimer) clearInterval(animationTimer);

        document.getElementById('map-status-badge').innerHTML =
            `🚢 ${originName} → ${destName} — memuat rute laut...`;
        document.getElementById('map-simulator').scrollIntoView({ behavior: 'smooth', block: 'center' });

        // Ambil rute laut yang SEBENARNYA (mengikuti jalur pelayaran, bukan garis lurus)
        // beserta koordinat pelabuhan persis dari endpoint show() — bukan koordinat negara.
        try {
            const res = await fetch(`/api/route-simulator/${shipmentId}`, {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) throw new Error(`Server merespons status ${res.status}`);

            const result    = await res.json();
            const waypoints = result.data.waypoints;
            const oPoint    = result.data.origin;
            const dPoint    = result.data.destination;

            // Icon asal (merah) & tujuan (hijau) — digambar di koordinat pelabuhan yang akurat
            const originIcon = L.divIcon({
                html: `<div style="background:#dc3545;width:14px;height:14px;border-radius:50%;border:3px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.4);"></div>`,
                iconSize: [14,14], iconAnchor: [7,7], className: ''
            });
            const destIcon = L.divIcon({
                html: `<div style="background:#28a745;width:14px;height:14px;border-radius:50%;border:3px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.4);"></div>`,
                iconSize: [14,14], iconAnchor: [7,7], className: ''
            });

            originMarker = L.marker([oPoint.latitude, oPoint.longitude], { icon: originIcon }).addTo(map)
                .bindPopup(`<b>📍 ${originName}</b><br><small>${originPort}</small>`).openPopup();
            destMarker = L.marker([dPoint.latitude, dPoint.longitude], { icon: destIcon }).addTo(map)
                .bindPopup(`<b>🏁 ${destName}</b><br><small>${destPort}</small>`);

            seaRouteLine = L.polyline(waypoints, {
                color: '#5b6ef5', weight: 3,
                dashArray: '10, 6', lineCap: 'round', opacity: 0.85
            }).addTo(map);

            map.fitBounds(seaRouteLine.getBounds(), { padding: [60, 60] });
            map.invalidateSize();

            // Posisi kapal dihitung dari titik-titik rute laut asli (ikut alur pelayaran),
            // bukan interpolasi garis lurus — supaya kapal tidak "memotong" daratan.
            const startMs = new Date(startedAt).getTime();
            const totalMs = totalDays * 86400000;
            const elapsed = Date.now() - startMs;
            const pct     = Math.min(1, Math.max(0, elapsed / totalMs));
            const idx     = Math.floor(pct * (waypoints.length - 1));

            const shipIcon = L.divIcon({
                html: '<div style="font-size:22px;filter:drop-shadow(0 2px 4px rgba(0,0,0,0.3));">🚢</div>',
                iconSize: [28,28], iconAnchor: [14,14], className: ''
            });

            shipMarker = L.marker(waypoints[idx], { icon: shipIcon, zIndexOffset: 1000 }).addTo(map);
            shipMarker.bindTooltip(
                `🚢 ${originName} → ${destName}<br>Progres: ${(pct*100).toFixed(1)}%<br>ETA: ${totalDays} hari`,
                { permanent: false, direction: 'top' }
            );

            document.getElementById('map-status-badge').innerHTML =
                `🚢 ${originName} → ${destName} (Pengiriman Aktif)`;
        } catch (e) {
            console.error('loadActiveShipmentRoute gagal ambil rute:', e);
            document.getElementById('map-status-badge').innerHTML =
                `⚠️ Gagal memuat rute ${originName} → ${destName}`;
        }
    }
    let currentShipmentId = null;

    function openCompleteModal(id, routeLabel) {
        currentShipmentId = id;
        document.getElementById('modal-route-label').textContent = '📦 ' + routeLabel;
        document.getElementById('complete-notes').value = '';
        document.getElementById('completeModal').classList.add('open');
    }

    function closeCompleteModal() {
        document.getElementById('completeModal').classList.remove('open');
        currentShipmentId = null;
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('completeModal')?.addEventListener('click', function(e) {
            if (e.target === this) closeCompleteModal();
        });
    });

    async function submitComplete() {
        if (!currentShipmentId) return;
        const notes = document.getElementById('complete-notes').value;
        const CSRF  = document.querySelector('meta[name="csrf-token"]').content;
        try {
            const res  = await fetch(`/api/route-simulator/${currentShipmentId}/complete`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ notes })
            });
            const data = await res.json();
            if (data.status === 'success') {
                closeCompleteModal();
                window.location.reload();
            } else {
                alert(data.message || 'Gagal menandai selesai.');
            }
        } catch(e) {
            alert('Terjadi kesalahan koneksi.');
        }
    }
</script>
@endpush