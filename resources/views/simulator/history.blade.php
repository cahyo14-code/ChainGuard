@extends('layouts.app')

@section('title', 'Histori Pengiriman')
@section('page-title', '📦 Histori Pengiriman')

@push('styles')
<style>
.history-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 18px 20px;
    margin-bottom: 14px;
    transition: box-shadow 0.2s;
}
.history-card:hover { box-shadow: var(--shadow-md); }
.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.status-active    { background: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe; }
.status-completed { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.route-arrow { color: var(--accent); font-size: 16px; margin: 0 8px; }
.flag-sm { width: 22px; height: 14px; object-fit: cover; border-radius: 2px; margin-right: 6px; }
.info-pill {
    display: inline-flex; align-items: center; gap: 5px;
    background: #f8fafc; border: 1px solid var(--border-color);
    padding: 4px 10px; border-radius: 20px; font-size: 12px;
    color: var(--text-secondary);
}
.factor-tag {
    display: inline-block;
    font-size: 11px; padding: 2px 8px;
    border-radius: 10px; margin-right: 4px; margin-top: 4px;
}
.tag-ok      { background: #dcfce7; color: #15803d; }
.tag-warning { background: #fef9c3; color: #854d0e; }
.tag-danger  { background: #fee2e2; color: #b91c1c; }
</style>
@endpush

@section('content')

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h2>📦 Histori Pengiriman</h2>
        <p>Riwayat semua simulasi pengiriman yang pernah kamu buat</p>
    </div>
    <a href="{{ route('simulator.index') }}" class="btn-accent" style="padding: 8px 18px; font-size: 13px; text-decoration: none;">
        <i class="fas fa-plus"></i> Simulasi Baru
    </a>
</div>

@if(session('success'))
<div style="background:#dcfce7; border:1px solid #bbf7d0; color:#15803d; border-radius:8px; padding:12px 16px; margin-bottom:16px; font-size:13px;">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif

@if($shipments->isEmpty())
<div style="text-align:center; padding: 80px 20px; color: var(--text-secondary);">
    <i class="fas fa-ship" style="font-size: 48px; opacity: 0.15; display: block; margin-bottom: 16px;"></i>
    <h4 style="color: var(--text-primary); margin-bottom: 8px;">Belum ada histori pengiriman</h4>
    <p>Buat simulasi pengiriman pertama kamu sekarang.</p>
    <a href="{{ route('simulator.index') }}" class="btn-accent" style="display: inline-block; margin-top: 16px; padding: 10px 24px; text-decoration: none;">
        <i class="fas fa-route"></i> Mulai Simulasi
    </a>
</div>
@else

@foreach($shipments as $shipment)
<div class="history-card">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">

        {{-- Rute --}}
        <div>
            <div class="d-flex align-items-center mb-1">
                @if($shipment->originCountry?->flag_url)
                    <img class="flag-sm" src="{{ $shipment->originCountry->flag_url }}" alt="">
                @endif
                <strong style="font-size: 15px; color: var(--text-primary);">{{ $shipment->originCountry?->name }}</strong>
                <span class="route-arrow"><i class="fas fa-arrow-right"></i></span>
                @if($shipment->destinationCountry?->flag_url)
                    <img class="flag-sm" src="{{ $shipment->destinationCountry->flag_url }}" alt="">
                @endif
                <strong style="font-size: 15px; color: var(--text-primary);">{{ $shipment->destinationCountry?->name }}</strong>
                <span class="ms-2">
                    @if($shipment->status === 'active')
                        <span class="status-badge status-active">🚢 Aktif</span>
                    @else
                        <span class="status-badge status-completed">✅ Selesai</span>
                    @endif
                </span>
            </div>

            {{-- Info pills --}}
            <div class="d-flex flex-wrap gap-2 mt-2">
                <span class="info-pill"><i class="fas fa-anchor"></i> {{ $shipment->origin_port }}</span>
                <span class="info-pill"><i class="fas fa-route"></i> {{ number_format($shipment->nautical_miles) }} NM</span>
                <span class="info-pill"><i class="fas fa-clock"></i> {{ $shipment->normal_days }} hari (normal)</span>
                @if($shipment->total_delay_days > 0)
                <span class="info-pill" style="background: #fee2e2; border-color: #fecaca; color: #b91c1c;">
                    <i class="fas fa-exclamation-triangle"></i> +{{ $shipment->total_delay_days }} hari keterlambatan
                </span>
                @endif
            </div>

            {{-- Faktor kendala --}}
            @if($shipment->factors)
            <div class="mt-2">
                @php $f = $shipment->factors; @endphp
                <span class="factor-tag {{ ($f['weather']['delay'] ?? 0) > 0 ? 'tag-danger' : 'tag-ok' }}">
                    ⛈ Cuaca {{ ($f['weather']['delay'] ?? 0) > 0 ? '+' . $f['weather']['delay'] . 'h' : 'Aman' }}
                </span>
                <span class="factor-tag {{ abs($f['currency']['impact_pct'] ?? 0) > 3 ? 'tag-danger' : (abs($f['currency']['impact_pct'] ?? 0) > 1.5 ? 'tag-warning' : 'tag-ok') }}">
                    💱 Kurs {{ $f['currency']['impact_pct'] ?? 0 }}%
                </span>
                <span class="factor-tag {{ ($f['geopolitics']['delay'] ?? 0) > 0 ? 'tag-danger' : 'tag-ok' }}">
                    🛡 Geopolitik {{ ($f['geopolitics']['delay'] ?? 0) > 0 ? '+' . $f['geopolitics']['delay'] . 'h' : 'Aman' }}
                </span>
                <span class="factor-tag {{ ($f['port']['delay'] ?? 0) > 0 ? 'tag-warning' : 'tag-ok' }}">
                    ⚓ Pelabuhan {{ ($f['port']['delay'] ?? 0) > 0 ? '+' . $f['port']['delay'] . 'h' : 'Lancar' }}
                </span>
                <span class="factor-tag {{ ($f['inflation']['rate'] ?? 0) > 6 ? 'tag-danger' : (($f['inflation']['rate'] ?? 0) > 3.5 ? 'tag-warning' : 'tag-ok') }}">
                    📈 Inflasi {{ $f['inflation']['rate'] ?? 0 }}%
                </span>
            </div>
            @endif
        </div>

        {{-- ETA & aksi --}}
        <div style="text-align: right; min-width: 180px;">
            <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 4px;">ETA Normal</div>
            <div style="font-size: 14px; font-weight: 600; color: #28a745;">
                {{ $shipment->normal_eta?->format('d M Y') }}
            </div>
            @if($shipment->total_delay_days > 0)
            <div style="font-size: 12px; color: var(--text-secondary); margin-top: 6px; margin-bottom: 2px;">ETA dengan Kendala</div>
            <div style="font-size: 14px; font-weight: 600; color: #dc3545;">
                {{ $shipment->risk_adjusted_eta?->format('d M Y') }}
            </div>
            @endif

            <div style="margin-top: 10px; font-size: 11px; color: var(--text-secondary);">
                Dibuat: {{ $shipment->created_at->format('d M Y H:i') }}
            </div>

            @if($shipment->isCompleted() && $shipment->completed_at)
            <div style="font-size: 11px; color: #15803d; margin-top: 2px;">
                ✅ Selesai: {{ $shipment->completed_at->format('d M Y H:i') }}
            </div>
            @endif

            @if($shipment->notes)
            <div style="font-size: 11px; color: var(--text-secondary); margin-top: 4px; font-style: italic;">
                "{{ Str::limit($shipment->notes, 60) }}"
            </div>
            @endif

            {{-- Tombol hapus --}}
            <div style="margin-top: 12px;">
                <form action="{{ route('simulator.destroy', $shipment) }}" method="POST"
                      onsubmit="return confirm('Hapus pengiriman ini dari histori?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        style="background: #fee2e2; border: 1px solid #fecaca; color: #b91c1c;
                               padding: 5px 12px; border-radius: 8px; font-size: 12px;
                               font-weight: 600; cursor: pointer; transition: all 0.2s;"
                        onmouseover="this.style.background='#b91c1c';this.style.color='white'"
                        onmouseout="this.style.background='#fee2e2';this.style.color='#b91c1c'">
                        <i class="fas fa-trash-alt"></i> Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endforeach

{{-- Pagination --}}
<div class="d-flex justify-content-center mt-3">
    {{ $shipments->links() }}
</div>

@endif
@endsection
