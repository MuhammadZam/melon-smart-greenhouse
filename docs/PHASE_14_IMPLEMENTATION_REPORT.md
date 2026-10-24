# LAPORAN IMPLEMENTASI PHASE 14
## GOOGLE SHEETS TELEMETRY INTEGRATION & DEVICE SECURITY
### Sistem Web Monitoring Tanaman Melon — Greenhouse Smart Agriculture

**Versi Dokumen:** 1.0  
**Tanggal Rilis:** 16 Oktober 2026  
**Status Keseluruhan:** **PASS (Mock Integration Verified; Live Google Sheets Marked PENDING)**  
**Penanggung Jawab:** Senior Laravel Engineer, IoT Integration Architect, Database Engineer, Security Engineer, QA Engineer  

---

## 1. RINGKASAN EKSEKUTIF

Phase 14 berhasil mengimplementasikan pipa data telemetri Google Sheets (`ESP32 → Google Sheets → Laravel Sync Engine → MySQL → Dashboard`) serta fondasi keamanan perangkat IoT (*Device Security & Hashed Token Authentication*).

### Pencapaian Kunci:
1. **Google Sheets Telemetry Connector:**
   - Adapter pattern modular (`GoogleSheetsClientInterface`, `MockGoogleSheetsClient`, `GoogleSheetsApiClient`, `GoogleSheetsTelemetryService`).
   - Dukungan autentikasi Google Cloud Service Account dengan enkripsi RSA-SHA256 JWT.
   - Konfigurasi kredensial terisolasi di luar root direktori publik (`storage/app/credentials/`) dan diproteksi dari Git (`.gitignore`).
   - Mock Adapter untuk pengujian otomatis tanpa ketergantungan jaringan eksternal.
   - Penandaan status: **PENDING** untuk integrasi live fisik hingga kredensial diserahkan oleh Tim Elektro.
2. **Mesin Sinkronisasi & Idempotensi Data:**
   - Service `GoogleSheetsSyncService` dan Artisan Command `php artisan telemetry:sync-google-sheets`.
   - Strategi inkremental cursor (*high-water mark*) baris dan timestamp.
   - Idempotensi unik menggunakan hash `source_identifier` (`sha256(device_id + monitored_at + plant_id)`).
   - Scheduler lock via cache lock (`Cache::lock`) guna mencegah konkurensi tumpang tindih.
   - Evaluasi alarm terintegrasi penuh melalui `MonitoringAlarmService` dengan kebijakan ketat 4-parameter.
3. **Keamanan Perangkat IoT (Device Security):**
   - Endpoint `POST /api/monitorings` diproteksi middleware `AuthenticateDeviceToken` (`device.auth`) dan rate limiting `throttle:60,1`.
   - Token otentikasi disimpan dalam format hash kriptografis SHA-256 (`token_hash`) di tabel `iot_devices`.
   - Kemampuan pencabutan (*revocation*) dan rotasi token melalui Admin Panel.
   - Mode transisi ramah pengujian (*development mode fallback*) dengan header `X-Device-Auth-Warning`.
   - Kompatibilitas penuh dengan Python Simulator (`iot-simulator/simulator.py`) melalui flag `--device-token` dan `--device-id`.
4. **Antarmuka Administrator (Admin UI):**
   - Halaman khusus `/admin/integrations/google-sheets` terproteksi RBAC `role:admin`.
   - Menampilkan metrik data diterima, record dilewati (duplikat), kegagalan, status kesegaran (*connected*, *stale*, *error*, *no_data*), dan audit log lengkap.
   - Tombol manual sync untuk Administrator.
   - Registrasi dan pencabutan token perangkat IoT tanpa pernah mengekspos token hash/secret.
5. **Kesiapan Draf Fuzzy Logic (Phase 15 Prep):**
   - DTO `App\DTO\FuzzyInferenceResult` siap menerima hasil inferensi tanpa mengklaim AI aktif sebelum rule-base disepakati.
6. **Verifikasi Kualitas & Regresi:**
   - **160 tests PASS (3.703 assertions)** diuji secara menyeluruh tanpa regresi dari Phase 1–13.
   - Build aset Vite production selesai tanpa error.

---

## 2. AUDIT PERUBAHAN BASIS DATA & SKEMA

Migrasi non-destruktif `2026_10_16_220000_create_phase14_telemetry_and_security_tables.php` telah dieksekusi pada MySQL Docker container (`azolla_mysql`) dan SQLite testing database:

### A. Tabel Baru: `iot_devices`
Menyimpan registrasi perangkat fisik/simulasi dan hash token otentikasi:
- `id` (BIGINT UNSIGNED PK)
- `device_id` (VARCHAR UNIQUE, misal: `ESP32-MELON-01`, `SIMULATOR-01`)
- `name` (VARCHAR)
- `type` (VARCHAR: `esp32_hardware`, `python_simulator`, `google_sheets_gateway`)
- `token_hash` (VARCHAR(64), SHA-256 hash)
- `token_prefix` (VARCHAR(12), misal: `dev-melo...` untuk identifikasi visual)
- `is_active` (BOOLEAN)
- `last_seen_at` (TIMESTAMP NULL)
- `last_ip` (VARCHAR(45) NULL)
- `revoked_at` (TIMESTAMP NULL)
- `revoked_reason` (VARCHAR NULL)
- `timestamps`

### B. Tabel Baru: `google_sheets_sync_configs`
Menyimpan konfigurasi target spreadsheet dan status kursor:
- `id` (BIGINT UNSIGNED PK)
- `name` (VARCHAR)
- `spreadsheet_id` (VARCHAR NULL)
- `sheet_name` (VARCHAR, default: `Telemetry`)
- `data_range` (VARCHAR, default: `A2:O`)
- `is_active` (BOOLEAN)
- `polling_interval_minutes` (INT, default: 5)
- `auth_mode` (VARCHAR: `mock`, `service_account`)
- `credentials_path` (VARCHAR NULL)
- `last_synced_at` (TIMESTAMP NULL)
- `last_successful_sync_at` (TIMESTAMP NULL)
- `last_synced_row` (UNSIGNED INT, default: 1)
- `last_status` (VARCHAR: `IDLE`, `SUCCESS`, `PARTIAL`, `FAILED`)
- `last_error` (TEXT NULL)

### C. Tabel Baru: `google_sheets_sync_logs`
Catatan audit setiap eksekusi sinkronisasi:
- `id` (BIGINT UNSIGNED PK)
- `config_id` (FK to `google_sheets_sync_configs`, nullable)
- `mode` (VARCHAR: `mock`, `live`)
- `status` (VARCHAR: `SUCCESS`, `PARTIAL`, `FAILED`)
- `records_imported` (UNSIGNED INT)
- `records_skipped` (UNSIGNED INT)
- `records_failed` (UNSIGNED INT)
- `started_at` & `finished_at` (TIMESTAMP)
- `error_details` (TEXT NULL)
- `cursor_before` & `cursor_after` (VARCHAR NULL)
- `triggered_by` (VARCHAR: `cli`, `schedule`, `manual_admin`)

### D. Perluasan Kolom pada Tabel `monitorings`
- `source` (VARCHAR, default `'api'`: `'api'`, `'google_sheets'`, `'web'`)
- `device_id` (VARCHAR NULL)
- `source_identifier` (VARCHAR NULL, indexed SHA-256 hash)
- `reported_pump_state` (VARCHAR NULL, status aktual fisik)
- `reported_fan_state` (VARCHAR NULL, status aktual fisik)
- `fuzzy_score` (DECIMAL(5,2) NULL)
- `fuzzy_label` (VARCHAR NULL)

---

## 3. DAFTAR BERKAS BARU & BERKAS TERUBAH

### Berkas Baru:
1. `database/migrations/2026_10_16_220000_create_phase14_telemetry_and_security_tables.php`
2. `database/seeders/IotDeviceSeeder.php`
3. `app/Models/IotDevice.php`
4. `app/Models/GoogleSheetsSyncConfig.php`
5. `app/Models/GoogleSheetsSyncLog.php`
6. `app/Contracts/GoogleSheetsClientInterface.php`
7. `app/Exceptions/GoogleSheetsException.php`
8. `app/Services/GoogleSheets/MockGoogleSheetsClient.php`
9. `app/Services/GoogleSheets/GoogleSheetsApiClient.php`
10. `app/Services/GoogleSheets/GoogleSheetsTelemetryService.php`
11. `app/Services/GoogleSheets/GoogleSheetsSyncService.php`
12. `app/DTO/FuzzyInferenceResult.php`
13. `app/Http/Middleware/AuthenticateDeviceToken.php`
14. `app/Console/Commands/SyncGoogleSheetsTelemetryCommand.php`
15. `app/Http/Controllers/GoogleSheetsIntegrationController.php`
16. `resources/views/admin/integrations/google_sheets.blade.php`
17. `tests/Feature/GoogleSheetsTelemetryAndSecurityTest.php`
18. `docs/PHASE_14_IMPLEMENTATION_REPORT.md`
19. `docs/GOOGLE_SHEETS_SETUP_GUIDE.md`
20. `docs/TELEMETRY_DATA_MAPPING.md`
21. `docs/DEVICE_AUTHENTICATION_GUIDE.md`
22. `docs/SYNCHRONIZATION_ARCHITECTURE.md`
23. `docs/ERROR_HANDLING_AND_RECOVERY.md`
24. `docs/PHASE_15_READINESS_ASSESSMENT.md`

### Berkas Terubah:
1. `app/Models/Monitoring.php` (tambah fillable metadata, casts, relasi `device()`).
2. `app/Http/Controllers/Api/MonitoringController.php` (asimilasi device_id & source_identifier).
3. `app/Http/Requests/Api/StoreMonitoringApiRequest.php` (tambah validasi `device_id`).
4. `bootstrap/app.php` (registrasi alias middleware `device.auth`).
5. `routes/api.php` (pasang middleware `device.auth` dan rate limiter).
6. `routes/web.php` (registrasi rute Admin integrasi Google Sheets).
7. `config/services.php` (konfigurasi Google Sheets & IoT enforcement).
8. `.gitignore` (isolasi kredensial Service Account JSON).
9. `database/seeders/DatabaseSeeder.php` (panggil `IotDeviceSeeder`).
10. `resources/views/layouts/partials/sidebar.blade.php` (navigasi Admin integrasi Google Sheets).
11. `iot-simulator/simulator.py` (dukungan parameter `--device-token` dan `--device-id`).
12. `iot-simulator/.env.example` (dokumentasi variabel lingkungan baru).

---

## 4. VERIFIKASI PENGUJIAN OTOMATIS

Perbandingan metrik pengujian:
- **Baseline Phase 12:** 119 tests PASS.
- **Baseline Phase 13:** 139 tests PASS (2.521 assertions).
- **Hasil Akhir Phase 14:** **160 tests PASS (3.703 assertions)**.
- **Delta:** +21 tests baru, +1.182 assertions baru, **0 regressions**.

Semua 21 skenario pengujian Phase 14 terverifikasi 100% lulus:
1. Valid Google Sheets rows ingestion.
2. Missing optional values handled gracefully.
3. Invalid numeric values rejected.
4. Invalid timestamps rejected.
5. Duplicate rows skipped by idempotency.
6. Repeated synchronization idempotency.
7. Out-of-order historical readings.
8. Partial failure and retry handling.
9. Spreadsheet unavailable (HTTP 404) exception handling.
10. API timeout exception handling.
11. Rate limiting (HTTP 429) backoff handling.
12. Valid device token authentication.
13. Invalid device token rejection (HTTP 401).
14. Revoked token rejection (HTTP 403).
15. Missing token in production environment (HTTP 401).
16. Simulator backwards-compatibility in development mode.
17. Four-parameter alarm policy continuity.
18. Admin dashboard status view rendering.
19. RBAC access control (Admin allowed, Pengamat rejected).
20. Preservation of historical data integrity.
21. Regression verification Phase 1–13.

---

## 5. CATATAN SRS

Perubahan berikut perlu diinkorporasikan ke dalam draf revisi SRS akademik (tanpa mengubah dokumen `.docx` asli):
1. **SRS-FR-027 (Google Sheets Ingestion):** Sistem mampu mengonsumsi telemetri periodik dari Google Sheets API v4 melalui kursor baris inkremental dan idempotensi hash identitas sumber.
2. **SRS-FR-028 (IoT Device Authentication):** Sistem memvalidasi identitas perangkat keras/simulasi pada endpoint REST API menggunakan token yang di-hash dengan algoritma SHA-256 pada basis data.
3. **SRS-NFR-SEC-004 (Credential Isolation):** Kredensial Google Service Account disimpan di luar direktori web publik dan diisolasi dari kontrol versi Git.
4. **SRS-NFR-PERF-003 (Rate Limiting & Cursor Query):** Pengambilan data Google Sheets dibatasi per batch rentang sel dan scheduler lock guna mematuhi batas kuota 60 request/menit API Google.
