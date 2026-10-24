# Phase 16.2 — Regression Test Results

Project: **Sistem Web Monitoring Tanaman Melon** — `D:\Compro`.
Tanggal penyelesaian: **24 Oktober 2026 (Asia/Jakarta)**. Pekerjaan dimulai 22 Oktober dan dilanjutkan setelah interupsi.

## Ringkasan hasil aktual

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

Hasil lengkap: [regression.xml](phase16_2/evidence/regression.xml) dan [regression.txt](phase16_2/evidence/regression.txt). Semua existing suites authentication, RBAC, plants, monitoring, device tokens, Sheets mock, fuzzy math/pipeline, alarm4 parameter, actuator simulation, dashboard dan reports lulus. Test baru tidak mengganti 237 test existing; jumlah akhirnya 297.

## Test environment yang aman

phpunit.xml memakai tests/bootstrap.php. Bootstrap memaksa APP_ENV=testing, DB_CONNECTION=sqlite, DB_DATABASE=:memory:, DB_URL kosong, cache/session array dan path config-cache khusus yang tidak memakai cached config aplikasi. Tests\TestCase menolak database selain memori sebelum menjalankan migrasi. Setiap test memakai app/connection baru, migrate biasa pada memori dan fixture deterministic. Tidak memakai migrate:fresh/db:wipe atau database demo untuk destructive testing.

Satu test concurrency sengaja membuat file SQLite disposable bernama phase162-* di temp, memigrasikannya, lalu menjalankan 10 worker HTTP kernel independen. Semua worker harus mencapai barrier sebelum dilepas. Worker memiliki guard path yang menolak target selain file temporary tersebut. File/marker ditutup dan dibersihkan setelah test. Ini tidak mengubah database utama.

Legacy positive API tests kini memakai authenticated user/device fixtures eksplisit. Negative tests tetap mengirim request tanpa token/session, invalid/revoked token dan role tanpa izin. Tidak menonaktifkan auth agar test lama lolos. Cookie valid/expired benar-benar dikirim memakai withCredentials dan melalui middleware cookie/session.

## Mengapa assertion count lebih rendah

Baseline 15.133 assertions diperoleh dari salinan database existing dengan 150 tanaman dan 959 monitoring. Banyak assertion struktur diulang untuk setiap record API. Fixture baru tetap (3 tanaman/21 seeded monitoring sebelum data tiap test), sehingga assertion count turun menjadi 2075, walaupun test bertambah 60. Ini perubahan denominator dataset, bukan penghapusan test; tidak mengartikan banyak assertion sebagai feature completeness.

## API, idempotency dan fuzzy

- Guest/private reads 401, device/private reads 403, observer/admin operations 403; valid user cookie 200 dan expired cookie 401.
- Device ingestion valid 201; absent/invalid token401 dan revoked403. Identity spoof tidak diterima.
- Retry10× menghasilkan satu ID; concurrency menghasilkan satu monitoring, satu inference dan satu alarm.
- Perbedaan timestamp, event ID, source, device dan subsecond tidak dibuang. Key reuse dengan payload berbeda mendapat409.
- Semua boundary fuzzy termasuk0 diuji dengan DB/body assertions. Histori eksternal tidak ditimpa; null/missing bukan score0.

## Simulator dan integrasi eksternal

Python **3.13.17 portable** dari [rilis Windows resmi Python](https://www.python.org/downloads/windows/) dipasang pada folder tools yang diabaikan Git. Tidak menambah dependency Laravel/npm atau mengubah PATH global. Simulator CLI --once benar-benar dijalankan ke server HTTP lokal dengan salinan database serta token disposable; mendapat201. Probe fungsi send_reading mengirim payload yang sama dua kali:201→200, ID sama, score0 dan source simulator. Token tidak disimpan di laporan/command line probe, melainkan diteruskan melalui environment/stdin.

Sheets mock import/replay/dua perangkat/score0 berhasil. Sandbox/live Google Sheets dan ESP32 fisik **NOT EXECUTED**, sehingga tidak dinyatakan PASS. MySQL Docker concurrency dan load/distributed testing juga belum dieksekusi. Tidak mengklaim code coverage LOC/branch atau performance p95; suite ini adalah verifikasi fungsional.

## Daftar testcase baru yang benar-benar dijalankan

| File / class | Testcase | Assertions | Actual |
| --- | --- | --- | --- |
| tests/Feature/Phase16_2ApiSecurityTest.php | test_guest_cannot_read_private_endpoints with data set #0 | 1 | PASS |
| tests/Feature/Phase16_2ApiSecurityTest.php | test_guest_cannot_read_private_endpoints with data set #1 | 1 | PASS |
| tests/Feature/Phase16_2ApiSecurityTest.php | test_guest_cannot_read_private_endpoints with data set #2 | 1 | PASS |
| tests/Feature/Phase16_2ApiSecurityTest.php | test_guest_cannot_read_private_endpoints with data set #3 | 1 | PASS |
| tests/Feature/Phase16_2ApiSecurityTest.php | test_guest_cannot_read_private_endpoints with data set #4 | 1 | PASS |
| tests/Feature/Phase16_2ApiSecurityTest.php | test_guest_cannot_read_private_endpoints with data set #5 | 1 | PASS |
| tests/Feature/Phase16_2ApiSecurityTest.php | test_guest_cannot_read_private_endpoints with data set #6 | 1 | PASS |
| tests/Feature/Phase16_2ApiSecurityTest.php | test_authorized_observer_can_read_internal_data with data set #0 | 1 | PASS |
| tests/Feature/Phase16_2ApiSecurityTest.php | test_authorized_observer_can_read_internal_data with data set #1 | 1 | PASS |
| tests/Feature/Phase16_2ApiSecurityTest.php | test_authorized_observer_can_read_internal_data with data set #2 | 1 | PASS |
| tests/Feature/Phase16_2ApiSecurityTest.php | test_authorized_observer_can_read_internal_data with data set #3 | 1 | PASS |
| tests/Feature/Phase16_2ApiSecurityTest.php | test_authorized_observer_can_read_internal_data with data set #4 | 1 | PASS |
| tests/Feature/Phase16_2ApiSecurityTest.php | test_authorized_observer_can_read_internal_data with data set #5 | 1 | PASS |
| tests/Feature/Phase16_2ApiSecurityTest.php | test_authorized_observer_can_read_internal_data with data set #6 | 1 | PASS |
| tests/Feature/Phase16_2ApiSecurityTest.php | test_token_only_device_cannot_read_private_data | 14 | PASS |
| tests/Feature/Phase16_2ApiSecurityTest.php | test_unknown_user_role_has_no_read_permission | 1 | PASS |
| tests/Feature/Phase16_2ApiSecurityTest.php | test_authentication_runs_before_private_model_binding | 3 | PASS |
| tests/Feature/Phase16_2ApiSecurityTest.php | test_observer_cannot_use_admin_endpoints_but_admin_can | 15 | PASS |
| tests/Feature/Phase16_2ApiSecurityTest.php | test_admin_session_cannot_substitute_for_device_token | 2 | PASS |
| tests/Feature/Phase16_2ApiSecurityTest.php | test_valid_invalid_missing_and_revoked_device_auth | 8 | PASS |
| tests/Feature/Phase16_2ApiSecurityTest.php | test_expired_database_session_is_unauthorized | 2 | PASS |
| tests/Feature/Phase16_2ApiSecurityTest.php | test_valid_encrypted_session_cookie_can_read_api_without_acting_as | 7 | PASS |
| tests/Feature/Phase16_2DeduplicationTest.php | test_ten_concurrent_http_kernel_requests_commit_exactly_once | 18 | PASS |
| tests/Feature/Phase16_2DeduplicationTest.php | test_additive_migration_and_rollback_preserve_legacy_records | 3 | PASS |
| tests/Feature/Phase16_2DeduplicationTest.php | test_ten_identical_api_retries_create_one_observation_and_inference | 33 | PASS |
| tests/Feature/Phase16_2DeduplicationTest.php | test_timestamp_fallback_normalizes_equivalent_timezones | 3 | PASS |
| tests/Feature/Phase16_2DeduplicationTest.php | test_distinct_subsecond_events_are_not_collapsed | 5 | PASS |
| tests/Feature/Phase16_2DeduplicationTest.php | test_identical_sensor_values_at_different_times_or_event_ids_are_preserved | 5 | PASS |
| tests/Feature/Phase16_2DeduplicationTest.php | test_changed_payload_with_same_key_is_conflict_without_overwrite | 5 | PASS |
| tests/Feature/Phase16_2DeduplicationTest.php | test_invalid_idempotency_key_is_rejected with data set #0 | 4 | PASS |
| tests/Feature/Phase16_2DeduplicationTest.php | test_invalid_idempotency_key_is_rejected with data set #1 | 4 | PASS |
| tests/Feature/Phase16_2DeduplicationTest.php | test_invalid_idempotency_key_is_rejected with data set #2 | 4 | PASS |
| tests/Feature/Phase16_2DeduplicationTest.php | test_invalid_idempotency_key_is_rejected with data set #3 | 4 | PASS |
| tests/Feature/Phase16_2DeduplicationTest.php | test_invalid_idempotency_key_is_rejected with data set #4 | 4 | PASS |
| tests/Feature/Phase16_2DeduplicationTest.php | test_conflicting_header_body_keys_are_rejected | 1 | PASS |
| tests/Feature/Phase16_2DeduplicationTest.php | test_same_data_from_different_sources_and_devices_are_not_lost | 1 | PASS |
| tests/Feature/Phase16_2DeduplicationTest.php | test_legacy_duplicates_are_reported_as_conflict_not_deleted | 3 | PASS |
| tests/Feature/Phase16_2DeduplicationTest.php | test_unambiguous_legacy_event_is_replayed_without_rewriting_history | 3 | PASS |
| tests/Feature/Phase16_2FuzzyZeroTest.php | test_api_preserves_external_score_and_uses_existing_category with data set #0 | 5 | PASS |
| tests/Feature/Phase16_2FuzzyZeroTest.php | test_api_preserves_external_score_and_uses_existing_category with data set #1 | 5 | PASS |
| tests/Feature/Phase16_2FuzzyZeroTest.php | test_api_preserves_external_score_and_uses_existing_category with data set #2 | 5 | PASS |
| tests/Feature/Phase16_2FuzzyZeroTest.php | test_api_preserves_external_score_and_uses_existing_category with data set #3 | 5 | PASS |
| tests/Feature/Phase16_2FuzzyZeroTest.php | test_api_preserves_external_score_and_uses_existing_category with data set #4 | 5 | PASS |
| tests/Feature/Phase16_2FuzzyZeroTest.php | test_api_preserves_external_score_and_uses_existing_category with data set #5 | 5 | PASS |
| tests/Feature/Phase16_2FuzzyZeroTest.php | test_api_preserves_external_score_and_uses_existing_category with data set #6 | 5 | PASS |
| tests/Feature/Phase16_2FuzzyZeroTest.php | test_api_preserves_external_score_and_uses_existing_category with data set #7 | 5 | PASS |
| tests/Feature/Phase16_2FuzzyZeroTest.php | test_api_preserves_external_score_and_uses_existing_category with data set #8 | 5 | PASS |
| tests/Feature/Phase16_2FuzzyZeroTest.php | test_invalid_external_score_cannot_store_observation with data set #0 | 4 | PASS |
| tests/Feature/Phase16_2FuzzyZeroTest.php | test_invalid_external_score_cannot_store_observation with data set #1 | 4 | PASS |
| tests/Feature/Phase16_2FuzzyZeroTest.php | test_invalid_external_score_cannot_store_observation with data set #2 | 4 | PASS |
| tests/Feature/Phase16_2FuzzyZeroTest.php | test_invalid_external_score_cannot_store_observation with data set #3 | 4 | PASS |
| tests/Feature/Phase16_2FuzzyZeroTest.php | test_invalid_external_score_cannot_store_observation with data set #4 | 4 | PASS |
| tests/Feature/Phase16_2FuzzyZeroTest.php | test_invalid_external_score_cannot_store_observation with data set #5 | 4 | PASS |
| tests/Feature/Phase16_2FuzzyZeroTest.php | test_invalid_external_score_cannot_store_observation with data set #6 | 4 | PASS |
| tests/Feature/Phase16_2FuzzyZeroTest.php | test_invalid_external_score_cannot_store_observation with data set #7 | 4 | PASS |
| tests/Feature/Phase16_2FuzzyZeroTest.php | test_nonfinite_native_values_are_rejected | 3 | PASS |
| tests/Feature/Phase16_2FuzzyZeroTest.php | test_null_or_missing_external_score_is_unavailable_not_external_zero | 7 | PASS |
| tests/Feature/Phase16_2FuzzyZeroTest.php | test_external_zero_history_is_not_overwritten_on_reprocessing | 2 | PASS |
| tests/Feature/Phase16_2FuzzyZeroTest.php | test_sheet_zero_import_replay_and_different_device_are_safe | 11 | PASS |
| tests/Feature/Phase16_2FuzzyZeroTest.php | test_zero_is_visible_in_history_dashboard_and_csv | 8 | PASS |

## Preservasi database utama

| Tabel historis | Record sebelum / sesudah | Hash isi |
| --- | --- | --- |
| monitorings | 959 / 959 | IDENTIK |
| monitoring_alarms | 1 / 1 | IDENTIK |
| fuzzy_inference_results | 493 / 493 | IDENTIK |
| actuator_command_logs | 1 / 1 | IDENTIK |
| plants | 150 / 150 | IDENTIK |
| users | 10 / 10 | IDENTIK |
| iot_devices | 3 / 3 | IDENTIK |

Semua 18 tabel existing juga diperiksa, termasuk konfigurasi/cache/session. Migration baru dan tabel ledger dikecualikan dari perbandingan isi historis karena memang merupakan metadata tambahan. Tidak menghapus kandidat duplicate existing atau mengoreksi fuzzy history lama.
