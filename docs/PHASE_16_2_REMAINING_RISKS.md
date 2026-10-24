# Phase 16.2 — Remaining Risks & Readiness

Project: **Sistem Web Monitoring Tanaman Melon** — `D:\Compro`.
Tanggal penyelesaian: **24 Oktober 2026 (Asia/Jakarta)**. Pekerjaan dimulai 22 Oktober dan dilanjutkan setelah interupsi.

## Keputusan

**Phase 16.2 PASS pada scope software lokal SQLite yang telah diverifikasi. Project keseluruhan tetap NOT READY untuk demonstrasi kelengkapan SRS atau production.** Tidak ada klaim baru bahwa semua fitur selesai. Skor audit 51,6% functional completeness dan 28,6% compliance adalah baseline audit 22 Oktober; tidak dihitung ulang atau dinaikkan hanya karena fase ini lulus.

## Risiko yang belum ditutup

| Prioritas | Risiko | Status / tindakan selanjutnya |
| --- | --- | --- |
| P1 | Debug routes dan konfigurasi production | Tiga route _ignition terdaftar pada runtime development dan memakai guard package, bukan login bisnis. APP_DEBUG/TLS/cookies production belum divalidasi; jangan membuka server development ke internet. |
| P0 | Tampilan Tailwind rusak (G01) | Belum diperbaiki; fase ini tidak mencakup CSS/UI. Build PASS tidak berarti visual PASS. |
| P1 | Preset token development (G13) | Token preset yang telah terdaftar belum dirotasi otomatis. Jangan gunakan seed development untuk production; lakukan rotasi/revocation terencana. |
| P1 | Advisori dependency dan lifecycle Laravel (G17) | Audit sebelumnya menemukan advisori; tidak upgrade framework/PHP/dependency dalam fase ini. Triage sebelum publikasi. |
| P1 | MySQL dan concurrency lintas server | DDL dibuat portable tetapi migration/parallel workload aktual MySQL Docker belum dieksekusi; bukti concurrency fase ini pada SQLite saja. |
| P1 | Cursor Sheets (G06) | Masih append-only; edit/insert/sort baris lama tidak otomatis direkonsiliasi. Row invalid tetap dapat memajukan cursor sesuai implementasi existing. |
| P1 | Mapping perangkat/tanaman Sheets (G07) | Registry revoked/unknown dan fallback plant belum diperketat; token REST tidak secara otomatis memvalidasi isi spreadsheet. |
| P1 | Live Google Sheets dan ESP32 | Kredensial sandbox/live dan hardware belum diverifikasi. Mock bukan bukti perangkat fisik. |
| P1 | Toggle fuzzy semua ingress (G05) | Guard lokal existing belum diseragamkan; tidak termasuk tiga bug utama fase ini. |
| P1 | Filter ringkasan laporan (G08) | Source/condition dan tanggal inferensi masih dapat berbeda dari scope observasi. |
| P1 | Kontrak sensor/threshold/aturan agronomi | pH opsional, rentang sensor, dan konflik SRS/Elektro belum diselesaikan; fuzzy tetap PROVISIONAL. |
| P1 | Scheduler dan sampling grafik (G11/G12) | Sync terjadwal belum terpasang dan grafik masih memilih limit lama. |
| P1 | Rate limit dan volume baca (G14) | Login/read API belum diberi limiter khusus; API daftar masih tanpa pagination. |
| P1 | PDF (G22) | Nomor halaman dan legenda kategori PDF belum diperbaiki. |
| P2 | Histori lama tanpa perangkat | 721 telemetry rows tidak mempunyai device_id; tidak aman menggabungkan/reklasifikasi otomatis. |
| P2 | Timestamp tampilan | Ledger mempertahankan mikrodetik; monitorings dan tampilan lama masih memakai resolusi detik. Gunakan event_id stabil untuk identitas observasi. |
| P2 | Rollback ledger | Rollback menghilangkan metadata idempotensi baru, bukan observasi. Perlu menghentikan ingress dan merencanakan pemulihan metadata sebelum rollback operasional. |

## Batas penerimaan

Bukti SQLite mendukung unique-key enforcement dan transaksi di driver yang benar-benar diuji. Portabilitas schema bukan pengganti pengujian MySQL. Sebelum deployment target MySQL, jalankan migration, concurrency race, FK, rollback dan replay pada database MySQL terisolasi. Jangan menjalankan tes terhadap MySQL utama.

Sumber/source identification diperbaiki untuk ingestion baru. Data historis api yang sebenarnya simulator atau telemetry tanpa device tidak direklasifikasi otomatis. Identity fallback tidak bisa menebak dua event berbeda yang benar-benar mempunyai semua identity attributes sama: client harus memakai event ID berbeda. Payload berbeda pada identity sama menghasilkan conflict eksplisit, bukan dipilih diam-diam.

External inference existing immutable pada key monitoring/rule/source; koreksi yang disengaja membutuhkan prosedur/versioning terpisah. Ketidaktersediaan score eksternal tetap dibedakan dari0, dan local inference tidak dianggap hasil ESP32.

## Kesiapan Phase 16.3

**Siap direncanakan**, dengan prioritas UI blocker, hardening token/dependency, MySQL verification dan konsistensi laporan/Sheets. Lingkup perlu ditetapkan melalui instruksi berikutnya. Tidak mengembangkan Phase16.3/17, mengaktifkan relay, mengubah rule/threshold, atau mengoreksi histori dalam fase ini.

## Berkas bukti

[Verification](phase16_2/evidence/verification.json), [route classification](phase16_2/evidence/endpoint-classification.json), [concurrency](phase16_2/evidence/concurrent.json), [simulator](phase16_2/evidence/simulator.json), [legacy findings](phase16_2/evidence/legacy-findings.json). Backup database mengandung data lokal dan sengaja tidak dijadikan deliverable publik atau dimasukkan Git.

**STOP setelah Phase 16.2.**
