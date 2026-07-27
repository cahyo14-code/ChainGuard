<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Port;
use App\Models\WeatherData;
use App\Models\RiskScore;
use App\Models\CurrencyHistory;
use App\Models\EconomicIndicator;
use App\Models\Shipment;
use App\Models\ShipmentConditionLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RouteSimulatorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        // Route Simulator khusus untuk user biasa, admin tidak boleh akses.
        $this->middleware(function ($request, $next) {
            if (auth()->user() && auth()->user()->isAdmin()) {
                abort(403, 'Fitur Route Simulator tidak tersedia untuk akun Admin.');
            }
            return $next($request);
        });
    }

    // ── Halaman utama simulator ────────────────────────────────
    public function index()
    {
        $countries = Country::orderBy('name')->get();

        // Pengiriman aktif milik user
        $activeShipments = Shipment::where('user_id', auth()->id())
            ->where('status', 'active')
            ->with(['originCountry', 'destinationCountry'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('simulator.index', compact('countries', 'activeShipments'));
    }

    // ── API: Daftar pelabuhan per negara (untuk dropdown AJAX) ─
    public function portsByCountry(int $countryId)
    {
        // Cache 1 jam per negara — menghindari query berulang
        $ports = \Illuminate\Support\Facades\Cache::remember(
            "ports_country_{$countryId}",
            3600,
            fn() => Port::where('country_id', $countryId)
                ->where('is_active', true)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->orderBy('name')
                ->limit(30)
                ->get(['id', 'name', 'code', 'city', 'latitude', 'longitude', 'type'])
        );

        return response()->json([
            'status' => 'success',
            'data'   => $ports,
        ]);
    }

    // ── Hitung rute & simpan sebagai shipment aktif ────────────
    public function calculate(Request $request)
    {
        $request->validate([
            'origin_country_id'      => 'required|exists:countries,id',
            'destination_country_id' => 'required|exists:countries,id|different:origin_country_id',
            'origin_port_id'         => 'nullable|exists:ports,id',
            'destination_port_id'    => 'nullable|exists:ports,id',
        ]);

        $origin      = Country::findOrFail($request->origin_country_id);
        $destination = Country::findOrFail($request->destination_country_id);

        // Gunakan pelabuhan yang dipilih user, fallback ke pertama yang tersedia
        $originPort = $request->origin_port_id
            ? Port::find($request->origin_port_id)
            : Port::where('country_id', $origin->id)->where('is_active', true)->whereNotNull('latitude')->first();

        $destPort = $request->destination_port_id
            ? Port::find($request->destination_port_id)
            : Port::where('country_id', $destination->id)->where('is_active', true)->whereNotNull('latitude')->first();

        $lat1 = $originPort ? (float)$originPort->latitude  : (float)$origin->latitude;
        $lon1 = $originPort ? (float)$originPort->longitude : (float)$origin->longitude;
        $lat2 = $destPort   ? (float)$destPort->latitude   : (float)$destination->latitude;
        $lon2 = $destPort   ? (float)$destPort->longitude  : (float)$destination->longitude;

        $nauticalMiles = $this->calculateNauticalMiles($lat1, $lon1, $lat2, $lon2);
        $normalDays    = max(3, (int) ceil($nauticalMiles / 500) + 2);
        $normalEta     = Carbon::now()->addDays($normalDays);

        // Faktor kendala
        $factors = $this->calculateFactors($origin, $destination);

        $totalDelayDays   = $factors['weather']['delay'] + $factors['geopolitics']['delay'] + $factors['port']['delay'];
        $riskAdjustedDays = $normalDays + $totalDelayDays;
        $riskEta          = Carbon::now()->addDays($riskAdjustedDays);

        $recommendation = $this->getRecommendation($totalDelayDays);

        $portOriginName = $originPort ? $originPort->name : ($origin->name . ' Port');
        $portDestName   = $destPort   ? $destPort->name   : ($destination->name . ' Port');

        // Rute laut yang benar (waypoint strategis)
        $routeWaypoints = $this->generateSeaRoute($lat1, $lon1, $lat2, $lon2);

        $startedAt = Carbon::now();

        // Simpan sebagai shipment aktif
        $shipment = Shipment::create([
            'user_id'               => auth()->id(),
            'origin_country_id'     => $origin->id,
            'destination_country_id'=> $destination->id,
            'origin_port'           => $portOriginName,
            'destination_port'      => $portDestName,
            'origin_point_lat'      => $lat1,
            'origin_point_lng'      => $lon1,
            'destination_point_lat' => $lat2,
            'destination_point_lng' => $lon2,
            'nautical_miles'        => $nauticalMiles,
            'normal_days'           => $normalDays,
            'normal_eta'            => $normalEta->toDateString(),
            'risk_adjusted_days'    => $riskAdjustedDays,
            'risk_adjusted_eta'     => $riskEta->toDateString(),
            'total_delay_days'      => $totalDelayDays,
            'factors'               => $factors,
            'recommendation'        => $recommendation['text'],
            'recommendation_level'  => $recommendation['level'],
            'status'                => 'active',
        ]);

        // Simpan kondisi kendala sebagai records
        $this->saveConditionRecords($shipment, $factors, $origin, $destination);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'shipment_id' => $shipment->id,
                'started_at'  => $startedAt->toISOString(),
                'origin' => [
                    'name'      => $origin->name,
                    'code'      => $origin->code,
                    'flag_url'  => $origin->flag_url,
                    'port_name' => $portOriginName,
                    'latitude'  => $lat1,
                    'longitude' => $lon1,
                ],
                'destination' => [
                    'name'      => $destination->name,
                    'code'      => $destination->code,
                    'flag_url'  => $destination->flag_url,
                    'port_name' => $portDestName,
                    'latitude'  => $lat2,
                    'longitude' => $lon2,
                ],
                'route' => [
                    'nautical_miles' => number_format($nauticalMiles),
                    'waypoints'      => $routeWaypoints,
                ],
                'eta' => [
                    'normal_days'        => $normalDays,
                    'normal_date'        => $normalEta->locale('id')->isoFormat('D MMMM YYYY'),
                    'risk_adjusted_days' => $riskAdjustedDays,
                    'risk_adjusted_date' => $riskEta->locale('id')->isoFormat('D MMMM YYYY'),
                    'total_delay_days'   => $totalDelayDays,
                ],
                'factors'        => $factors,
                'recommendation' => $recommendation,
            ],
        ]);
    }

    // ── Hapus shipment ─────────────────────────────────────────
    public function destroy(Shipment $shipment)
    {
        if ($shipment->user_id !== auth()->id()) {
            return back()->with('error', 'Unauthorized.');
        }
        $shipment->conditionLogs()->delete();
        $shipment->delete();
        return back()->with('success', 'Pengiriman berhasil dihapus.');
    }

    // ── Tandai pengiriman selesai ──────────────────────────────
    public function complete(Request $request, Shipment $shipment)    {
        if ($shipment->user_id !== auth()->id()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $shipment->update([
            'status'       => 'completed',
            'completed_at' => now(),
            'notes'        => $request->input('notes'),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Pengiriman berhasil ditandai selesai.',
        ]);
    }

    // ── Histori pengiriman ─────────────────────────────────────
    public function history()
    {
        $shipments = Shipment::where('user_id', auth()->id())
            ->with(['originCountry', 'destinationCountry', 'conditionLogs'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('simulator.history', compact('shipments'));
    }

    // ── Detail 1 shipment ──────────────────────────────────────
    public function show(Shipment $shipment)
    {
        if ($shipment->user_id !== auth()->id()) {
            abort(403);
        }

        $shipment->load(['originCountry', 'destinationCountry', 'conditionLogs']);

        // Pakai koordinat pelabuhan persis yang dipakai saat kalkulasi pertama.
        // Fallback ke titik tengah negara hanya untuk shipment lama (sebelum kolom ini ada).
        $lat1 = $shipment->origin_point_lat      ?? (float) $shipment->originCountry->latitude;
        $lon1 = $shipment->origin_point_lng      ?? (float) $shipment->originCountry->longitude;
        $lat2 = $shipment->destination_point_lat ?? (float) $shipment->destinationCountry->latitude;
        $lon2 = $shipment->destination_point_lng ?? (float) $shipment->destinationCountry->longitude;

        $routeWaypoints = $this->generateSeaRoute($lat1, $lon1, $lat2, $lon2);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'shipment'   => $shipment,
                'waypoints'  => $routeWaypoints,
                'origin'     => ['latitude' => $lat1, 'longitude' => $lon1],
                'destination'=> ['latitude' => $lat2, 'longitude' => $lon2],
            ],
        ]);
    }

    // ── Hitung faktor kendala ──────────────────────────────────
    private function calculateFactors($origin, $destination): array
    {
        // 1. Cuaca
        $originWeather = WeatherData::where('country_id', $origin->id)->latest('fetched_at')->first();
        $destWeather   = WeatherData::where('country_id', $destination->id)->latest('fetched_at')->first();

        $weatherDelay = 0;
        $weatherDesc  = 'Cuaca relatif normal di sepanjang jalur pelayaran.';

        if (($originWeather?->storm_risk) || ($destWeather?->storm_risk)) {
            $weatherDelay = 5;
            $weatherDesc  = '⚠️ Risiko badai ekstrem terdeteksi di area pelabuhan lintasan (+5 hari).';
        } elseif (($originWeather?->risk_level === 'High') || ($destWeather?->risk_level === 'High')) {
            $weatherDelay = 3;
            $weatherDesc  = '🌧 Kondisi cuaca buruk memperlambat laju kapal (+3 hari).';
        } elseif (($originWeather?->wind_speed > 35) || ($destWeather?->wind_speed > 35)) {
            $weatherDelay = 2;
            $weatherDesc  = '💨 Angin kencang terdeteksi — kecepatan kapal dikurangi (+2 hari).';
        }

        // 2. Kurs
        $currencyVolatility = 0.0;
        $currencyDesc       = 'Mata uang negara asal relatif stabil dalam 7 hari terakhir.';

        $histories = CurrencyHistory::where('country_id', $origin->id)
            ->orderBy('rate_date', 'desc')->take(7)->pluck('rate');

        if ($histories->count() >= 2) {
            $newest = $histories->first();
            $oldest = $histories->last();
            if ($oldest > 0) {
                $currencyVolatility = round((($newest - $oldest) / $oldest) * 100, 2);
            }
        }

        if ($currencyVolatility > 3.0) {
            $currencyDesc = "📈 Depresiasi signifikan mata uang asal (+{$currencyVolatility}%). Biaya kargo berpotensi membengkak.";
        } elseif ($currencyVolatility < -3.0) {
            $currencyDesc = "📉 Penguatan mata uang asal ({$currencyVolatility}%). Biaya impor lebih efisien.";
        } elseif (abs($currencyVolatility) > 1.5) {
            $currencyDesc = "💱 Volatilitas kurs moderat ({$currencyVolatility}%). Gunakan kontrak forward exchange.";
        }

        // 3. Geopolitik
        $originRisk = RiskScore::where('country_id', $origin->id)->latest('calculated_at')->first();
        $destRisk   = RiskScore::where('country_id', $destination->id)->latest('calculated_at')->first();

        $geoDelay = 0;
        $geoDesc  = 'Kondisi geopolitik di wilayah asal & tujuan tergolong aman.';

        $maxNewsRisk  = max($originRisk?->news_risk ?? 0, $destRisk?->news_risk ?? 0);
        $maxTotalRisk = max($originRisk?->total_risk ?? 0, $destRisk?->total_risk ?? 0);

        if ($maxNewsRisk >= 75 || $maxTotalRisk >= 75) {
            $geoDelay = 7;
            $geoDesc  = '🛡 Konflik geopolitik sangat tinggi — kapal harus rerouting (+7 hari).';
        } elseif ($maxNewsRisk >= 55 || $maxTotalRisk >= 55) {
            $geoDelay = 3;
            $geoDesc  = '⚠️ Isu geopolitik aktif — pemeriksaan keamanan ekstra (+3 hari).';
        } elseif ($maxNewsRisk >= 40 || $maxTotalRisk >= 40) {
            $geoDelay = 1;
            $geoDesc  = '📰 Isu politik lokal terpantau — kemungkinan perlambatan bea cukai (+1 hari).';
        }

        // 4. Kemacetan Pelabuhan
        $portDelay = 0;
        $portDesc  = 'Kepadatan pelabuhan terpantau lancar.';

        $origWeatherRisk = $originRisk?->weather_risk ?? 0;
        $destWeatherRisk = $destRisk?->weather_risk ?? 0;

        if ($origWeatherRisk >= 70 && $destWeatherRisk >= 70) {
            $portDelay = 4;
            $portDesc  = '⚓ Kemacetan parah di pelabuhan asal & tujuan (+4 hari).';
        } elseif ($origWeatherRisk >= 70 || $destWeatherRisk >= 70) {
            $portDelay = 3;
            $portDesc  = '⚓ Antrean padat di salah satu pelabuhan (+3 hari).';
        } elseif ($origWeatherRisk >= 40 || $destWeatherRisk >= 40) {
            $portDelay = 1;
            $portDesc  = '⚓ Kepadatan sedang di area pelabuhan (+1 hari).';
        }

        // 5. Inflasi
        $economic      = EconomicIndicator::where('country_id', $origin->id)->orderBy('year', 'desc')->first();
        $inflationRate = $economic ? (float)($economic->inflation ?? 2.5) : 2.5;
        $inflationDesc = "Inflasi negara asal ({$inflationRate}%) dalam batas wajar.";

        if ($inflationRate > 10.0) {
            $inflationDesc = "📈 Inflasi sangat tinggi ({$inflationRate}%) — biaya total impor berpotensi membengkak.";
        } elseif ($inflationRate > 6.0) {
            $inflationDesc = "⚠️ Inflasi tinggi ({$inflationRate}%) — pertimbangkan renegosiasi harga kontrak.";
        } elseif ($inflationRate > 3.5) {
            $inflationDesc = "💹 Inflasi moderat ({$inflationRate}%) — amankan harga dengan kontrak fixed-price.";
        }

        return [
            'weather'     => ['delay' => $weatherDelay,     'desc' => $weatherDesc],
            'currency'    => ['impact_pct' => $currencyVolatility, 'desc' => $currencyDesc],
            'geopolitics' => ['delay' => $geoDelay,         'desc' => $geoDesc],
            'port'        => ['delay' => $portDelay,         'desc' => $portDesc],
            'inflation'   => ['rate'  => $inflationRate,     'desc' => $inflationDesc],
        ];
    }

    // ── Simpan kondisi kendala sebagai records ─────────────────
    private function saveConditionRecords(Shipment $shipment, array $factors, $origin, $destination): void
    {
        $conditionTypes = [
            'weather'     => 'weather',
            'currency'    => 'currency',
            'geopolitics' => 'geopolitics',
            'port'        => 'port',
            'inflation'   => 'inflation',
        ];

        foreach ($conditionTypes as $key => $type) {
            $factor = $factors[$key];
            $hasIssue = isset($factor['delay']) ? $factor['delay'] > 0 : abs($factor['impact_pct'] ?? 0) > 1.5;

            ShipmentConditionLog::create([
                'shipment_id'    => $shipment->id,
                'condition_type' => $type,
                'title'          => ucfirst($key) . ' Condition',
                'description'    => $factor['desc'],
                'condition_data' => $factor,
                'latitude'       => $origin->latitude,
                'longitude'      => $origin->longitude,
                'location_name'  => $origin->name,
                'recorded_at'    => now(),
            ]);
        }
    }

    // ── Rekomendasi berdasarkan total delay ────────────────────
    private function getRecommendation(int $totalDelay): array
    {
        if ($totalDelay >= 8) {
            return [
                'text'  => '🔴 <strong>RISIKO SANGAT TINGGI</strong>: Rute mengalami kendala kumulatif parah (+' . $totalDelay . ' hari). Pertimbangkan Air Freight atau tunda pengiriman.',
                'level' => 'High',
            ];
        } elseif ($totalDelay >= 4) {
            return [
                'text'  => '🟡 <strong>RISIKO SEDANG</strong>: Beberapa kendala di jalur ini (+' . $totalDelay . ' hari). Gunakan asuransi kargo premium.',
                'level' => 'Medium',
            ];
        } elseif ($totalDelay >= 1) {
            return [
                'text'  => '🟠 <strong>RISIKO RENDAH</strong>: Kendala kecil terdeteksi (+' . $totalDelay . ' hari). Rute masih aman digunakan.',
                'level' => 'Low-Medium',
            ];
        }

        return [
            'text'  => '🟢 <strong>RUTE OPTIMAL</strong>: Jalur pengiriman dalam kondisi prima. Estimasi tiba sangat berpeluang tepat waktu.',
            'level' => 'Low',
        ];
    }

    // ── Hitung Nautical Miles (Haversine) ──────────────────────
    private function calculateNauticalMiles(float $lat1, float $lon1, float $lat2, float $lon2): int
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a    = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        $km   = 6371 * 2 * atan2(sqrt($a), sqrt(1 - $a));
        return max(100, (int) round($km * 0.539957));
    }

    // ── Generate rute laut dengan waypoint strategis ───────────
    private function generateSeaRoute(float $lat1, float $lon1, float $lat2, float $lon2): array
    {
        $wp = [
            'malacca_s'   => [1.2,   103.5],  // Selat Malaka Selatan (Singapura)
            'malacca_n'   => [5.5,    99.5],  // Selat Malaka Utara
            'sunda'       => [-6.0,  105.0],  // Selat Sunda
            'lombok'      => [-8.8,  115.7],  // Selat Lombok
            'makassar'    => [-2.0,  118.0],  // Selat Makassar
            'south_china' => [10.0,  113.0],  // Laut China Selatan
            'vietnam_s'   => [9.0,   109.0],  // Laut Vietnam Selatan
            'luzon'       => [18.0,  122.0],  // Selat Luzon Filipina
            'taiwan'      => [24.0,  122.5],  // Selat Taiwan
            'japan_sw'    => [30.0,  130.0],  // Jepang Barat Daya
            'japan_e'     => [35.5,  141.0],  // Jepang Timur
            'korea_str'   => [34.5,  129.0],  // Selat Korea
            'indian_ne'   => [8.0,    80.0],  // Hindia Timur Laut
            'indian_nw'   => [12.0,   60.0],  // Hindia Barat Laut
            'indian_w'    => [-15.0,  60.0],  // Hindia Barat Tengah
            'indian_sw'   => [-25.0,  50.0],  // Hindia Barat Daya
            'indian_s'    => [-30.0,  80.0],  // Hindia Selatan
            'hormuz'      => [26.3,   56.3],  // Selat Hormuz
            'oman'        => [22.0,   59.0],  // Laut Arab
            'aden_g'      => [12.0,   48.0],  // Teluk Aden
            'aden_w'      => [11.5,   43.5],  // Bab el-Mandeb
            'red_sea_s'   => [15.0,   42.0],  // Laut Merah Selatan
            'red_sea_n'   => [27.5,   34.0],  // Laut Merah Utara
            'suez_s'      => [30.0,   32.6],  // Terusan Suez Selatan
            'suez_n'      => [31.2,   32.3],  // Terusan Suez Utara
            'cape_good'   => [-34.2,  18.3],  // Cape of Good Hope
            'cape_e'      => [-34.0,  26.0],  // Selatan Afrika Timur
            'east_africa' => [-10.0,  40.5],  // Afrika Timur Lepas Pantai
            'med_e'       => [33.5,   32.5],  // Med Timur
            'med_ce'      => [35.0,   24.0],  // Med Tengah-Timur
            'med_c'       => [37.0,   13.0],  // Med Tengah
            'med_w'       => [37.5,    0.5],  // Med Barat
            'gibraltar'   => [35.9,   -5.7],  // Selat Gibraltar
            'atlantic_ne' => [48.0,  -10.0],  // Atlantik Timur Laut
            'atlantic_n'  => [48.0,  -20.0],  // Atlantik Utara
            'atlantic_nw' => [45.0,  -40.0],  // Atlantik Tengah Utara
            'atlantic_s'  => [-20.0, -20.0],  // Atlantik Selatan
            'atlantic_sw' => [-35.0, -50.0],  // Atlantik Selatan Barat
            'north_sea'   => [56.0,    4.0],  // Laut Utara
            'english'     => [50.5,    1.0],  // Selat Inggris
            'biscay'      => [46.0,   -9.0],  // Teluk Biscay
            'azores'      => [38.0,  -27.0],  // Kepulauan Azores
            'florida'     => [24.5,  -81.0],  // Selat Florida
            'carib'       => [15.0,  -70.0],  // Karibia
            'atlantic_us' => [35.0,  -65.0],  // Atlantik AS
            'panama_p'    => [8.5,   -79.8],  // Panama Pasifik
            'panama_a'    => [9.3,   -79.9],  // Panama Atlantik
            'pacific_sw'  => [-15.0,-150.0],  // Pasifik Selatan Barat
            'pacific_nw'  => [30.0,  165.0],  // Pasifik Barat Laut
            'pacific_ne'  => [40.0, -145.0],  // Pasifik Timur Laut
            'pacific_c'   => [5.0,   160.0],  // Pasifik Tengah
            'hawaii'      => [20.0, -157.0],  // Hawaii
        ];

        $originRegion = $this->getRegion($lat1, $lon1);
        $destRegion   = $this->getRegion($lat2, $lon2);
        $routePoints  = $this->selectWaypoints($originRegion, $destRegion, $lat1, $lon1, $lat2, $lon2, $wp);

        return $this->interpolateRoute($routePoints, 80);
    }

    // ── Tentukan region berdasarkan koordinat ──────────────────
    private function getRegion(float $lat, float $lon): string
    {
        if ($lat >= -11 && $lat <= 28 && $lon >= 95  && $lon <= 141) return 'SEA';
        if ($lat >= 20  && $lat <= 50 && $lon >= 118 && $lon <= 152) return 'EAST_ASIA';
        if ($lat >= 5   && $lat <= 38 && $lon >= 60  && $lon <= 95)  return 'SOUTH_ASIA';
        if ($lat >= 12  && $lat <= 40 && $lon >= 35  && $lon <= 65)  return 'MIDDLE_EAST';
        if ($lat >= -35 && $lat <= 15 && $lon >= 28  && $lon <= 52)  return 'EAST_AFRICA';
        if ($lat >= -35 && $lat <= 20 && $lon >= -20 && $lon <= 28)  return 'WEST_AFRICA';
        if ($lat >= 35  && $lat <= 72 && $lon >= -12 && $lon <= 45)  return 'EUROPE';
        if ($lat >= 20  && $lat <= 75 && $lon >= -140&& $lon <= -50) return 'NORTH_AMERICA';
        if ($lat >= -60 && $lat <= 15 && $lon >= -85 && $lon <= -30) return 'SOUTH_AMERICA';
        if ($lat >= -50 && $lat <= 5  && $lon >= 110 && $lon <= 180) return 'OCEANIA';
        return 'OTHER';
    }

    // ── Pilih waypoint berdasarkan rute ────────────────────────
    private function selectWaypoints(string $from, string $to, float $lat1, float $lon1, float $lat2, float $lon2, array $wp): array
    {
        $s = [$lat1, $lon1];
        $e = [$lat2, $lon2];

        // SEA
        if ($from === 'SEA' && $to === 'EUROPE')
            return [$s,$wp['malacca_s'],$wp['malacca_n'],$wp['indian_nw'],$wp['aden_g'],$wp['aden_w'],$wp['red_sea_s'],$wp['red_sea_n'],$wp['suez_s'],$wp['suez_n'],$wp['med_e'],$wp['med_ce'],$wp['med_c'],$wp['med_w'],$wp['gibraltar'],$wp['atlantic_ne'],$e];
        if ($from === 'SEA' && $to === 'MIDDLE_EAST')
            return [$s,$wp['malacca_s'],$wp['malacca_n'],$wp['indian_ne'],$wp['indian_nw'],$wp['oman'],$wp['hormuz'],$e];
        if ($from === 'SEA' && $to === 'SOUTH_ASIA')
            return [$s,$wp['malacca_s'],$wp['malacca_n'],$wp['indian_ne'],$e];
        if ($from === 'SEA' && $to === 'EAST_AFRICA')
            return [$s,$wp['malacca_s'],$wp['indian_nw'],$wp['indian_sw'],$wp['east_africa'],$e];
        if ($from === 'SEA' && $to === 'WEST_AFRICA')
            return [$s,$wp['malacca_s'],$wp['indian_w'],$wp['indian_sw'],$wp['cape_e'],$wp['cape_good'],$wp['atlantic_s'],$e];
        if ($from === 'SEA' && $to === 'NORTH_AMERICA')
            return [$s,$wp['south_china'],$wp['vietnam_s'],$wp['luzon'],$wp['pacific_nw'],$wp['pacific_ne'],$e];
        if ($from === 'SEA' && $to === 'SOUTH_AMERICA')
            return [$s,$wp['south_china'],$wp['luzon'],$wp['pacific_c'],$wp['pacific_sw'],$e];
        if ($from === 'SEA' && $to === 'OCEANIA')
            return [$s,$wp['makassar'],$wp['lombok'],$e];
        if ($from === 'SEA' && $to === 'EAST_ASIA')
            return [$s,$wp['malacca_s'],$wp['south_china'],$wp['vietnam_s'],$wp['taiwan'],$e];

        // EUROPE
        if ($from === 'EUROPE' && $to === 'SEA')
            return [$s,$wp['atlantic_ne'],$wp['gibraltar'],$wp['med_w'],$wp['med_c'],$wp['med_ce'],$wp['med_e'],$wp['suez_n'],$wp['suez_s'],$wp['red_sea_n'],$wp['red_sea_s'],$wp['aden_w'],$wp['aden_g'],$wp['indian_nw'],$wp['malacca_n'],$wp['malacca_s'],$e];
        if ($from === 'EUROPE' && $to === 'EAST_ASIA')
            return [$s,$wp['atlantic_ne'],$wp['gibraltar'],$wp['med_w'],$wp['med_c'],$wp['med_e'],$wp['suez_n'],$wp['suez_s'],$wp['red_sea_n'],$wp['red_sea_s'],$wp['aden_w'],$wp['aden_g'],$wp['indian_nw'],$wp['malacca_n'],$wp['malacca_s'],$wp['south_china'],$wp['taiwan'],$e];
        if ($from === 'EUROPE' && $to === 'MIDDLE_EAST')
            return [$s,$wp['atlantic_ne'],$wp['gibraltar'],$wp['med_w'],$wp['med_c'],$wp['med_e'],$wp['suez_n'],$wp['suez_s'],$wp['red_sea_n'],$wp['red_sea_s'],$wp['aden_w'],$wp['oman'],$wp['hormuz'],$e];
        if ($from === 'EUROPE' && $to === 'NORTH_AMERICA')
            return [$s,$wp['atlantic_ne'],$wp['atlantic_n'],$wp['atlantic_nw'],$e];
        if ($from === 'EUROPE' && $to === 'SOUTH_AMERICA')
            return [$s,$wp['atlantic_ne'],$wp['azores'],$wp['atlantic_s'],$e];
        if ($from === 'EUROPE' && $to === 'EAST_AFRICA')
            return [$s,$wp['atlantic_ne'],$wp['gibraltar'],$wp['med_w'],$wp['med_c'],$wp['med_e'],$wp['suez_n'],$wp['suez_s'],$wp['red_sea_n'],$wp['red_sea_s'],$wp['aden_w'],$wp['aden_g'],$wp['east_africa'],$e];
        if ($from === 'EUROPE' && $to === 'WEST_AFRICA')
            return [$s,$wp['atlantic_ne'],$wp['gibraltar'],$wp['atlantic_s'],$e];
        if ($from === 'EUROPE' && $to === 'SOUTH_ASIA')
            return [$s,$wp['atlantic_ne'],$wp['gibraltar'],$wp['med_w'],$wp['med_c'],$wp['suez_n'],$wp['suez_s'],$wp['red_sea_n'],$wp['red_sea_s'],$wp['aden_w'],$wp['aden_g'],$wp['indian_nw'],$wp['indian_ne'],$e];

        // EAST_ASIA
        if ($from === 'EAST_ASIA' && $to === 'EUROPE')
            return [$s,$wp['taiwan'],$wp['south_china'],$wp['malacca_s'],$wp['malacca_n'],$wp['indian_nw'],$wp['aden_g'],$wp['aden_w'],$wp['red_sea_s'],$wp['red_sea_n'],$wp['suez_s'],$wp['suez_n'],$wp['med_e'],$wp['med_c'],$wp['med_w'],$wp['gibraltar'],$wp['atlantic_ne'],$e];
        if ($from === 'EAST_ASIA' && $to === 'NORTH_AMERICA')
            return [$s,$wp['japan_e'],$wp['pacific_nw'],$wp['pacific_ne'],$e];
        if ($from === 'EAST_ASIA' && $to === 'SEA')
            return [$s,$wp['taiwan'],$wp['south_china'],$wp['malacca_s'],$e];

        // NORTH_AMERICA
        if ($from === 'NORTH_AMERICA' && $to === 'EUROPE')
            return [$s,$wp['atlantic_nw'],$wp['atlantic_n'],$wp['atlantic_ne'],$e];
        if ($from === 'NORTH_AMERICA' && $to === 'SEA')
            return [$s,$wp['pacific_ne'],$wp['pacific_nw'],$wp['luzon'],$wp['south_china'],$wp['malacca_s'],$e];
        if ($from === 'NORTH_AMERICA' && $to === 'EAST_ASIA')
            return [$s,$wp['pacific_ne'],$wp['pacific_nw'],$wp['japan_e'],$e];
        if ($from === 'NORTH_AMERICA' && $to === 'SOUTH_AMERICA')
            return [$s,$wp['florida'],$wp['carib'],$wp['atlantic_s'],$e];
        if ($from === 'NORTH_AMERICA' && $to === 'OCEANIA')
            return [$s,$wp['hawaii'],$wp['pacific_c'],$e];

        // SOUTH_AMERICA
        if ($from === 'SOUTH_AMERICA' && $to === 'EUROPE')
            return [$s,$wp['atlantic_sw'],$wp['atlantic_s'],$wp['azores'],$wp['atlantic_ne'],$e];
        if ($from === 'SOUTH_AMERICA' && $to === 'SEA')
            return [$s,$wp['pacific_sw'],$wp['pacific_c'],$wp['luzon'],$wp['south_china'],$wp['malacca_s'],$e];
        if ($from === 'SOUTH_AMERICA' && $to === 'NORTH_AMERICA')
            return [$s,$wp['atlantic_sw'],$wp['carib'],$wp['florida'],$e];

        // MIDDLE_EAST
        if ($from === 'MIDDLE_EAST' && $to === 'SEA')
            return [$s,$wp['hormuz'],$wp['oman'],$wp['indian_nw'],$wp['indian_ne'],$wp['malacca_n'],$wp['malacca_s'],$e];
        if ($from === 'MIDDLE_EAST' && $to === 'EUROPE')
            return [$s,$wp['hormuz'],$wp['oman'],$wp['aden_w'],$wp['red_sea_s'],$wp['red_sea_n'],$wp['suez_s'],$wp['suez_n'],$wp['med_e'],$wp['med_c'],$wp['gibraltar'],$wp['atlantic_ne'],$e];

        // SOUTH_ASIA
        if ($from === 'SOUTH_ASIA' && $to === 'SEA')
            return [$s,$wp['indian_ne'],$wp['malacca_n'],$wp['malacca_s'],$e];
        if ($from === 'SOUTH_ASIA' && $to === 'EUROPE')
            return [$s,$wp['indian_nw'],$wp['aden_g'],$wp['aden_w'],$wp['red_sea_s'],$wp['red_sea_n'],$wp['suez_s'],$wp['suez_n'],$wp['med_e'],$wp['med_c'],$wp['gibraltar'],$wp['atlantic_ne'],$e];

        // OCEANIA
        if ($from === 'OCEANIA' && $to === 'SEA')
            return [$s,$wp['lombok'],$wp['makassar'],$wp['malacca_s'],$e];
        if ($from === 'OCEANIA' && $to === 'NORTH_AMERICA')
            return [$s,$wp['pacific_c'],$wp['hawaii'],$e];
        if ($from === 'OCEANIA' && $to === 'EUROPE')
            return [$s,$wp['lombok'],$wp['malacca_s'],$wp['malacca_n'],$wp['indian_nw'],$wp['aden_g'],$wp['aden_w'],$wp['red_sea_s'],$wp['red_sea_n'],$wp['suez_s'],$wp['suez_n'],$wp['med_e'],$wp['med_c'],$wp['gibraltar'],$wp['atlantic_ne'],$e];

        // AFRICA
        if ($from === 'EAST_AFRICA' && $to === 'EUROPE')
            return [$s,$wp['east_africa'],$wp['aden_g'],$wp['aden_w'],$wp['red_sea_s'],$wp['red_sea_n'],$wp['suez_s'],$wp['suez_n'],$wp['med_e'],$wp['med_c'],$wp['gibraltar'],$wp['atlantic_ne'],$e];
        if ($from === 'EAST_AFRICA' && $to === 'SEA')
            return [$s,$wp['east_africa'],$wp['indian_sw'],$wp['indian_nw'],$wp['malacca_n'],$wp['malacca_s'],$e];
        if ($from === 'WEST_AFRICA' && $to === 'EUROPE')
            return [$s,$wp['atlantic_s'],$wp['atlantic_ne'],$e];
        if ($from === 'WEST_AFRICA' && $to === 'SEA')
            return [$s,$wp['atlantic_s'],$wp['cape_good'],$wp['cape_e'],$wp['indian_sw'],$wp['indian_nw'],$wp['malacca_n'],$wp['malacca_s'],$e];

        return $this->fallbackSeaRoute($s, $e, $lat1, $lon1, $lat2, $lon2);
    }

    // ── Fallback: offset ke laut hindari darat ─────────────────
    private function fallbackSeaRoute(array $start, array $end, float $lat1, float $lon1, float $lat2, float $lon2): array
    {
        $midLat = max(-55.0, min(55.0, ($lat1 + $lat2) / 2 - 10.0));
        $midLon = ($lon1 + $lon2) / 2;
        return [$start, [$midLat, $midLon], $end];
    }

    // ── Interpolasi rute menjadi titik-titik halus ─────────────
    private function interpolateRoute(array $waypoints, int $totalSteps): array
    {
        if (count($waypoints) < 2) return $waypoints;

        $result    = [];
        $segments  = count($waypoints) - 1;
        $stepsPerSegment = max(5, (int)($totalSteps / $segments));

        for ($i = 0; $i < $segments; $i++) {
            $p1 = $waypoints[$i];
            $p2 = $waypoints[$i + 1];

            for ($j = 0; $j <= $stepsPerSegment; $j++) {
                $t      = $j / $stepsPerSegment;
                $result[] = [
                    round($p1[0] + ($p2[0] - $p1[0]) * $t, 5),
                    round($p1[1] + ($p2[1] - $p1[1]) * $t, 5),
                ];
            }
        }

        return $result;
    }
}