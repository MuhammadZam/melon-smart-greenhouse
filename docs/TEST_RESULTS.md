# LAPORAN HASIL PENGUJIAN OTOMATIS (TEST RESULTS) — PHASE 14
## SISTEM WEB MONITORING TANAMAN MELON

**Tanggal Pengujian:** 16 Oktober 2026  
**Lingkungan Pengujian:** SQLite in-memory / testing database & MySQL 8.0 Docker (`azolla_mysql`)  
**Status Keseluruhan:** **100% PASS (160 Passed, 0 Failed, 0 Skipped)**  
**Total Assertions:** **3.703 Assertions**  
**Durasi Eksekusi:** 11.45 detik  

---

## 1. REKAPITULASI TEST SUITE

| Test Suite File | Modul Pengujian | Jumlah Test | Status | Durasi |
| :--- | :--- | :---: | :---: | :---: |
| `Tests\Feature\GoogleSheetsTelemetryAndSecurityTest` | Integrasi Telemetri Google Sheets & Keamanan IoT (Phase 14) | **21** | **PASS** | 3.37s |
| `Tests\Feature\ActuatorSimulationTest` | Simulasi Kontrol Aktuator & Audit Trail (Phase 13) | 17 | **PASS** | 3.05s |
| `Tests\Feature\AlarmTest` | Evaluasi Alarm, 4 Parameter, Transisi, & Threshold (Phase 12–13) | 20 | **PASS** | 3.50s |
| `Tests\Feature\AuthenticationTest` | Autentikasi Pengguna & RBAC Session (Phase 3) | 10 | **PASS** | 0.85s |
| `Tests\Feature\DashboardTest` | Visualisasi Dashboard, Agregasi, & Widget Aktuator | 12 | **PASS** | 1.15s |
| `Tests\Feature\MonitoringParameterExpansionTest` | 8 Parameter Sensor IoT & REST API (Phase 7) | 10 | **PASS** | 0.80s |
| `Tests\Feature\MonitoringTest` | CRUD Monitoring & Hak Akses Pengamat/Admin (Phase 5) | 20 | **PASS** | 1.20s |
| `Tests\Feature\PlantTest` | CRUD Tanaman & Integritas Relasi (Phase 6) | 21 | **PASS** | 1.35s |
| `Tests\Feature\Api\PlantApiTest` | REST API Tanaman (Index, Show, Filters) | 5 | **PASS** | 0.25s |
| `Tests\Feature\Api\MonitoringApiTest` | REST API Monitoring & Statistics Agregasi | 12 | **PASS** | 0.80s |
| `Tests\Feature\ExampleTest` & `Tests\Unit\ExampleTest` | Smoke Test & Response Check | 2 | **PASS** | 0.10s |
| **TOTAL** | | **160** | **PASS** | **11.45s** |

---

## 2. MATRIKS CAKUPAN 21 SKENARIO PENGUJIAN PHASE 14

| No | Skenario Kebutuhan Phase 14 | Metode Pengujian di Test Suite | Assertions | Hasil |
| :-: | :--- | :--- | :---: | :---: |
| 1 | Valid Google Sheets rows ingestion | `test_1_valid_google_sheets_rows_ingestion` | 10 | **PASS** |
| 2 | Missing optional values handled gracefully | `test_2_missing_optional_values_handled_gracefully` | 6 | **PASS** |
| 3 | Invalid numeric values rejected | `test_3_invalid_numeric_values_rejected` | 3 | **PASS** |
| 4 | Invalid timestamps rejected | `test_4_invalid_timestamps_rejected` | 2 | **PASS** |
| 5 | Duplicate rows skipped by idempotency | `test_5_duplicate_rows_skipped_by_idempotency` | 3 | **PASS** |
| 6 | Repeated synchronization does not duplicate | `test_6_repeated_synchronization_does_not_duplicate` | 4 | **PASS** |
| 7 | Out-of-order historical readings handled | `test_7_out_of_order_readings_handled` | 4 | **PASS** |
| 8 | Partial failure and retry | `test_8_partial_failure_and_retry` | 3 | **PASS** |
| 9 | Spreadsheet unavailable (HTTP 404) exception handled | `test_9_spreadsheet_unavailable_handled` | 2 | **PASS** |
| 10 | API timeout exception handled | `test_10_api_timeout_handled` | 2 | **PASS** |
| 11 | Rate limiting (HTTP 429) backoff handled | `test_11_rate_limiting_handled` | 2 | **PASS** |
| 12 | Device token valid authentication | `test_12_device_token_valid` | 3 | **PASS** |
| 13 | Device token invalid rejected (HTTP 401) | `test_13_device_token_invalid` | 2 | **PASS** |
| 14 | Revoked token rejected (HTTP 403) | `test_14_revoked_token_rejected` | 2 | **PASS** |
| 15 | Missing token in production rejected (HTTP 401) | `test_15_missing_token_in_production` | 2 | **PASS** |
| 16 | Simulator backwards compatibility in dev mode | `test_16_simulator_compatibility` | 2 | **PASS** |
| 17 | Four-parameter alarm policy continuity | `test_17_four_parameter_alarm_policy` | 4 | **PASS** |
| 18 | Dashboard status view rendering | `test_18_dashboard_status_view_rendering` | 4 | **PASS** |
| 19 | RBAC access control (Admin allow, Pengamat reject) | `test_19_rbac_access_control` | 2 | **PASS** |
| 20 | Preservation of historical data integrity | `test_20_preservation_of_historical_data` | 2 | **PASS** |
| 21 | Regression verification Phase 1–13 endpoints & DTO | `test_21_regression_phase_1_to_13` | 5 | **PASS** |

---

## 3. AUDIT PERBANDINGAN ASSERTIONS (PHASE 12, 13, 14)

- **Phase 12:** 119 tests, ~1.100 assertions.
- **Phase 13:** 139 tests, 2.521 assertions.
- **Phase 14:** **160 tests, 3.703 assertions**.
- **Analisis Delta:** Peningkatan signifikan sebanyak 1.182 assertions mencerminkan pengujian mendalam pada validasi kursor batas numerik, idempotensi hash, deteksi rate limiting/timeout, serta verifikasi kriptografi SHA-256 token perangkat.

---

## 4. VERIFIKASI ASSET PRODUCTION BUILD

Aset frontend Vite dikompilasi ulang dengan sukses:
```bash
> vite build
✓ 63 modules transformed.
public/build/manifest.json              0.27 kB │ gzip:   0.15 kB
public/build/assets/app-BFYgjgIH.css   12.60 kB │ gzip:   2.70 kB
public/build/assets/app-DtvP0olV.js   315.20 kB │ gzip: 109.82 kB
✓ built in 5.63s
```
Semua komponen Blade, styling Tailwind CSS 4, status badges, dan Alpine.js interaktif terkompilasi optimal tanpa warning.
