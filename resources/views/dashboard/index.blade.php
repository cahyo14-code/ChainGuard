@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Global Supply Chain Dashboard')

@section('content')

{{-- Statistik Utama --}}
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card-custom text-center">
            <div class="card-title">Total Negara</div>
            <div class="card-value">{{ $totalCountries }}</div>
            <small style="color: var(--text-secondary)">Negara dipantau</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-custom text-center">
            <div class="card-title">Total Pelabuhan</div>
            <div class="card-value">{{ $totalPorts }}</div>
            <small style="color: var(--text-secondary)">Pelabuhan global</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-custom text-center">
            <div class="card-title">Total Berita</div>
            <div class="card-value">{{ $totalNews }}</div>
            <small style="color: var(--text-secondary)">Berita dianalisis</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-custom text-center">
            <div class="card-title">Risk Summary</div>
            <div class="d-flex justify-content-center gap-2 mt-2">
                <span class="badge-high">{{ $highRisk }} High</span>
                <span class="badge-medium">{{ $mediumRisk }} Med</span>
                <span class="badge-low">{{ $lowRisk }} Low</span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Top 10 Negara Risiko Tertinggi --}}
    <div class="col-md-7">
        <div class="card-custom">
            <div class="page-header">
                <h2 style="font-size: 16px;">🔴 Top 10 Negara Risiko Tertinggi</h2>
            </div>
            <table class="table table-custom">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Negara</th>
                        <th>Cuaca</th>
                        <th>Inflasi</th>
                        <th>Berita</th>
                        <th>Kurs</th>
                        <th>Total</th>
                        <th>Level</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topRiskCountries as $index => $risk)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <a href="{{ route('countries.show', $risk->country->code) }}"
                               style="color: var(--accent-light); text-decoration: none;">
                                {{ $risk->country->name ?? '-' }}
                            </a>
                        </td>
                        <td>{{ $risk->weather_risk }}</td>
                        <td>{{ $risk->inflation_risk }}</td>
                        <td>{{ $risk->news_risk }}</td>
                        <td>{{ $risk->currency_risk }}</td>
                        <td><strong>{{ $risk->total_risk }}</strong></td>
                        <td>
                            @if($risk->risk_level === 'High')
                                <span class="badge-high">High</span>
                            @elseif($risk->risk_level === 'Medium')
                                <span class="badge-medium">Medium</span>
                            @else
                                <span class="badge-low">Low</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Sidebar Info --}}
    <div class="col-md-5">
        {{-- Berita Terbaru --}}
        <div class="card-custom">
            <div class="page-header">
                <h2 style="font-size: 16px;">📰 Berita Terbaru</h2>
            </div>
            @foreach($latestNews as $news)
            <div style="border-bottom: 1px solid var(--border-color); padding: 10px 0;">
                <div style="font-size: 13px; color: var(--text-primary); margin-bottom: 4px;">
                    {{ Str::limit($news->title, 70) }}
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <small style="color: var(--text-secondary)">
                        {{ $news->country->name ?? '-' }}
                    </small>
                    @if($news->sentiment === 'Positive')
                        <span class="badge-low" style="font-size: 10px;">Positive</span>
                    @elseif($news->sentiment === 'Negative')
                        <span class="badge-high" style="font-size: 10px;">Negative</span>
                    @else
                        <span class="badge-medium" style="font-size: 10px;">Neutral</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        {{-- Cuaca Ekstrem --}}
        <div class="card-custom">
            <div class="page-header">
                <h2 style="font-size: 16px;">⛈ Cuaca Ekstrem</h2>
            </div>
            @if($extremeWeather->isEmpty())
                <p style="color: var(--text-secondary); font-size: 13px;">Tidak ada cuaca ekstrem saat ini</p>
            @else
                @foreach($extremeWeather as $weather)
                <div style="border-bottom: 1px solid var(--border-color); padding: 10px 0;">
                    <div style="font-size: 13px; color: var(--text-primary);">
                        {{ $weather->country->name ?? '-' }}
                    </div>
                    <small style="color: var(--text-secondary)">
                        {{ $weather->weather_condition }} — {{ $weather->temperature }}°C
                        | Hujan: {{ $weather->rainfall }}mm
                        | Angin: {{ $weather->wind_speed }} km/h
                    </small>
                </div>
                @endforeach
            @endif
        </div>
    </div>
</div>

@endsection