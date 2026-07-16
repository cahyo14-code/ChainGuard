@extends('layouts.app')

@section('title', 'Global Countries')
@section('page-title', 'Global Countries')

@section('content')

<div class="page-header">
    <h2>🌍 Global Country Dashboard</h2>
    <p>Pantau kondisi risiko supply chain per negara</p>
</div>

{{-- Filter --}}
<div class="card-custom mb-4">
    <div class="row g-3">
        <div class="col-md-4">
            <input type="text" id="searchInput" class="form-control-custom" style="width: 100%;"
                placeholder="🔍 Cari negara...">
        </div>
        <div class="col-md-3">
            <select id="regionFilter" class="form-control-custom" style="width: 100%;">
                <option value="">Semua Region</option>
                @foreach($regions as $region)
                    <option value="{{ $region }}">{{ $region }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select id="riskFilter" class="form-control-custom" style="width: 100%;">
                <option value="">Semua Risk Level</option>
                <option value="High">High Risk</option>
                <option value="Medium">Medium Risk</option>
                <option value="Low">Low Risk</option>
            </select>
        </div>
    </div>
</div>

{{-- Tabel Negara --}}
<div class="card-custom">
    <table class="table table-custom" id="countriesTable">
        <thead>
            <tr>
                <th>Negara</th>
                <th>Region</th>
                <th>Mata Uang</th>
                <th>Populasi</th>
                <th>Risk Score</th>
                <th>Risk Level</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($countries as $country)
            <tr class="country-row"
                data-name="{{ strtolower($country->name) }}"
                data-region="{{ $country->region }}"
                data-risk="{{ $country->riskScore->risk_level ?? '' }}">
                <td>
                    <div class="d-flex align-items-center gap-2">
                        @if($country->flag_url)
                            <img src="{{ $country->flag_url }}" style="width: 24px; height: 16px; object-fit: cover; border-radius: 2px;">
                        @endif
                        <strong>{{ $country->name }}</strong>
                        <small style="color: var(--text-secondary)">({{ $country->code }})</small>
                    </div>
                </td>
                <td style="color: var(--text-secondary)">{{ $country->region ?? '-' }}</td>
                <td style="color: var(--text-secondary)">{{ $country->currency_code ?? '-' }}</td>
                <td style="color: var(--text-secondary)">
                    {{ $country->population ? number_format($country->population) : '-' }}
                </td>
                <td>
                    <strong>{{ $country->riskScore->total_risk ?? '-' }}</strong>
                </td>
                <td>
                    @if($country->riskScore)
                        @if($country->riskScore->risk_level === 'High')
                            <span class="badge-high">High</span>
                        @elseif($country->riskScore->risk_level === 'Medium')
                            <span class="badge-medium">Medium</span>
                        @else
                            <span class="badge-low">Low</span>
                        @endif
                    @else
                        <span style="color: var(--text-secondary)">-</span>
                    @endif
                </td>
                <td>
                    <a href="{{ route('countries.show', $country->code) }}"
                        class="btn-accent" style="padding: 5px 12px; font-size: 12px; text-decoration: none;">
                        Detail
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Pagination --}}
    <div class="mt-3" style="color: var(--text-secondary);">
        {{ $countries->links() }}
    </div>
</div>

@endsection

@push('scripts')
<script>
// Filter pencarian
document.getElementById('searchInput').addEventListener('keyup', filterTable);
document.getElementById('regionFilter').addEventListener('change', filterTable);
document.getElementById('riskFilter').addEventListener('change', filterTable);

function filterTable() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const region = document.getElementById('regionFilter').value;
    const risk   = document.getElementById('riskFilter').value;

    document.querySelectorAll('.country-row').forEach(row => {
        const nameMatch   = row.dataset.name.includes(search);
        const regionMatch = !region || row.dataset.region === region;
        const riskMatch   = !risk || row.dataset.risk === risk;

        row.style.display = (nameMatch && regionMatch && riskMatch) ? '' : 'none';
    });
}
</script>
@endpush