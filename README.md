# ⛓ ChainGuard
### Global Supply Chain Risk Intelligence Platform

Platform monitoring risiko rantai pasok global berbasis multi-API dan analitik data.
Dibangun menggunakan Laravel 12, Bootstrap 5, Chart.js, dan Leaflet.js.

---

## 📋 Fitur Utama

| Fitur | Keterangan |
|-------|-----------|
| 🌍 Global Country Dashboard | Data GDP, inflasi, populasi, mata uang, cuaca per negara |
| ⚠️ Risk Scoring Engine | Algoritma Weighted Risk: Cuaca 30% + Inflasi 20% + Berita 40% + Kurs 10% |
| 🌦 Weather Monitor | Peta cuaca global dengan Leaflet.js, color-coded risk level |
| 💱 Currency Impact | Grafik historis kurs Chart.js, volatilitas 7 hari |
| 📰 News Intelligence | Analisis sentimen berita (Lexicon-Based), filter negara & sentimen |
| ⚓ Port Locations | Peta pelabuhan dunia dengan MarkerCluster, search & filter |
| ⚖️ Country Comparison | Bandingkan 2 negara: 7 Chart.js + tabel metrik winner/loser |
| ⭐ Watchlist | Simpan negara favorit, pantau risk real-time |
| ⚙️ Admin Panel | Kelola user, artikel, data refresh manual & scheduler otomatis |

---

## 🛠 Tech Stack

- **Backend**: PHP 8.2, Laravel 12, MySQL 8
- **Frontend**: Bootstrap 5, JavaScript ES6, AJAX
- **Visualisasi**: Chart.js 4, Leaflet.js 1.9
- **API Eksternal**: Open-Meteo, World Bank, REST Countries, ExchangeRate, GNews, World Port Index

---

## 🚀 Cara Menjalankan (XAMPP / Lokal)

### Prasyarat
- PHP 8.2+
- Composer
- Node.js 18+
- MySQL
- XAMPP (Windows)

### Langkah Instalasi

```bash
# 1. Clone repository
git clone https://github.com/username/chainguard.git
cd chainguard

# 2. Install dependencies PHP
composer install

# 3. Install dependencies Node.js & build assets
npm install && npm run build

# 4. Salin konfigurasi environment
cp .env.example .env

# 5. Generate app key
php artisan key:generate

# 6. Sesuaikan konfigurasi database di .env
# DB_DATABASE=chainguard
# DB_USERNAME=root
# DB_PASSWORD=

# 7. Buat database & jalankan migration
php artisan migrate

# 8. Jalankan seeder (data awal)
php artisan db:seed

# 9. Jalankan server
php artisan serve
```

Buka browser: **http://localhost:8000**

### Aktifkan Scheduler Real-Time

```bash
# Jalankan di terminal terpisah (development)
php artisan schedule:work

# Atau jalankan manual per data type:
php artisan chainguard:fetch-weather
php artisan chainguard:fetch-currency
php artisan chainguard:fetch-news
php artisan chainguard:calculate-risk
```

---

## 🐳 Cara Menjalankan dengan Docker

### Prasyarat
- Docker Desktop
- Docker Compose

### Langkah

```bash
# 1. Clone repository
git clone https://github.com/username/chainguard.git
cd chainguard

# 2. Salin .env untuk Docker
cp .env.docker .env

# 3. Build & jalankan semua container
docker compose up -d --build

# 4. Tunggu container siap (sekitar 1-2 menit), lalu:
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
docker compose exec app php artisan optimize
```

Buka browser: **http://localhost:8000**

### Perintah Docker yang Berguna

```bash
# Lihat status container
docker compose ps

# Lihat log app
docker compose logs app -f

# Masuk ke container app
docker compose exec app bash

# Jalankan artisan command
docker compose exec app php artisan chainguard:fetch-weather

# Stop semua container
docker compose down

# Stop & hapus volume database (reset total)
docker compose down -v
```

---

## 🌐 API Endpoints

Semua endpoint memerlukan autentikasi (session login).

| Method | Endpoint | Keterangan |
|--------|----------|-----------|
| GET | `/api/countries` | Daftar semua negara + risk score |
| GET | `/api/countries/{code}` | Detail 1 negara |
| GET | `/api/risk` | Semua risk score (filter: level, region) |
| GET | `/api/risk/{code}` | Risk detail + trend 30 hari |
| GET | `/api/risk/distribution` | Distribusi High/Medium/Low |
| GET | `/api/risk/top` | Top N negara risiko tertinggi |
| GET | `/api/currency` | Semua kurs terkini |
| GET | `/api/currency/{code}` | Historis kurs (7/14/30 hari) |
| GET | `/api/currency/compare` | Bandingkan kurs multi negara |
| GET | `/api/news` | Daftar berita (filter: country, sentiment) |
| GET | `/api/ports` | Daftar pelabuhan + koordinat |
| GET | `/api/compare` | Data lengkap perbandingan 2-4 negara |

---

## 📁 Struktur Project

```
chainguard/
├── app/
│   ├── Console/Commands/       # Artisan commands real-time data
│   ├── Http/Controllers/       # Controllers web & API
│   ├── Models/                 # 15 Eloquent models
│   └── Services/               # 8 service classes (API integration)
├── database/
│   ├── migrations/             # 18 tabel database
│   └── seeders/                # Data awal
├── resources/
│   ├── css/app.css             # Custom CSS (light theme)
│   └── views/                  # Blade templates
├── routes/
│   ├── web.php                 # Web & API routes
│   └── console.php             # Scheduler jadwal
├── docker/
│   ├── nginx/chainguard.conf   # Nginx config
│   └── php/local.ini           # PHP config
├── docker-compose.yml
├── Dockerfile
└── .env.docker
```

---

## 🔑 API Keys yang Dibutuhkan

Daftarkan di `.env`:

| Variable | Layanan | Link Daftar |
|----------|---------|-------------|
| `EXCHANGE_RATE_API_KEY` | ExchangeRate-API | https://www.exchangerate-api.com |
| `GNEWS_API_KEY` | GNews API | https://gnews.io |

API lain (Open-Meteo, World Bank, REST Countries) **tidak memerlukan API key**.

---

## 👨‍💻 Developer

Dibuat sebagai Final Project mata kuliah Pemrograman Web Lanjut.

**Platform**: Global Supply Chain Risk Intelligence System  
**Framework**: Laravel 12  
**Tahun**: 2026
