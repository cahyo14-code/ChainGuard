@extends('layouts.app')

@section('title', $country->name)
@section('page-title', 'Detail Negara')

@section('content')

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h2>
            @if($country->flag_url)
                <img src="{{ $country->flag_url }}" style="width: 32px; height: 22px; object-fit: cover; border-radius: 3px; margin-right: 8px;">
            @endif
            {{ $country->name }}
        </h2>
        <p>{{ $country->region }} — {{ $country->subregion }}</p>
    </div>
    <div class="d-flex gap-2">
        {{-- Watchlist Button --}}
        <form action="{{ $isWatchlisted ? route('watchlist.destroy', $country->code) : route('watchlist.store', $country->code) }}"
              method="POST">
            @csrf
            @if($isWatchlisted)
                @method('DELETE')
                <button type="submit" class="btn-accent" style="background: #dc3545; border-color: #dc3545;">
                    <i class="fas fa-star"></i> Hapus dari Watchlist
                </button>
            @else
                <button type="submit" class="btn-accent">
                    <i class="far fa-star"></i> Tambah ke Watchlist
                </button>
            @endif
        </form>
        <a href="{{ route('countries.index') }}" class="btn-accent" style="background: transparent; border: 1px solid var(--border-color); text-decoration: none;">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="row">
    {{-- Info Negara --}}
    <div class="col-md-4">
        <div class="card-custom">
            <div class="card-title">Informasi Negara</div>
            <table style="width: 100%; font-size: 14px;">
                <tr style="border-bottom: 1px solid rgba(40,98,58,0.2);">
                    <td style="padding: 8px 0; color: var(--text-secondary);">Kode</td>
                    <td style="padding: 8px 0; color: var(--text-primary);">{{ $country->code }}</td>
                </tr>
                <tr style="border-bottom: 1px solid rgba(40,98,58,0.2);">
                    <td style="padding: 8px 0; color: var(--text-secondary);">Ibu Kota</td>
                    <td style="padding: 8px 0; color: var(--text-primary);">{{ $country->capital ?? '-' }}</td>
                </tr>
                <tr style="border-bottom: 1px solid rgba(40,98,58,0.2);">
                    <td style="padding: 8px 0; color: var(--text-secondary);">Region</td>
                    <td style="padding: 8px 0; color: var(--text-primary);">{{ $country->region ?? '-' }}</td>
                </tr>
                <tr style="border-bottom: 1px solid rgba(40,98,58,0.2);">
                    <td style="padding: 8px 0; color: var(--text-secondary);">Mata Uang</td>
                    <td style="padding: 8px 0; color: var(--text-primary);">{{ $country->currency_code }} — {{ $country->currency_name ?? '-' }}</td>
                </tr>
                <tr style="border-bottom: 1px solid rgba(40,98,58,0.2);">
                    <td style="padding: 8px 0; color: var(--text-secondary);">Populasi</td>
                    <td style="padding: 8px 0; color: var(--text-primary);">
                        {{ $country->population ? number_format($country->population) : '-' }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: var(--text-secondary);">Koordinat</td>
                    <td style="padding: 8px 0; color: var(--text-primary);">{{ $country->latitude }}, {{ $country->longitude }}</td>
                </tr>
            </table>
        </div>

        {{-- Cuaca --}}
        <div class="card-custom">
            <div class="card-title">🌤 Kondisi Cuaca</div>
            @if($weather)
                <div style="font-size: 32px; font-weight: 700; color: var(--text-primary);">
                    {{ $weather->temperature }}°C
                </div>
                <div style="color: var(--text-secondary); margin-bottom: 15px;">{{ $weather->weather_condition }}</div>
                <table style="width: 100%; font-size: 13px;">
                    <tr>
                        <td style="padding: 5px 0; color: var(--text-secondary);">Curah Hujan</td>
                        <td style="color: var(--text-primary);">{{ $weather->rainfall }} mm</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0; color: var(--text-secondary);">Kecepatan Angin</td>
                        <td style="color: var(--text-primary);">{{ $weather->wind_speed }} km/h</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0; color: var(--text-secondary);">Risiko Badai</td>
                        <td style="color: var(--text-primary);">{{ $weather->storm_risk ? '⚠️ Ya' : '✅ Tidak' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0; color: var(--text-secondary);">Risk Level</td>
                        <td>
                            @if($weather->risk_level === 'High')
                                <span class="badge-high">High</span>
                            @elseif($weather->risk_level === 'Medium')
                                <span class="badge-medium">Medium</span>
                            @else
                                <span class="badge-low">Low</span>
                            @endif
                        </td>
                    </tr>
                </table>
            @else
                <p style="color: var(--text-secondary); font-size: 13px;">Data cuaca tidak tersedia</p>
            @endif
        </div>

        {{-- Kurs --}}
        <div class="card-custom">
            <div class="card-title">💱 Nilai Tukar</div>
            @if($currencyRate)
                <div style="font-size: 22px; font-weight: 700; color: var(--accent-light);">
                    1 USD = {{ number_format($currencyRate->rate, 2) }} {{ $currencyRate->target_currency }}
                </div>
                <small style="color: var(--text-secondary);">
                    Update: {{ $currencyRate->fetched_at ? $currencyRate->fetched_at->diffForHumans() : '-' }}
                </small>
            @else
                <p style="color: var(--text-secondary); font-size: 13px;">Data kurs tidak tersedia</p>
            @endif
        </div>
    </div>

    {{-- Risk Score & Ekonomi --}}
    <div class="col-md-8">
        {{-- Risk Score --}}
        @if($riskScore)
        <div class="card-custom">
            <div class="card-title">⚠️ Risk Assessment</div>
            <div class="row mb-3">
                <div class="col-md-3 text-center">
                    <div style="font-size: 42px; font-weight: 700;
                        color: {{ $riskScore->risk_level === 'High' ? '#dc3545' : ($riskScore->risk_level === 'Medium' ? '#ffc107' : '#28a745') }}">
                        {{ $riskScore->total_risk }}
                    </div>
                    <div>
                        @if($riskScore->risk_level === 'High')
                            <span class="badge-high">High Risk</span>
                        @elseif($riskScore->risk_level === 'Medium')
                            <span class="badge-medium">Medium Risk</span>
                        @else
                            <span class="badge-low">Low Risk</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-9">
                    {{-- Weather Risk --}}
                    <div class="mb-2">
                        <div class="d-flex justify-content-between mb-1">
                            <small style="color: var(--text-secondary);">🌧 Cuaca (30%)</small>
                            <small style="color: var(--text-primary);">{{ $riskScore->weather_risk }}/100</small>
                        </div>
                        <div style="background: rgba(40,98,58,0.2); border-radius: 4px; height: 6px;">
                            <div style="background: #28a745; height: 6px; border-radius: 4px; width: {{ $riskScore->weather_risk }}%;"></div>
                        </div>
                        <small style="color: var(--text-secondary); font-size: 11px;">{{ $riskScore->weather_description }}</small>
                    </div>
                    {{-- Inflation Risk --}}
                    <div class="mb-2">
                        <div class="d-flex justify-content-between mb-1">
                            <small style="color: var(--text-secondary);">📈 Inflasi (20%)</small>
                            <small style="color: var(--text-primary);">{{ $riskScore->inflation_risk }}/100</small>
                        </div>
                        <div style="background: rgba(40,98,58,0.2); border-radius: 4px; height: 6px;">
                            <div style="background: #ffc107; height: 6px; border-radius: 4px; width: {{ $riskScore->inflation_risk }}%;"></div>
                        </div>
                        <small style="color: var(--text-secondary); font-size: 11px;">{{ $riskScore->inflation_description }}</small>
                    </div>
                    {{-- News Risk --}}
                    <div class="mb-2">
                        <div class="d-flex justify-content-between mb-1">
                            <small style="color: var(--text-secondary);">📰 Berita (40%)</small>
                            <small style="color: var(--text-primary);">{{ $riskScore->news_risk }}/100</small>
                        </div>
                        <div style="background: rgba(40,98,58,0.2); border-radius: 4px; height: 6px;">
                            <div style="background: #dc3545; height: 6px; border-radius: 4px; width: {{ $riskScore->news_risk }}%;"></div>
                        </div>
                        <small style="color: var(--text-secondary); font-size: 11px;">{{ $riskScore->news_description }}</small>
                    </div>
                    {{-- Currency Risk --}}
                    <div class="mb-2">
                        <div class="d-flex justify-content-between mb-1">
                            <small style="color: var(--text-secondary);">💱 Kurs (10%)</small>
                            <small style="color: var(--text-primary);">{{ $riskScore->currency_risk }}/100</small>
                        </div>
                        <div style="background: rgba(40,98,58,0.2); border-radius: 4px; height: 6px;">
                            <div style="background: #17a2b8; height: 6px; border-radius: 4px; width: {{ $riskScore->currency_risk }}%;"></div>
                        </div>
                        <small style="color: var(--text-secondary); font-size: 11px;">{{ $riskScore->currency_description }}</small>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Data Ekonomi --}}
        <div class="card-custom">
            <div class="card-title">📊 Data Ekonomi (5 Tahun Terakhir)</div>
            @if($economicData->isNotEmpty())
            <table class="table table-custom">
                <thead>
                    <tr>
                        <th>Tahun</th>
                        <th>GDP (USD)</th>
                        <th>Inflasi (%)</th>
                        <th>Populasi</th>
                        <th>Ekspor (USD)</th>
                        <th>Impor (USD)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($economicData as $data)
                    <tr>
                        <td>{{ $data->year }}</td>
                        <td>{{ $data->gdp ? '$' . number_format($data->gdp / 1e9, 1) . 'B' : '-' }}</td>
                        <td>{{ $data->inflation ? $data->inflation . '%' : '-' }}</td>
                        <td>{{ $data->population ? number_format($data->population) : '-' }}</td>
                        <td>{{ $data->exports ? '$' . number_format($data->exports / 1e9, 1) . 'B' : '-' }}</td>
                        <td>{{ $data->imports ? '$' . number_format($data->imports / 1e9, 1) . 'B' : '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
                <p style="color: var(--text-secondary); font-size: 13px;">Data ekonomi tidak tersedia</p>
            @endif
        </div>

        {{-- Berita --}}
        <div class="card-custom">
            <div class="card-title">📰 Berita Terkini</div>
            @if($news->isNotEmpty())
                @foreach($news as $item)
                <div style="border-bottom: 1px solid rgba(40,98,58,0.2); padding: 12px 0;">
                    <div class="d-flex justify-content-between align-items-start">
                        <div style="flex: 1;">
                            <a href="{{ $item->url }}" target="_blank"
                               style="color: var(--text-primary); text-decoration: none; font-size: 14px; font-weight: 500;">
                                {{ $item->title }}
                            </a>
                            <div style="color: var(--text-secondary); font-size: 12px; margin-top: 4px;">
                                {{ Str::limit($item->description, 100) }}
                            </div>
                            <small style="color: var(--text-secondary);">
                                {{ $item->source }} —
                                {{ $item->published_at ? $item->published_at->diffForHumans() : '-' }}
                            </small>
                        </div>
                        <div class="ms-3">
                            @if($item->sentiment === 'Positive')
                                <span class="badge-low" style="font-size: 11px;">Positive</span>
                            @elseif($item->sentiment === 'Negative')
                                <span class="badge-high" style="font-size: 11px;">Negative</span>
                            @else
                                <span class="badge-medium" style="font-size: 11px;">Neutral</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            @else
                <p style="color: var(--text-secondary); font-size: 13px;">Tidak ada berita untuk negara ini</p>
            @endif
        </div>
    </div>
</div>

@endsection