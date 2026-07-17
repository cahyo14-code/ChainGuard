@extends('layouts.app')

@section('title', 'Watchlist')
@section('page-title', 'Favorite Monitoring List')

@push('styles')
<style>
.wl-card {
    background:var(--bg-card); border:1px solid var(--border-color);
    border-radius:10px; padding:18px; margin-bottom:14px;
    transition: border-color 0.2s, transform 0.15s;
}
.wl-card:hover { border-color:var(--accent-light); transform:translateY(-1px); }
.flag-img { width:28px; height:18px; object-fit:cover; border-radius:3px; margin-right:10px; }
.country-name { font-size:16px; font-weight:700; color:var(--text-primary); text-decoration:none; }
.country-name:hover { color:var(--accent-light); }
.meta-row { display:flex; gap:16px; flex-wrap:wrap; font-size:12px; color:var(--text-secondary); margin-top:6px; }
.meta-row span { display:flex; align-items:center; gap:4px; }
.btn-remove {
    background:rgba(220,53,69,0.15); border:1px solid rgba(220,53,69,0.3);
    color:#dc3545; padding:5px 12px; border-radius:6px;
    font-size:12px; cursor:pointer; transition:all 0.2s;
}
.btn-remove:hover { background:rgba(220,53,69,0.3); }
.btn-add {
    background:var(--accent); border:none; color:white;
    padding:8px 18px; border-radius:8px; font-size:13px;
    font-weight:600; cursor:pointer; transition:all 0.2s;
}
.btn-add:hover { background:var(--accent-light); }
.modal-custom {
    display:none; position:fixed; inset:0; z-index:9999;
    background:rgba(0,0,0,0.6); align-items:center; justify-content:center;
}
.modal-custom.open { display:flex; }
.modal-box {
    background:var(--bg-card); border:1px solid var(--border-color);
    border-radius:12px; padding:28px; width:100%; max-width:500px;
    margin:20px;
}
.modal-box h5 { color:var(--text-primary); margin-bottom:20px; }
.form-input {
    background:var(--bg-secondary); border:1px solid var(--border-color);
    color:var(--text-primary); border-radius:8px; padding:10px 14px;
    font-size:13px; width:100%; margin-bottom:12px;
}
.form-input:focus { outline:none; border-color:var(--accent-light); }
.form-input option { background:var(--bg-secondary); }
.empty-state { text-align:center; padding:80px 20px; color:var(--text-secondary); }
.empty-state i { font-size:48px; opacity:0.15; display:block; margin-bottom:16px; }
</style>
@endpush

@section('content')

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h2>⭐ Favorite Monitoring List</h2>
        <p>Pantau negara-negara pilihan kamu secara real-time</p>
    </div>
    <button class="btn-add" onclick="openModal()">
        <i class="fas fa-plus"></i> Tambah Negara
    </button>
</div>

{{-- Flash messages --}}
@if(session('success'))
<div style="background:rgba(40,167,69,0.15); border:1px solid rgba(40,167,69,0.3); color:#28a745; border-radius:8px; padding:12px 16px; margin-bottom:16px; font-size:13px;">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif
@if(session('info'))
<div style="background:rgba(255,193,7,0.15); border:1px solid rgba(255,193,7,0.3); color:#ffc107; border-radius:8px; padding:12px 16px; margin-bottom:16px; font-size:13px;">
    <i class="fas fa-info-circle"></i> {{ session('info') }}
</div>
@endif

{{-- Daftar watchlist --}}
@if($watchlistData->isEmpty())
<div class="empty-state">
    <i class="fas fa-star"></i>
    <h4 style="color:var(--text-primary); margin-bottom:8px">Watchlist masih kosong</h4>
    <p>Tambahkan negara yang ingin kamu pantau kondisi rantai pasoknya.</p>
    <button class="btn-add mt-3" onclick="openModal()">
        <i class="fas fa-plus"></i> Tambah Negara Pertama
    </button>
</div>
@else

{{-- Summary bar --}}
<div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:10px; padding:14px 18px; margin-bottom:20px; display:flex; gap:20px; flex-wrap:wrap; font-size:13px;">
    <span style="color:var(--text-secondary)">
        <i class="fas fa-list"></i>
        <strong style="color:var(--text-primary)">{{ $watchlistData->count() }}</strong> negara dipantau
    </span>
    <span>
        <span class="badge-high" style="font-size:11px">{{ $watchlistData->where('risk_level', 'High')->count() }} High</span>
    </span>
    <span>
        <span class="badge-medium" style="font-size:11px">{{ $watchlistData->where('risk_level', 'Medium')->count() }} Medium</span>
    </span>
    <span>
        <span class="badge-low" style="font-size:11px">{{ $watchlistData->where('risk_level', 'Low')->count() }} Low</span>
    </span>
</div>

@foreach($watchlistData as $item)
<div class="wl-card">
    <div class="d-flex justify-content-between align-items-start">
        <div class="d-flex align-items-center">
            @if($item['country']?->flag_url)
                <img class="flag-img" src="{{ $item['country']->flag_url }}" alt="">
            @endif
            <div>
                <a href="{{ route('countries.show', $item['country']?->code) }}" class="country-name">
                    {{ $item['country']?->name ?? '-' }}
                </a>
                <div class="meta-row">
                    <span><i class="fas fa-map-marker-alt"></i>{{ $item['country']?->capital ?? '-' }}</span>
                    <span><i class="fas fa-globe"></i>{{ $item['country']?->region ?? '-' }}</span>
                    <span><i class="fas fa-money-bill"></i>{{ $item['currency_code'] }}</span>
                    @if($item['currency_rate'] !== '-')
                    <span>1 USD = {{ number_format($item['currency_rate'], 2) }}</span>
                    @endif
                    <span><i class="fas fa-clock"></i>Ditambahkan {{ $item['added_at']?->diffForHumans() }}</span>
                </div>
            </div>
        </div>

        {{-- Risk badge & tombol aksi --}}
        <div class="d-flex align-items-center gap-2">
            {{-- Total risk --}}
            <div style="text-align:center">
                <div style="font-size:20px; font-weight:800; color:
                    @if($item['risk_level'] === 'High') #dc3545
                    @elseif($item['risk_level'] === 'Medium') #ffc107
                    @else #28a745 @endif">
                    {{ $item['total_risk'] }}
                </div>
                @if($item['risk_level'] === 'High')
                    <span class="badge-high" style="font-size:10px">High</span>
                @elseif($item['risk_level'] === 'Medium')
                    <span class="badge-medium" style="font-size:10px">Medium</span>
                @else
                    <span class="badge-low" style="font-size:10px">Low</span>
                @endif
            </div>

            <a href="{{ route('risk.show', $item['country']?->code) }}"
               style="background:rgba(58,138,82,0.15); border:1px solid rgba(58,138,82,0.3); color:var(--accent-light); padding:5px 10px; border-radius:6px; font-size:12px; text-decoration:none;">
                <i class="fas fa-chart-line"></i> Risk Detail
            </a>

            <form action="{{ route('watchlist.destroy', $item['country']) }}" method="POST" style="margin:0"
                  onsubmit="return confirm('Hapus {{ $item['country']?->name }} dari watchlist?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-remove">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </form>
        </div>
    </div>

    {{-- Info cuaca & notes --}}
    <div class="d-flex gap-3 mt-3 flex-wrap" style="font-size:12px; color:var(--text-secondary);">
        <span>
            🌦 Cuaca: <strong style="color:var(--text-primary)">{{ $item['weather_condition'] }}</strong>
        </span>
        <span>
            Risiko Cuaca:
            @if($item['weather_risk'] === 'High')
                <span class="badge-high" style="font-size:10px">High</span>
            @elseif($item['weather_risk'] === 'Medium')
                <span class="badge-medium" style="font-size:10px">Medium</span>
            @elseif($item['weather_risk'] === 'Low')
                <span class="badge-low" style="font-size:10px">Low</span>
            @else
                <span style="color:var(--text-secondary)">-</span>
            @endif
        </span>
        @if($item['notes'])
        <span>📝 {{ $item['notes'] }}</span>
        @endif
    </div>
</div>
@endforeach
@endif

{{-- Modal Tambah --}}
<div class="modal-custom" id="addModal">
    <div class="modal-box">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 style="margin:0"><i class="fas fa-plus-circle" style="color:var(--accent-light)"></i> Tambah ke Watchlist</h5>
            <button onclick="closeModal()" style="background:none; border:none; color:var(--text-secondary); font-size:18px; cursor:pointer;">×</button>
        </div>
        <form action="" method="POST" id="addWatchlistForm">
            @csrf
            <div style="margin-bottom:12px;">
                <label style="font-size:11px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:6px;">Pilih Negara</label>
                <select name="country_select" id="countrySelect" class="form-input" onchange="updateFormAction(this.value)">
                    <option value="">— Pilih Negara —</option>
                    @foreach($allCountries as $c)
                    <option value="{{ $c->id }}" data-code="{{ $c->code }}"
                        {{ in_array($c->id, $watchlistedIds) ? 'disabled' : '' }}>
                        {{ $c->name }} ({{ $c->currency_code ?? $c->code }})
                        {{ in_array($c->id, $watchlistedIds) ? '✓ Sudah ada' : '' }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div style="margin-bottom:16px;">
                <label style="font-size:11px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:6px;">Catatan (opsional)</label>
                <input type="text" name="notes" class="form-input" placeholder="Misalnya: supplier utama bahan baku">
            </div>
            <button type="submit" class="btn-add w-100" id="addBtn" disabled>
                <i class="fas fa-star"></i> Tambahkan ke Watchlist
            </button>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openModal()  { document.getElementById('addModal').classList.add('open'); }
function closeModal() { document.getElementById('addModal').classList.remove('open'); }

// Tutup modal klik di luar
document.getElementById('addModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

function updateFormAction(countryId) {
    const select  = document.getElementById('countrySelect');
    const option  = select.options[select.selectedIndex];
    const code    = option.dataset.code;
    const btn     = document.getElementById('addBtn');
    const form    = document.getElementById('addWatchlistForm');

    if (code) {
        form.action = `/watchlist/${countryId}`;
        btn.disabled = false;
    } else {
        btn.disabled = true;
    }
}
</script>
@endpush
