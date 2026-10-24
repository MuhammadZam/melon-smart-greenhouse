# Phase 16.3 — MySQL Compatibility

Tanggal: **24 Oktober 2026 (Asia/Jakarta)**. Project existing: `D:\Compro`. Lingkup: pemulihan UI; tidak memulai Phase 16.4.

## Gate: PASS sebelum implementasi UI

Docker awalnya tidak berjalan; startup biasa terbentur akses direktori/pipe. Setelah Docker Desktop dijalankan dan Docker CLI mendapat akses yang diperlukan, container baru **compro-phase163-mysql**, image mysql:8.0, port loopback3319, database **phase163_test**, berhasil digunakan. Server melaporkan **8.0.46**. Tidak memakai compose project, port3308 atau volume MySQL utama. Kredensial acak disimpan lokal dan diabaikan Git; tidak dicetak dalam laporan.

| Pemeriksaan aktual | Hasil |
| --- | --- |
| migrations | PASS |
| guest_read_401 | PASS |
| missing_device_token_401 | PASS |
| invalid_device_token_401 | PASS |
| ten_workers_at_barrier | PASS |
| concurrent_http_exactly_once | PASS |
| single_side_effects | PASS |
| external_zero_preserved | PASS |
| identity_primary_unique | PASS |
| database_rejects_duplicate_identity | PASS |
| history_unchanged | PASS |
| admin_read_200 | PASS |

Seluruh12 migration, termasuk monitoring_ingestion_keys, berjalan pada MySQL. SHOW INDEX membuktikan identity_hash PRIMARY unique; insert duplikat ditolak oleh database. Sepuluh proses PHP terpisah memakai HTTP kernel Laravel dan barrier yang sama: **1×201 +9×200**, satu ID, satu monitoring, satu ledger, satu inferensi, satu alarm. Skor fuzzy eksternal0 tetap0/BURUK.

Guest read401, device token kosong/invalid401, request device valid melalui10 worker, dan admin guard read200 diuji. Sesi terenkripsi/expired/RBAC juga tercakup suite SQLite Phase16.2; tidak mengklaim browser login memakai MySQL pada fase ini. Satu row histori kontrol MySQL direload sebelum snapshot dan tetap identik sesudah ingress;18 tabel utama SQLite juga identik.

## Isolasi dan batas

Tidak ada migrate:fresh/db:wipe/seeding telemetry pada database utama. Reset selama debugging harness hanya DROP/CREATE schema phase163_test setelah guard host/port/name memastikan target disposable; tidak terhadap database project. Tidak mengubah migration atau service backend. Ini gate terarah, bukan suite penuh297 pada MySQL.

[Hasil12 pemeriksaan](phase16_3/evidence/mysql-results.json), [output migrasi](phase16_3/evidence/mysql-migrations.txt), [worker](phase16_3/evidence/mysql_worker.php), [guard bootstrap](phase16_3/evidence/mysql_bootstrap.php).

Setelah verifikasi, container khusus audit dihentikan. Docker Desktop tetap tersedia; container/volume project utama tidak disentuh. Server browser audit8019 juga dihentikan. Salinan DB dan kredensial audit lokal diabaikan Git.
