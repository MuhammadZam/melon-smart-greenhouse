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
| `GET` | `/api/monitorings` | `plant_id`, `condition`, `date_from`, `date_to`, `search` | Mengambil riwayat observasi monitoring terurut kronologis terbaru. |
| `GET` | `/api/monitorings/{monitoring}` | - | Mengambil detail satu rekaman observasi parameter monitoring. |
| `GET` | `/api/monitorings/statistics` | `plant_id`, `date_from`, `date_to` | Menghitung agregasi SQL (`AVG`, `MIN`, `MAX`) parameter suhu, pH, kelembapan, tinggi, dan kerapatan kanopi. |
| `GET` | `/api/dashboard/summary` | - | Mengambil ringkasan metrik dashboard, distribusi status tanaman, observasi hari ini, dan kondisi fisik tanaman. |

### Contoh Permintaan & Respons

#### 1. GET `/api/dashboard/summary`
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
      "plant_height": 85.0,
      "canopy_density": 70.0,
      "condition": "Optimal"
    },
    "latest_temperature": 27.5,
    "latest_ph": 6.8,
    "latest_humidity": 75.0,
    "plant_condition_summary": {
      "Optimal": 60,
      "Baik": 35,
      "Perlu Perhatian": 16
    }
  }
}
```

#### 2. GET `/api/monitorings/statistics?plant_id=1`
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

## 6. Status Pengembangan
- Phase 1: Inisialisasi Project, UI Landing Page, Asset Setup ✅
- Phase 2: Schema Database, Migrasi, Model Eloquent, Factory & Seeder ✅
- Phase 3: Autentikasi, Role Middleware, Manajemen Sesi ✅
- Phase 4: Dashboard, Layout Responsive, Summary Cards, Chart.js, Filter Tanaman ✅
- Phase 4.1: Konversi Identitas & Penyesuaian Sistem Monitoring Tanaman Melon ✅
- Phase 5: Monitoring CRUD & Authorization ✅
- Phase 6: Plant Management & Authorization ✅
- Phase 7: Advanced Chart & Visualization ✅
- **Phase 8: REST API Monitoring System (Selesai)** ✅
- Phase 9: IoT / Real-Time Integration (Akan datang / Menunggu instruksi) ⏸️
