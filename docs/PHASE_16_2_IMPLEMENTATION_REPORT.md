# Phase 16.2 — Implementation Report

Project: **Sistem Web Monitoring Tanaman Melon** — `D:\Compro`.
Tanggal penyelesaian: **24 Oktober 2026 (Asia/Jakarta)**. Pekerjaan dimulai 22 Oktober dan dilanjutkan setelah interupsi.

## Status fase

**PASS untuk acceptance perangkat lunak lokal yang diverifikasi (SQLite).** Ketiga bug utama diperbaiki dan regresi penuh lulus. Status ini **bukan** kesiapan production, kepatuhan seluruh SRS, atau keberhasilan integrasi fisik. MySQL, Google Sheets live dan ESP32 tetap NOT VERIFIED/EXTERNAL DEPENDENCY. Kesiapan project secara keseluruhan tetap **NOT READY** karena blocker di luar fase, terutama UI.

## Pre-implementation dan lingkup

Membaca laporan master, gap, security, coverage, RTM dan readiness serta bukti audit; kemudian mencocokkan temuan terhadap routes, middleware, controller, FormRequest, schema, services fuzzy/Sheets dan tests. Status Git awal tersimpan di git-before.txt. Project sudah memiliki banyak perubahan staged/unstaged; perubahan tersebut dipertahankan. Baseline baru dijalankan pada salinan database: 237 tests PASS/15.133 assertions. PHP 8.2.12 dan ekstensi Composer required tersedia. Tidak membuat project baru atau mengembangkan Phase 17/16.3.

## Root cause dan perubahan

1. **Guest API read:** GET routes tidak memakai autentikasi. Sekarang sesi web pengguna dibaca melalui middleware internal yang memberi 401 untuk guest/expired session, 403 untuk role tanpa izin atau token perangkat saja. Authorization berjalan sebelum model binding.
2. **Duplicate ingestion:** controller selalu create, source_identifier lama bukan unique dan pre-check Sheets mempunyai race serta fallback plant+time terlalu luas. Sekarang service bersama memakai ledger dengan primary key unik, transaksi, fingerprint payload, replay dan conflict yang eksplisit.
3. **External zero:** empty(score) menganggap 0 tidak dikirim. Sekarang normalisasi numeric finite 0–100 mempertahankan nol, kategori memakai config existing, dan inferensi eksternal yang telah tersimpan tidak ditimpa pada pemrosesan ulang.

## Hasil verifikasi

| Pemeriksaan | Hasil | Bukti |
| --- | --- | --- |
| Baseline baru sebelum implementasi | 237 PASS; 15.133 assertions; salinan database | phase16_2/evidence/baseline.xml |
| Regresi penuh setelah perbaikan | 297 PASS; 2075 assertions; 0 failed/error/skipped | phase16_2/evidence/regression.xml |
| Test baru | 60 cases; 274 assertions | Phase16_2ApiSecurityTest / DeduplicationTest / FuzzyZeroTest |
| Production build | PASS; Vite 6.4.4, 63 modules | phase16_2/evidence/build.txt |
| Routes | 55; seluruhnya diklasifikasikan | phase16_2/evidence/endpoint-classification.json |
| Migration status | 12 Ran, termasuk ledger baru | phase16_2/evidence/migrations-after.txt |
| Concurrency nyata | 10 proses dilepas melalui barrier; 1×201, 9×200; 1 monitoring, 1 inferensi | phase16_2/evidence/concurrent.json |
| Simulator Python | CLI 201; replay 201→200 dengan ID sama dan skor 0 | phase16_2/evidence/simulator.json |
| Preservasi data | 18 tabel historis: count dan SHA-256 isi identik | phase16_2/evidence/history-before.json / history-after.json |
| PHP syntax | 30 file berubah lulus php -l | phase16_2/evidence/verification.json |

[Bukti verifikasi](phase16_2/evidence/verification.json). Penurunan assertions dari baseline bukan penurunan jumlah test: fixture baru deterministik di SQLite memori, sedangkan baseline lama mengiterasi 959 record existing. Tetap ada 237 test existing dan ditambah 60 case baru.

## File aplikasi, client dan test yang berubah pada fase ini

- `app/Http/Controllers/Api/MonitoringController.php`
- `app/Http/Middleware/AuthenticateDeviceToken.php`
- `app/Http/Middleware/AuthenticateInternalApi.php`
- `app/Http/Requests/Api/StoreMonitoringApiRequest.php`
- `app/Http/Resources/MonitoringResource.php`
- `app/Services/FuzzyLogic/ExternalFuzzyScore.php`
- `app/Services/FuzzyLogic/FuzzyPipelineService.php`
- `app/Services/GoogleSheets/GoogleSheetsSyncService.php`
- `app/Services/GoogleSheets/GoogleSheetsTelemetryService.php`
- `app/Services/MonitoringIngestionService.php`
- `bootstrap/app.php`
- `config/services.php`
- `database/migrations/2026_10_22_000001_create_monitoring_ingestion_keys_table.php`
- `iot-simulator/README.md`
- `iot-simulator/simulator.py`
- `phpunit.xml`
- `routes/api.php`
- `routes/web.php`
- `tests/ApiTestCase.php`
- `tests/Feature/AlarmTest.php`
- `tests/Feature/Api/DashboardApiTest.php`
- `tests/Feature/Api/MonitoringApiTest.php`
- `tests/Feature/Api/MonitoringIngestionTest.php`
- `tests/Feature/Api/PlantApiTest.php`
- `tests/Feature/FuzzyLogicInferenceTest.php`
- `tests/Feature/GoogleSheetsTelemetryAndSecurityTest.php`
- `tests/Feature/MonitoringParameterExpansionTest.php`
- `tests/Feature/Phase16_2ApiSecurityTest.php`
- `tests/Feature/Phase16_2DeduplicationTest.php`
- `tests/Feature/Phase16_2FuzzyZeroTest.php`
- `tests/Support/ingestion_worker.php`
- `tests/TestCase.php`
- `tests/bootstrap.php`
- `docs/DEVICE_AUTHENTICATION_GUIDE.md`
- `docs/GOOGLE_SHEETS_INTEGRATION_CONTRACT.md`
- `docs/TELEMETRY_DATA_MAPPING.md`

Daftar berasal dari hash source sebelum/sesudah dan file root yang diubah eksplisit, bukan seluruh dirty Git project. Enam laporan fase ini ditambahkan bersama skrip bukti di docs/phase16_2. Tidak mengubah SRS, threshold, membership atau rule base.

## Migration dan preservasi

Migration baru: `2026_10_22_000001_create_monitoring_ingestion_keys_table.php`. Hanya menambah tabel ledger; tidak memasang unique index pada identifier historis yang mungkin sudah duplikat. Migration diterapkan pada SQLite lokal setelah pengujian dan backup, sehingga aplikasi tidak menunggu tabel yang belum ada. Status akhir 12 migration Ran.

| Tabel historis | Record sebelum / sesudah | Hash isi |
| --- | --- | --- |
| monitorings | 959 / 959 | IDENTIK |
| monitoring_alarms | 1 / 1 | IDENTIK |
| fuzzy_inference_results | 493 / 493 | IDENTIK |
| actuator_command_logs | 1 / 1 | IDENTIK |
| plants | 150 / 150 | IDENTIK |
| users | 10 / 10 | IDENTIK |
| iot_devices | 3 / 3 | IDENTIK |

Hash isi seluruh 18 tabel existing sama. Hash keseluruhan file SQLite berubah secara wajar karena schema dan metadata migration bertambah. Backup pre-migration disimpan lokal dan diabaikan Git; tidak menggunakannya sebagai database test. Tidak menjalankan migrate:fresh/db:wipe, mass deletion, koreksi histori fuzzy, seeding atau telemetry test pada database utama.

## Kontrak dan kompatibilitas

Token perangkat kini wajib pada semua environment; mode tanpa token dihentikan. API baca memerlukan sesi pengguna, tidak cukup token perangkat. Simulator tetap memakai urllib dan payload sensor yang sama, dengan event_id stabil untuk retry; Python portable dipasang terisolasi untuk verifikasi. Respons observation baru 201, replay identik 200, conflict 409, validasi 422. Sheets tetap 15 kolom A:O, tetapi namespace dedup kini membedakan sumber dan perangkat. Label fuzzy baru diklasifikasikan dari skor; histori lama tidak direklasifikasi.

## Acceptance criteria

| Kriteria | Actual result |
| --- | --- |
| Endpoint privat terlindungi; guest tidak membaca data internal | PASS pada seluruh read API dan existing protected web routes. |
| Admin/pengamat/device diberi hak yang tepat | PASS; token valid ingestion 201, token absent/invalid 401, revoked 403, device read 403. |
| Retry tidak menggandakan; observasi berbeda tetap disimpan | PASS; 10 retry, timestamp/event berbeda, subsecond, source/device berbeda dan payload conflict diuji. |
| Concurrent ingestion aman | PASS pada SQLite: 10 proses HTTP kernel, shared DB, barrier, unique ledger dan tepat satu hasil. MySQL belum diuji. |
| External zero valid dan histori aman | PASS; zero/boundaries/invalid/nonfinite serta pemrosesan ulang diuji. |
| Seluruh tests dan production build | PASS; 297 tests / 2075 assertions; build PASS. |
| Tidak ada critical/high regression baru yang ditemukan | PASS dalam suite/scope yang diuji; bukan penetration-test atau audit global ulang. |
| Data historis aman | PASS; 18 checksum tabel identik. |

## Dokumentasi rinci dan keputusan selanjutnya

- [Keamanan API](PHASE_16_2_SECURITY_FIX.md)
- [Deduplikasi](PHASE_16_2_DEDUPLICATION_FIX.md)
- [Fuzzy zero](PHASE_16_2_FUZZY_ZERO_FIX.md)
- [Regresi](PHASE_16_2_REGRESSION_TEST_RESULTS.md)
- [Risiko tersisa](PHASE_16_2_REMAINING_RISKS.md)

**Siap merencanakan Phase 16.3**, terutama penanganan UI dan risiko yang belum ditutup. Phase 16.3 tidak dimulai otomatis. **STOP setelah Phase 16.2.**
