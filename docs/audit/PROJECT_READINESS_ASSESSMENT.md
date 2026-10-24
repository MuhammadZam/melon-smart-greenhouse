# Project Readiness Assessment

Tanggal audit: **22 Oktober 2026 (Asia/Jakarta)**. Project: `D:\Compro`. Audit read-only terhadap kode aplikasi dan database utama; artefak audit disimpan di `docs/audit`.

## Keputusan: NOT READY

Project berhasil boot pada server lokal menggunakanSQLite dan dependensi tersedia. Tetapi kesiapan demo kelengkapan, production dan hardware tidak terpenuhi: UI rusak setelah build, fuzzy eksternal0 salah ditampilkan, API internal terbuka, kontrak/pipeline data belum konsisten dan sebagian SRS belum dibuat.

| Jenis kesiapan | Keputusan | Syarat |
| --- | --- | --- |
| Boot lokal untuk inspeksi | READY WITH CONDITIONS | PHP/Composer/Node tersedia; database existingSQLite; style runtime rusak. |
| Demonstrasi SRS lengkap | NOT READY | Tutup G01–G03, lengkapi/putuskan akun dan pengamatan buah, regresi end-to-end. |
| Mock software IoT | READY WITH CONDITIONS | Suite mockPASS; cursor/dedup/provenance/score0 harus dibetulkan. |
| Live Google Sheets | NOT READY | ID/credential/sandbox dan scheduler belum tersedia/terpasang. |
| ESP32 fisik/relay | NOT READY | Tidak ada bukti lapangan; jangan nyalakan hardware dari audit. |
| Production internet | NOT READY | Guest API, preset token, advisories, TLS/debug/rate-limit, MySQL dan load belum siap. |

## Kebutuhan instalasi

Tidak perlu menambah extension VSCode untuk menjalankan PHP/Laravel. composer check-platform-reqs PASS; PHP CLI8.2.12 menyediakan PDO SQLite/MySQL, curl, openssl, dom/xml, mbstring, zip, gd dan intl. Node22.20.0 dan dependensi npm/vendor tersedia; Vite build dijalankan. Alat **pdfjs-dist** dipasang terisolasi ke docs/audit/tools untuk pemeriksaan PDF, tanpa mengubah composer.json/package.json/lock aplikasi.

Docker CLI ada tetapi daemon tidak tersedia; audit tidak mengaktifkan/mengubah MySQL atau melakukan migration pada database utama. SimulasiPython tidak dieksekusi sebagai proses eksternal karena Python tidak ditemukan diPATH dan menjalankan simulator akan menulis data; kompatibilitas payload diperiksa via test/probe. Ini diberi NOT EXECUTED, bukan PASS perangkat. InstalasiPython hanya diperlukan jika hendak menjalankan simulatorPython, bukan untuk website Laravel.

## Menjalankan project lokal untuk inspeksi

Dari D:\Compro, jalankan terminal:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Lalu buka http://127.0.0.1:8000/login. Bila port8000 sudah dipakai proses pengguna, gunakan port lain. Build sudah dibuat melalui npm run build sehingga tidak wajib menyalakan Vite dev server. Gunakan akun lokal yang telah disiapkan pada project; laporan tidak menyalin password atau device token. Jangan jalankan php artisan test default terhadap database demo; gunakan DB_DATABASE khusus.

Cara ini menjalankan kondisi saat audit, bukan memperbaiki styling. Menginstall extension editor tidak menyelesaikan G01. Tidak menjalankan setup migration/seeding untuk database utama karena audit melarang perubahan data.

## Gerbang penerimaan berikutnya

1. Persetujuan laporan audit dan scope perbaikan, terutama konflik SRS/Elektro.
2. G01–G03 ditutup dengan bukti screenshot, assertion boundary0 dan penolakan guest.
3. G04–G18 ditangani untuk data/security/testing dan rerun237 tests dengan fixture mandiri.
4. Tinjau kelengkapan89 requirement, tetapkan mana yang rilis utama/opsional tanpa mengubah SRS sepihak.
5. Sandbox/live/hardware test dengan tim Elektro serta pengesahan threshold/rule agronomi.

Audit selesai dan **STOP**. Tidak melanjutkan Phase 17, tidak memperbaiki kode, tidak mengaktifkan relay fisik.

## Verifikasi preservasi

SHA-256 database utama sebelum/sesudah audit identik. Server browser audit di port 8017 dihentikan setelah pemeriksaan; salinan database sementara tidak dipakai untuk menjalankan project pengguna. Dependensi parser PDF bersifat alat audit terisolasi dan diabaikan Git.


## Addendum — Phase16.3,24 Oktober2026

Laporan di atas adalah snapshot audit22 Oktober dan tetap dipertahankan. Phase16.2 memperbaiki keamanan API/deduplikasi/fuzzy0; Phase16.3 kini **PASS untuk UI lokal yang diverifikasi**:40 halaman-viewport tanpa overflow,52 interaksi browser,297 test/2075 assertions,build PASS,dan gate MySQL8.0.46 PASS. Temuan CSS G01 ditutup dalam lingkup tersebut. Data utama18 tabel tetap identik.

Lihat [implementasi Phase16.3](../PHASE_16_3_IMPLEMENTATION_REPORT.md) dan [perbandingan screenshot](../phase16_3/visual-comparison.html). **Kesiapan keseluruhan tetap NOT READY**: gap kebutuhan/backend di luar fase,production security,Sheets live,perangkat fisik dan validasi agronomi belum ditutup. Tidak memulai Phase16.4.
