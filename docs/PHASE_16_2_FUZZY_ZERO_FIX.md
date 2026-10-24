# Phase 16.2 — External Fuzzy Zero Fix

Project: **Sistem Web Monitoring Tanaman Melon** — `D:\Compro`.
Tanggal penyelesaian: **24 Oktober 2026 (Asia/Jakarta)**. Pekerjaan dimulai 22 Oktober dan dilanjutkan setelah interupsi.

## Root cause

FuzzyPipelineService memakai !empty(externalPayload.score). PHP menganggap 0, 0.0 dan string "0" empty, sehingga payload eksternal dilewati dan local Mamdani dapat menggantinya dengan 86.17 BAIK. Ini hasil yang menyesatkan, bukan sekadar format tampilan.

## Implementasi dan affected files

ExternalFuzzyScore::normalize memeriksa tipe numeric, is_finite dan domain sebelum pembulatan dua desimal. Digunakan FormRequest/API, Sheets parser dan FuzzyPipelineService. Validasi payload eksternal dilakukan sebelum catch isolasi kalkulasi agar input invalid tidak berubah menjadi hasil lokal yang terlihat valid.

Pipeline memakai pemeriksaan null/keberadaan payload, bukan truthiness. Kategori berasal dari MamdaniInferenceService::resolveCategory dan config/fuzzy.php existing; aturan/membership/threshold tidak diubah. API menyimpan source inference api_payload untuk external score, api_ingest untuk kalkulasi lokal. Sheets memakai google_sheets_payload untuk external score. Label fuzzy pada observasi baru dinormalisasi ke kategori Laravel, termasuk bila label client berbeda.

Inferensi eksternal memakai firstOrCreate pada monitoring/rule/source: pemrosesan ulang tidak menimpa record existing. Ini sengaja tidak memperbaiki histori lama secara diam-diam. Revisi yang disengaja memerlukan workflow/versi baru, bukan mengubah histori tersimpan.

Affected files: ExternalFuzzyScore.php, FuzzyPipelineService.php, StoreMonitoringApiRequest.php, API MonitoringController.php, MonitoringResource.php, GoogleSheetsTelemetryService.php dan GoogleSheetsSyncService.php.

## Kontrak nilai dan kategori

| Input | Expected / Actual |
| --- | --- |
| 0, 0.0, "0" | Valid → 0.00 / BURUK; tidak diubah menjadi null |
| 0.01 | BURUK |
| 39.99 | BURUK |
| 40 | PERLU_PERHATIAN |
| 64.99 | PERLU_PERHATIAN |
| 65 | BAIK |
| 100 | BAIK |
| null / field hilang | Tidak tersedia sebagai hasil eksternal; bukan nol |
| Empty string API | 422, termasuk raw JSON yang biasanya diubah middleware menjadi null |
| Blank optional Sheets cell | Tidak tersedia sesuai kontrak sel opsional |
| Negative / >100 / nonnumeric | Invalid; API 422, row Sheets gagal validasi |
| NaN / Infinity / -Infinity | Invalid; nilai native dan string diuji |
| Boolean / array | Invalid |


Numeric strings memakai representasi desimal numerik standar dengan titik. Jika score null/hilang pada API, local inference tetap boleh berjalan sebagai api_ingest; tidak dibuat record external palsu. Pada pemanggilan pipeline dengan external payload explicit tetapi score null/hilang, hasil eksternal tidak disimpan.

## Integrasi dan tests

Phase16_2FuzzyZeroTest menguji 9 input/boundary valid, 8 kelompok invalid, native nonfinite, null/missing, immutability histori eksternal, Sheets zero + repeated sync, dan tampilan semantik history/dashboard serta isi CSV. Database assertion memeriksa skor dan kategori, bukan hanya HTTP status.

Kasus Sheets dengan score "0" dan label client BAIK kini tersimpan sebagai **0/BURUK** pada monitoring maupun inference. API response mempertahankan nol. View dashboard/history/CSV/PDF existing memakai pemeriksaan !==null atau null coalescing yang mempertahankan nol; suite reporting tetap lulus. Tidak mengklaim seluruh visual UI/PDF telah diperbaiki: CSS dan legenda PDF masih risiko fase lain.

## Data historis

Heuristik read-only raw score0 vs nonzero inference menemukan **0 kandidat** di database lokal. Ini bukan bukti bahwa tidak pernah ada data terdampak: payload lama yang tidak tersimpan tidak dapat direkonstruksi. Tidak ada recalc/backfill atau update histori lama. Count dan checksum 493 inference existing tetap identik.

Jika ditemukan bukti payload asli yang terdampak, rencana terpisah adalah identifikasi event/sumber, review stakeholder, simpan versi koreksi dengan audit trail dan pertahankan hasil lama. Status agronomis rule tetap **PROVISIONAL_FOR_SOFTWARE_TESTING**.
