# Hasil pengujian revisi sensor dan aktuator

Tanggal: 7 November 2026 (Asia/Jakarta). Pengujian menggunakan kode revisi tiga sensor dan database pengujian terisolasi. Tidak ada penghapusan data historis pada database proyek.

## Hasil akhir

| Gate | Hasil | Bukti |
|---|---|---|
| Laravel suite pada MySQL Docker 8.0.46 | 287 lulus; 1 harness SQLite dilewati; 0 gagal/error; 2002 assertions | [JUnit MySQL](revision/evidence/phpunit-mysql.xml), [log](revision/evidence/phpunit-mysql.txt) |
| Laravel suite pada SQLite memory | 288 lulus; 0 gagal/error/skip; 2020 assertions | [JUnit SQLite](revision/evidence/phpunit-sqlite.xml), [log](revision/evidence/phpunit-sqlite.txt) |
| Konkurensi ingestion MySQL, 10 proses HTTP kernel | 1 respons 201 + 9 respons 200; tepat 1 observasi, 1 inference, 1 alarm, 1 receipt | [hasil konkurensi](revision/evidence/mysql-concurrency.json) |
| PHP syntax lint | 126 file lulus, 0 error | [hasil lint](revision/evidence/php-lint.json) |
| Frontend production build | Lulus, Vite 6.4.4 | [log build](revision/evidence/frontend-build.txt) |
| Kompilasi simulator Python | Lulus | simulator.py diperiksa dengan py_compile; cache biner repository dipertahankan |
| UI responsif | Lulus, 25 kombinasi halaman/ukuran layar; tanpa overflow dokumen atau error JavaScript | [hasil browser](revision/evidence/browser-results.json) |
| CSV dan PDF | HTTP 200; tiga sensor aktif; struktur kolom konsisten; PDF dua halaman telah dirender dan dilihat | [validasi ekspor](revision/evidence/export-validation.json) |

Skip MySQL adalah test harness lama yang sengaja memakai file SQLite untuk concurrency. Gate tersebut diganti secara eksplisit oleh `tests/Support/revision_mysql_concurrency.php`, yang benar-benar menjalankan sepuluh proses paralel terhadap MySQL Docker, bukan SQLite.

Pemeriksaan tambahan terakhir pada MySQL setelah perapian akhir: 49 tes terkait kontrak sensor, dashboard, dan alarm lulus (250 assertions); lihat [log](revision/evidence/final-targeted.txt).

## Cakupan regresi

Suite mencakup autentikasi/login, RBAC, API security, token hilang/salah/dicabut, larangan token perangkat untuk API baca internal, validasi sensor aktif, ingestion minimal/alias lama, soil_moisture wajib, preservasi kolom historis saat update, duplicate/retry/conflict, timestamp/subsecond, fingerprint, side effect tunggal, external fuzzy score nol, membership, min/max Mamdani, centroid, kategori skor, alarm boundary/severity/resolution, penolakan fan, idempotensi pompa, filter, PDF/CSV, UTF-8 BOM dan sanitasi formula CSV.

Kontrak tes delapan sensor dan skenario fuzzy empat input yang tidak lagi berlaku diganti oleh tes tiga sensor. Fungsi matematika membership dan operator generik tetap diuji. Centroid dibandingkan dengan jumlah diskret independen; kasus humidity yang berbeda harus menghasilkan inferensi yang identik. Seeder diuji agar registry pH historis dan threshold yang telah dikonfigurasi tidak diduplikasi/ditimpa oleh perubahan label.

Matriks provisional memeriksa 27 kombinasi representatif: 19 menghasilkan skor dan 8 INSUFFICIENT_RULE_COVERAGE. Delapan celah tersebut dilaporkan, tidak diisi dengan aturan agronomis baru. [Matriks lengkap](revision/evidence/fuzzy-27-combinations.json).

## UI dan laporan

Edge/Playwright headless diuji pada 360, 390, 768, 1024, dan 1440 piksel untuk dashboard, daftar monitoring, formulir tambah, laporan, dan aktuator. Seluruh halaman mengembalikan 200 untuk admin; guest dialihkan ke login. Halaman alarm, threshold admin, riwayat fuzzy, konfigurasi fuzzy, integrasi Google Sheets, dan tanaman juga berhasil dibuka. Kolom tabel laporan dibandingkan dengan jumlah sel setiap baris. Teks sensor nonaktif/FAN-01 tidak ditemukan pada halaman yang diuji.

Screenshot: [mobile 360](revision/evidence/dashboard-360.png), [390](revision/evidence/dashboard-390.png), [tablet](revision/evidence/dashboard-768.png), [1024](revision/evidence/dashboard-1024.png), [desktop](revision/evidence/dashboard-1440.png), [grafik aktif](revision/evidence/charts-desktop.png), [laporan web](revision/evidence/report-desktop.png).

Ekspor contoh berisi 30 observasi sintetis dari database uji. CSV memiliki 20 kolom termasuk identitas, tiga sensor, observasi manual, provenance fuzzy, dan catatan. PDF hanya memuat tiga baris statistik sensor dan sembilan kolom detail. Label jumlah sensor/aturan, batas kategori crisp, status PROVISIONAL, dan keterangan simulasi pompa diperiksa. Kedua halaman PDF dilihat setelah rendering; tidak ada kolom sensor lama, clipping, atau blok tanda tangan yang terpisah sendiri.

Artefak: [CSV](revision/evidence/monitoring.csv), [PDF](revision/evidence/monitoring.pdf), [halaman PDF 1](revision/evidence/pdf-page-1.png), [halaman PDF 2](revision/evidence/pdf-page-2.png). Data ini adalah fixture pengujian, bukan telemetri perangkat fisik.

## Cara mengulang

Container uji bernama `compro-revision-mysql`, port lokal 3327; database `compro_revision_test` dan `compro_revision_concurrency`. Kredensial pada bootstrap adalah khusus container uji lokal, tidak diambil dari database proyek. Jalankan dari root repository setelah container tersedia:

```powershell
docker start compro-revision-mysql
$env:COMPRO_REVISION_MYSQL = '1'
php vendor/phpunit/phpunit/phpunit
Remove-Item Env:COMPRO_REVISION_MYSQL
php vendor/phpunit/phpunit/phpunit
php tests/Support/revision_mysql_concurrency.php
npm run build
```

Jangan menjalankan build bersamaan dengan pemeriksaan browser: Vite mengganti manifest saat build dan dapat menimbulkan error sementara pada request yang sedang merender. Untuk browser, gunakan bootstrap/dataset `revision_prepare_ui.php`, server pada database uji port aplikasi 8027, lalu `node tests/Support/revision_browser.mjs`. Browser script membutuhkan Edge dan Playwright; `python tests/Support/revision_validate_exports.py` memeriksa ekspor dan merender PDF dengan PyMuPDF yang tersedia di lingkungan pengujian. Container uji dan data fixture dipertahankan; layanan uji dapat dihentikan setelah pemeriksaan.

## Batas verifikasi

Migration nullable humidity hanya diterapkan pada skema database uji baru melalui definisi up(), belum pada database proyek. Tidak ada perintah migrate/rollback destruktif, DROP TABLE, atau penghapusan volume. Pengujian live Service Account Google Sheets, sensor ESP32 fisik, kalibrasi, dan relay pompa belum dilakukan. Threshold, kurva fuzzy, rule base, delapan celah kombinasi, serta kategori/rekomendasi tetap memerlukan pengesahan Tim Elektro/pembimbing.
