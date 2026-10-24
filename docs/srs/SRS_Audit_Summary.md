# Ringkasan Eksekutif Audit Teknis Kode Sumber Proyek
**Sistem Web Monitoring Tanaman Melon pada Greenhouse**
*Audit Aktual Berbasis Bukti Nyata — D:\Compro — 16 Oktober 2026*

---

## 1. Ikhtisar Hasil Audit

Berdasarkan pemeriksaan langsung terhadap seluruh komponen kode sumber, arsitektur basis data, konfigurasi pustaka, dan eksekusi rangkaian pengujian otomatis pada proyek `D:\Compro`, tim analis dan penjaminan mutu (*Quality Assurance*) menyatakan bahwa sistem telah memiliki pondasi perangkat lunak yang sangat kokoh pada tingkat kematangan **Phase 12 Selesai**.

### Hasil Pengujian Otomatis Aktual (Verifikasi 16 Oktober 2026)
```text
   PASS  Tests\Unit\ExampleTest
   PASS  Tests\Feature\AuthenticationTest (9 tests)
   PASS  Tests\Feature\DashboardTest (17 tests)
   PASS  Tests\Feature\PlantTest (21 tests)
   PASS  Tests\Feature\MonitoringTest (20 tests)
   PASS  Tests\Feature\AlarmTest (17 tests)
   PASS  Tests\Feature\MonitoringParameterExpansionTest (11 tests)
   PASS  Tests\Feature\Api\MonitoringApiTest (9 tests)
   PASS  Tests\Feature\Api\MonitoringIngestionTest (7 tests)
   PASS  Tests\Feature\Api\PlantApiTest (5 tests)
   PASS  Tests\Feature\Api\DashboardApiTest (3 tests)

  Tests:    119 passed (5.935 assertions)
  Duration: 27.91s
  Failures: 0
  Errors:   0
```

> **Catatan Validasi**: Angka laporan terdahulu (Phase 11: 102 tests/3.846 assertions, Phase 12 awal: 119 tests/5.423 assertions) telah terlampaui dan diverifikasi ulang secara aktual pada repositori lokal menghasilkan **119 test cases dan 5.935 assertions berstatus lulus 100%**.

---

## 2. Inventarisasi Teknologi & Komponen Terpasang

### A. Lingkungan Komputasi Backend
* **Bahasa Pemrograman**: PHP `^8.2` (Lingkungan aktif: PHP 8.2+).
* **Framework Utama**: Laravel Framework `^11.0` (Arsitektur MVC modern).
* **Dependensi Backend**:
  * `laravel/framework`: Fondasi aplikasi web dan REST API.
  * `laravel/tinker`: Konsol interaktif manajemen basis data.
  * `phpunit/phpunit`: Kerangka kerja pengujian unit dan pengujian integrasi fitur.
  * `fakerphp/faker` & `mockery/mockery`: Pembangkitan data tiruan pengujian.

### B. Lingkungan Frontend & Aset Visual
* **CSS Framework**: Tailwind CSS v4 (`tailwindcss: ^4.3.3`, `@tailwindcss/postcss: ^4.3.3`).
* **JavaScript Framework**: Alpine.js `^3.17.4` (Interaktivitas DOM ringan).
* **Visualisasi Grafik**: Chart.js `^4.5.1` (Rendering canvas grafik responsif).
* **Build Tool**: Vite `^6.4.4` & `laravel-vite-plugin: ^1.0`.

### C. Arsitektur Basis Data (MySQL)
Sistem memiliki 8 berkas migrasi aktif yang menyusun skema relasional:
1. `0001_01_01_000000_create_users_table.php`: Entitas pengguna (id, name, email, password, role [admin/user], timestamps).
2. `0001_01_01_000001_create_cache_table.php`: Penanganan cache aplikasi dan lock.
3. `0001_01_01_000002_create_jobs_table.php`: Antrean proses latar belakang (*background queues*).
4. `2026_10_02_000001_create_plants_table.php`: Entitas tanaman melon (id, name, location, description, status [active/harvested/inactive]).
5. `2026_10_02_000002_create_monitoring_parameters_table.php`: Master konfigurasi batas ambang sensor (minimum_value, maximum_value, unit, critical_min_value, critical_max_value, parameter_key).
6. `2026_10_02_000003_create_monitorings_table.php`: Data telemetri awal (plant_id, temperature, ph, humidity, plant_height, plant_density, plant_condition, plant_color, monitored_at).
7. `2026_10_14_000001_add_expanded_sensor_parameters_to_monitorings_table.php`: **Ekspansi 8 parameter sensor lengkap**:
   * `nutrient_temperature` (decimal 5,2, Suhu larutan nutrisi tandon, °C).
   * `ec` (decimal 5,2, Konduktivitas listrik nutrisi, mS/cm).
   * `soil_moisture` (decimal 5,2, Kelembapan media tanam / tanah, %).
   * `light_intensity` (decimal 8,2, Intensitas cahaya fotosintesis, lux).
   * `tank_level` (decimal 5,2, Level ketinggian air tandon nutrisi, %).
8. `2026_10_14_145836_create_monitoring_alarms_table.php`: **Sistem alarm otomatis terdedikasi**:
   * `parameter_key`, `parameter_display_name`, `unit`.
   * `violation_direction` (`below_minimum`, `above_maximum`).
   * `sensor_value`, `threshold_min`, `threshold_max` (Snapshot historis).
   * `severity` (`warning`, `critical`).
   * `status` (`active`, `resolved`).
   * `first_detected_at`, `last_abnormal_value`, `last_abnormal_at`, `resolved_value`, `resolved_at`.
   * Indeks performa: `idx_alarm_lookup (plant_id, parameter_key, violation_direction, status)`.

---

## 3. Audit Modul & Fungsionalitas Eksisting

### A. Modul Autentikasi & Otorisasi (RBAC)
* Implementasi: `AuthController.php`, `LoginRequest.php`, `RoleMiddleware.php`.
* Fitur Terverifikasi: Login email/password terenkripsi, proteksi rute tertutup bagi guest, pembatasan ketat role `admin` vs `user` (Pengamat Lapangan). Pengamat yang mencoba memanipulasi data tanaman atau threshold langsung diblokir HTTP 403 Forbidden.

### B. Modul Master Data Tanaman (Plant Management)
* Implementasi: `PlantController.php`, `StorePlantRequest.php`, `UpdatePlantRequest.php`, `PlantPolicy.php`.
* Fitur Terverifikasi: CRUD data tanaman melon lengkap, pencarian kata kunci, filter status budidaya, pagination, serta perlindungan integritas data di mana tanaman yang memiliki riwayat data sensor tidak dapat dihapus (*referential integrity protection*).

### C. Modul REST API Telemetri Sensor (Ingestion)
* Implementasi: `Api\MonitoringController.php`, `StoreMonitoringApiRequest.php`, `MonitoringResource.php`.
* Endpoint Terverifikasi:
  * `POST /api/monitorings`: Menerima JSON 8 parameter sensor dari mikrokontroler/simulator.
  * `GET /api/monitorings`: Mengambil riwayat telemetri berindeks.
  * `GET /api/monitorings/statistics`: Agregasi min, max, avg per parameter.
  * `GET /api/dashboard/summary`: Ikhtisar telemetri terkini untuk kartu dashboard.
  * `GET /api/plants` & `GET /api/plants/{id}`: Metadata spesimen tanaman.
  * `GET /api/notifications/status`: Polling status jumlah alarm aktif.

### D. Modul Layanan Alarm Cerdas (MonitoringAlarmService)
* Implementasi: `app/Services/MonitoringAlarmService.php`, `AlarmController.php`.
* Logika Siklus Hidup Alarm:
  1. **Evaluasi Otomatis**: Dipanggil segera setelah record monitoring disimpan.
  2. **Tingkat Keparahan (Severity)**: Pelanggaran level tandon `< 20%` otomatis diklasifikasikan sebagai `critical` (*fail-safe dry-run protection*).
  3. **Pencegahan Duplikasi (Anti-Chattering)**: Transaksi basis data dengan `lockForUpdate()` mencegah pembengkakan record alarm aktif untuk insiden yang sama. Sistem hanya memperbarui `last_abnormal_value`.
  4. **Penyelesaian Otomatis (*Auto-Resolve*)**: Begitu pembacaan telemetri berikutnya kembali ke batas aman normal, alarm aktif otomatis diubah menjadi `resolved` lengkap dengan waktu pemulihan.
  5. **Manajemen Threshold**: Administrator dapat memperbarui batas batas parameter secara langsung melalui antarmuka web (`/admin/thresholds`) tanpa perlu memprogram ulang kode.

### E. Simulator Telemetri IoT Python
* Implementasi: `iot-simulator/simulator.py`, `requirements.txt`.
* Karakteristik: Modul mandiri berbasis Python yang mensimulasikan pengiriman paket telemetri 8 parameter sensor secara periodik (default 10 detik) ke server Laravel, memungkinkan pengujian integrasi alur data tanpa ketergantungan perangkat keras awal.

---

## 4. Pemetaan Kebutuhan: Terimplementasi vs Rencana Masa Depan

Berdasarkan audit 62 Functional Requirements (FR-01 s/d FR-62) template dokumen SRS:

| Status Fungsional | Jumlah FR | Persentase | Rincian Lingkup Fitur |
| :--- | :---: | :---: | :--- |
| **Implemented (Selesai)** | **38 FR** | **61,3%** | Autentikasi, Logout, RBAC, CRUD Tanaman, Ingesti REST API 8 Sensor, Validasi Fisik Data, Persistensi MySQL, Dashboard Telemetri 8 Kartu, Status Warna Parameter, Widget Alarm Aktif, Grafik Tren Chart.js, Filter Tanggal/Tanaman, Logika Alarm Penuh (Evaluasi, Keparahan, Anti-Chatter, Auto-Resolve), Riwayat Telemetri, Riwayat Alarm, dan Pengaturan Threshold oleh Admin. |
| **Planned (Rencana Tahap Lanjut)** | **24 FR** | **38,7%** | Peringkasan data terjadwal (`monitoring_summaries`), Alarm data timeout scheduler, Modul pencatatan observasi buah berdedikasi (`fruit_observations`), Autentikasi Bearer Token perangkat (`devices`), Garis ambang batas grafik, serta seluruh Modul Aktuator Fisik (Status relay, log aktuator, kendali override manual, interlock tandon). |
| **Out of Scope (Luar Lingkup Web)** | **-** | **-** | Perakitan hardware ESP32, penulisan firmware C++, pengkabelan relay, kalibrasi sensor fisik, dan perakitan mekanik greenhouse (menjadi wewenang eksklusif Tim Teknik Elektro). |

---

## 5. Rekomendasi Teknis untuk Pengembangan Lanjut

1. **Pertahankan Kebijakan Pembekuan Kode (*Feature Freeze*)**: Selaras dengan instruksi pengguna, seluruh pengembangan fitur baru (Phase 13–17) dihentikan penuh guna memastikan dokumen spesifikasi SRS menjadi acuan tunggal yang stabil.
2. **Koordinasi Kontrak Antarmuka dengan Elektro**: Menindaklanjuti 8 pertanyaan terbuka kepada Tim Elektro mengenai interval baku pengiriman data dan kesiapan sensor pH/EC analog.
3. **Penyelarasan Standar Mutu dengan Industri**: Mengonfirmasi 5 pertanyaan terbuka kepada Mitra Industri mengenai kriteria grading bobot/kemanisan buah melon untuk implementasi modul observasi buah di masa mendatang.
