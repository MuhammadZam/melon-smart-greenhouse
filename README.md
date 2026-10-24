# Sistem Web Monitoring Tanaman Melon

Sistem Web Monitoring Tanaman Melon adalah platform berbasis web untuk pemantauan kondisi lingkungan greenhouse, mikroklimat (suhu, pH media/nutrisi, kelembapan), dan pertumbuhan tanaman melon secara terstruktur.

---

## 1. Tech Stack
- **Framework:** Laravel 11 (PHP 8.2+)
- **Database:** MySQL 8.0 & SQLite (Testing)
- **Frontend / Styling:** Blade, Tailwind CSS, Alpine.js
- **Visualisasi Tren:** Chart.js
- **Containerization:** Docker & Docker Compose
- **Testing:** PHPUnit (Feature & Unit Tests)

---

## 2. Fitur Utama (Phase 1 – Phase 4.1)
- **Authentication & Role-Based Access Control:**
  - Role `admin`: Akses penuh ke dashboard, panel administrasi, ringkasan parameter threshold yang dikonfigurasi.
  - Role `user` (Pengamat): Akses pemantauan dashboard, data observasi, filter tanaman, dan grafik tren.
  - Middleware perlindungan rute (`role:admin`, `auth`, `guest`).
- **Dashboard Pemantauan:**
  - Summary Cards: Total Tanaman, Total Monitoring, Observasi Terakhir, Status Tanaman (Active, Harvested, Inactive).
  - Filter Dropdown Tanaman: Pemilihan data per plot tanaman melon atau akumulasi seluruh tanaman.
  - Grafik Interaktif (Chart.js): Suhu Lingkungan, pH Nutrisi/Media, Kelembapan Udara.
  - Tabel Observasi Terkini: 8 data observasi terbaru dengan indikator badge status kondisi tanaman.
- **Konfigurasi Ambang Batas (Configured Monitoring Threshold):**
  - Suhu Lingkungan (20.00 – 32.00 °C)
  - pH Nutrisi/Media (5.80 – 7.20 pH)
  - Kelembapan Udara (60.00 – 85.00 %)
  - Tinggi Tanaman (10.00 – 150.00 cm)
  - Kerapatan Kanopi (40.00 – 90.00 %)

---

## 3. Akun Pengujian (Demo Credentials)
| Role | Email | Password |
|---|---|---|
| Administrator | `admin@melon.com` | `password123` |
| Pengamat Lapangan | `pengamat@melon.com` | `password123` |

---

## 4. Menjalankan Database Seeder & Pengujian
```bash
# Menjalankan migrasi dan seeder data melon
php artisan migrate:fresh --seed

# Menjalankan automated test suite
php artisan test
```

---

## 5. REST API Documentation (Phase 8)

Sistem menyediakan REST API berbasis JSON untuk integrasi sensor, IoT/ESP32, aplikasi mobile, maupun klien eksternal.

### Base URL
```text
http://localhost:8000/api
```

### Response Standard & HTTP Status Codes
- `200 OK`: Permintaan berhasil diproses.
- `404 Not Found`: Entitas tanaman atau rekaman monitoring tidak ditemukan.
- `422 Unprocessable Entity`: Validasi parameter atau query filter gagal.
- `500 Internal Server Error`: Kesalahan sistem internal.

Format Berhasil:
```json
{
  "success": true,
  "message": "Data berhasil diambil.",
  "data": {}
}
```

Format Gagal / Error:
```json
{
  "success": false,
  "message": "Data tidak valid.",
  "errors": {}
}
```

### Endpoints

| Method | Endpoint | Query Parameters | Deskripsi |
|---|---|---|---|
| `GET` | `/api/plants` | `status` (`active`,`harvested`,`inactive`,`all`), `search` | Mengambil seluruh daftar spesimen tanaman melon beserta jumlah observasi dan monitoring terakhir. |
| `GET` | `/api/plants/{plant}` | - | Mengambil detail spesimen tanaman melon tertentu berdasarkan ID. |
| `GET` | `/api/monitorings` | `plant_id`, `condition`, `date_from`, `date_to`, `search` | Mengambil riwayat observasi monitoring terurut kronologis terbaru (termasuk 8 parameter sensor IoT). |
| `POST` | `/api/monitorings` | - | Mengirimkan data pembacaan sensor baru (ingestion endpoint untuk ESP32 / IoT simulator). Mendukung payload lengkap, parsial, maupun legacy. |
| `GET` | `/api/monitorings/{monitoring}` | - | Mengambil detail satu rekaman observasi parameter monitoring. |
| `GET` | `/api/monitorings/statistics` | `plant_id`, `date_from`, `date_to` | Menghitung agregasi SQL (`AVG`, `MIN`, `MAX`) untuk 8 parameter sensor lingkungan, nutrisi, media tanam, dan morfologi. |
| `GET` | `/api/dashboard/summary` | - | Mengambil ringkasan metrik dashboard, distribusi status tanaman, observasi hari ini, dan pembacaan sensor terakhir. |

### Contoh Permintaan & Respons

#### 1. POST `/api/monitorings` (Ingesti Data Sensor Lengkap 8 Parameter)
Request:
```json
{
  "plant_id": 1,
  "monitored_at": "2026-10-14 10:30:00",
  "temperature": 28.4,
  "humidity": 74.5,
  "soil_moisture": 68.0,
  "ph": 6.35,
  "ec": 2.25,
  "light_intensity": 32000.0,
  "nutrient_temperature": 24.2,
  "tank_level": 85.0,
  "plant_height": 82.0,
  "canopy_density": 68.0,
  "condition": "optimal",
  "leaf_color": "Hijau Segar",
  "notes": "Telemetri sensor greenhouse node A"
}
```
Response (`201 Created`):
```json
{
  "success": true,
  "message": "Data monitoring berhasil disimpan.",
  "data": {
    "id": 118,
    "plant_id": 1,
    "temperature": 28.4,
    "humidity": 74.5,
    "soil_moisture": 68.0,
    "ph": 6.35,
    "ec": 2.25,
    "light_intensity": 32000.0,
    "nutrient_temperature": 24.2,
    "tank_level": 85.0,
    "plant_height": 82.0,
    "canopy_density": 68.0,
    "condition": "optimal",
    "leaf_color": "Hijau Segar",
    "notes": "Telemetri sensor greenhouse node A"
  }
}
```

#### 2. GET `/api/dashboard/summary`
```json
{
  "success": true,
  "message": "Ringkasan dashboard berhasil diambil.",
  "data": {
    "total_plants": 63,
    "active_plants": 31,
    "harvested_plants": 19,
    "inactive_plants": 13,
    "total_monitorings": 111,
    "today_monitorings": 6,
    "todays_monitorings": 6,
    "latest_monitoring": {
      "id": 1,
      "plant_id": 1,
      "temperature": 27.5,
      "ph": 6.8,
      "humidity": 75.0,
      "ec": 2.2,
      "soil_moisture": 70.0,
      "light_intensity": 35000.0,
      "nutrient_temperature": 24.0,
      "tank_level": 88.0,
      "plant_height": 85.0,
      "canopy_density": 70.0,
      "condition": "Optimal"
    },
    "latest_temperature": 27.5,
    "latest_ph": 6.8,
    "latest_humidity": 75.0,
    "latest_ec": 2.2,
    "latest_soil_moisture": 70.0,
    "latest_light_intensity": 35000.0,
    "latest_nutrient_temperature": 24.0,
    "latest_tank_level": 88.0,
    "plant_condition_summary": {
      "Optimal": 60,
      "Baik": 35,
      "Perlu Perhatian": 16
    }
  }
}
```

#### 3. GET `/api/monitorings/statistics?plant_id=1`
```json
{
  "success": true,
  "message": "Statistik monitoring berhasil diambil.",
  "data": {
    "total_monitorings": 15,
    "average_temperature": 27.18,
    "minimum_temperature": 23.0,
    "maximum_temperature": 31.24,
    "average_ph": 6.8,
    "minimum_ph": 6.2,
    "maximum_ph": 7.3,
    "average_humidity": 72.5,
    "minimum_humidity": 60.0,
    "maximum_humidity": 85.0,
    "average_ec": 2.24,
    "minimum_ec": 1.9,
    "maximum_ec": 2.6,
    "average_soil_moisture": 71.3,
    "minimum_soil_moisture": 62.0,
    "maximum_soil_moisture": 80.0,
    "average_light_intensity": 29400.0,
    "minimum_light_intensity": 18200.0,
    "maximum_light_intensity": 41500.0,
    "average_nutrient_temperature": 23.6,
    "minimum_nutrient_temperature": 21.0,
    "maximum_nutrient_temperature": 25.8,
    "average_tank_level": 82.5,
    "minimum_tank_level": 65.0,
    "maximum_tank_level": 95.0,
    "average_plant_height": 78.4,
    "minimum_plant_height": 30.0,
    "maximum_plant_height": 135.0,
    "average_canopy_density": 68.2,
    "minimum_canopy_density": 45.0,
    "maximum_canopy_density": 88.0
  }
}
```

---

## 6. IoT Data Simulation & ESP32 Integration Readiness (Phase 11)

### Status Kesiapan Integrasi:
- **Prodi Teknik Informatika:** Bertanggung jawab atas backend API, skema basis data, validasi numerik, konfigurasi ambang batas, visualisasi analitik web, dan pengujian regresi.
- **Prodi Teknik Elektro:** Bertanggung jawab atas mikrokontroler ESP32, sensor fisik (DHT22/SHT31, pH electrode, EC probe, soil moisture capacitive, BH1750, DS18B20, ultrasonic/level sensor), kalibrasi ADC, firmware transmisi HTTP, dan aktuator fisik (relay pompa/ventilasi).
- **Klarifikasi Data:** Seluruh data yang dihasilkan oleh skrip Python di direktori `iot-simulator/` merupakan **DATA SIMULASI** sintetis untuk membuktikan kesiapan API dan antarmuka web, BUKAN data sensor fisik aktual.

### Daftar 8 Parameter Sensor Terintegrasi:
1. `temperature` (°C) — Mikroklimat greenhouse
2. `humidity` (% RH) — Kelembapan udara greenhouse
3. `soil_moisture` (%) — Kelembapan media tanam
4. `ph` (pH) — Derajat keasaman larutan nutrisi
5. `ec` (mS/cm) — Konduktivitas elektrik larutan nutrisi
6. `light_intensity` (lux) — Radiasi intensitas cahaya
7. `nutrient_temperature` (°C) — Suhu larutan tandon nutrisi
8. `tank_level` (%) — Cadangan volume tandon nutrisi

### Menjalankan Simulator Python:
```bash
# Pengiriman satu kali data simulasi 8 parameter
python iot-simulator/simulator.py --once

# Pengiriman berkala setiap 10 detik
python iot-simulator/simulator.py --interval 10

# Menargetkan spesimen tanaman ID 2
python iot-simulator/simulator.py --plant-id 2 --once
```

---

## 7. Status Pengembangan
- Phase 1: Inisialisasi Project, UI Landing Page, Asset Setup ✅
- Phase 2: Schema Database, Migrasi, Model Eloquent, Factory & Seeder ✅
- Phase 3: Autentikasi, Role Middleware, Manajemen Sesi ✅
- Phase 4: Dashboard, Layout Responsive, Summary Cards, Chart.js, Filter Tanaman ✅
- Phase 4.1: Konversi Identitas & Penyesuaian Sistem Monitoring Tanaman Melon ✅
- Phase 5: Monitoring CRUD & Authorization ✅
- Phase 6: Plant Management & Authorization ✅
- Phase 7: Advanced Chart & Visualization ✅
- Phase 8: REST API Monitoring System ✅
- Phase 9: IoT Data Simulation / Sensor Integration Prototype ✅
- Phase 10: Testing & Hardening (PASS: 92 tests, 2945 assertions) ✅
- **Phase 11: Sensor Parameter Expansion & IoT Integration Readiness (Selesai)** ✅
- Phase 12: Deployment & Real Device Field Integration (Menunggu Instruksi) ⚪
