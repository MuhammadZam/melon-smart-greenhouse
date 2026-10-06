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

## 5. Status Pengembangan
- Phase 1: Inisialisasi Project, UI Landing Page, Asset Setup ✅
- Phase 2: Schema Database, Migrasi, Model Eloquent, Factory & Seeder ✅
- Phase 3: Autentikasi, Role Middleware, Manajemen Sesi ✅
- Phase 4: Dashboard, Layout Responsive, Summary Cards, Chart.js, Filter Tanaman ✅
- **Phase 4.1: Konversi Identitas & Penyesuaian Sistem Monitoring Tanaman Melon (Selesai)** ✅
- Phase 5: Monitoring CRUD (Akan datang / Menunggu instruksi) ⏸️
