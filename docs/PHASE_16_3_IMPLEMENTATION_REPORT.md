# Phase 16.3 — Implementation Report

Tanggal: **24 Oktober 2026 (Asia/Jakarta)**. Project existing: `D:\Compro`. Lingkup: pemulihan UI; tidak memulai Phase 16.4.

## Keputusan: PASS dalam lingkup yang diverifikasi

UI kembali menampilkan desain existing navy/slate dengan aksen emerald, navigasi mobile yang dapat digunakan, tabel yang menggulir dalam panel, grafik responsif, form berlabel dan fokus keyboard. Tidak membuat project baru atau melakukan redesign total.

## Bukti acceptance

| Pemeriksaan | Hasil |
| --- | --- |
| Halaman × viewport | **40/40 PASS**, 10 halaman × empat viewport; 0 overflow dokumen |
| Interaksi browser | **52/52 PASS**; login/logout, drawer, notifikasi/retry, filter/reset, pagination, validasi, chart, export, modal, simulasi dan mock sync |
| Axe | **12 halaman**, 0 detected violations pada aturan WCAG 2 A/AA, 2.1 AA, 2.2 AA yang dipilih |
| MySQL gate | **PASS**, MySQL 8.0.46; 12 migration; 10 concurrent HTTP-kernel workers: 1×201 dan 9×200 |
| Laravel regression | **297 PASS / 2.075 assertions**, 0 error/failure/skipped |
| Vite production build | **PASS**, 63 modules; font WOFF2 lokal ikut dibundel |
| Routes / migration | **55 routes / 12 Ran**, ledger Phase 16.2 tetap ada |
| Preservasi | **18 tabel utama identik** count+SHA-256; 0 FK violations; backend dan tests tidak berubah |

[Bukti gabungan](phase16_3/evidence/verification.json), [galeri before/after](phase16_3/visual-comparison.html), [hasil regresi](PHASE_16_3_REGRESSION_TEST_RESULTS.md).

## Root cause dan implementasi

Tailwind 4 terpasang tetapi entry CSS masih menggunakan direktif v3. Build lama berhasil tanpa menghasilkan theme/preflight/utilitas yang diperlukan. Before browser menunjukkan font Times New Roman, background transparan dan 16 page-viewport meluber. Entry kini menggunakan import v4, sumber Blade/JS/pagination eksplisit, font lokal dan dark variant yang konsisten. Referensi: [Tailwind upgrade guide](https://tailwindcss.com/docs/upgrade-guide), [source detection](https://tailwindcss.com/docs/detecting-classes-in-source-files).

Perbaikan berikutnya berdasarkan browser: drawer offscreen inert, x-cloak, fokus terkurung dan kembali ke pemicu; dropdown notifikasi berada dalam viewport dan memberi loading/error/retry; child grid dapat menyusut; tabel keyboard-scroll; kontras kecil dan tombol; label/error form; canvas alternatif; title/badge aktuator dan detail modal tidak terjepit. Opsi Semua Tanaman pada alarm kini mengirim nilai kosong yang cocok dengan nullable integer existing. Tidak mengubah validator atau query backend.

Dashboard mempertahankan section A–G: overview, delapan sensor, grafik, alarm, fuzzy, simulasi aktuator dan kesehatan integrasi. Grafik sensor tanpa nilai tetap empty state; tidak mengisinya dengan nol atau nilai buatan.

## File aplikasi yang berubah pada fase ini

Daftar berdasarkan hash sebelum/sesudah fase, bukan seluruh dirty Git yang sudah ada:

- `resources/css/app.css`
- `resources/js/app.js`
- `resources/views/actuators/index.blade.php`
- `resources/views/alarms/index.blade.php`
- `resources/views/auth/login.blade.php`
- `resources/views/dashboard/index.blade.php`
- `resources/views/fuzzy/history.blade.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/auth.blade.php`
- `resources/views/monitorings/create.blade.php`
- `resources/views/monitorings/edit.blade.php`
- `resources/views/plants/create.blade.php`
- `resources/views/plants/edit.blade.php`
- `resources/views/admin/integrations/google_sheets.blade.php`
- `resources/views/layouts/partials/navbar.blade.php`
- `resources/views/layouts/partials/sidebar.blade.php`
- `resources/fonts/plus-jakarta-sans-latin-wght-normal.woff2`
- `resources/fonts/PLUS_JAKARTA_SANS_LICENSE.txt`

Tidak mengubah API security, deduplication, rules fuzzy, threshold alarm, device authentication, kontrak Sheets atau simulation policy. Dependensi aplikasi package.json/lock tidak berubah pada fase ini. Axe dan sumber font diunduh hanya ke direktori tools lokal yang diabaikan Git; aset font dan lisensi OFL disalin ke resources/fonts. Tidak memerlukan extension IDE tambahan untuk menjalankan aplikasi.

## Menjalankan project

Build production sudah tersedia. Dari D:\Compro jalankan `php artisan serve`, lalu buka http://127.0.0.1:8000. Jika PHP belum ada di PATH gunakan `C:\xampp\php\php.exe artisan serve`. Tetap memakai database SQLite lokal yang sudah dimigrasikan. Tidak perlu menjalankan migrate:fresh atau seeding ulang. Untuk pengembangan aset gunakan terminal kedua `npm.cmd run dev`; untuk build ulang `npm.cmd run build`.

Font UI sudah lokal sehingga halaman tidak menunggu Google Fonts. Server audit memakai port8019 dan database salinan; bukan server untuk data utama.

## Batas dan masalah tersisa

- PASS ini berlaku untuk lingkup perangkat lunak lokal yang diuji, bukan seluruh SRS atau kesiapan production. Status keseluruhan project tetap **NOT READY** sesuai gap yang belum ditutup.
- Google Sheets live, ESP32 fisik, relay dan validasi agronomi tetap belum diverifikasi. Sinkronisasi browser diuji pada **mode mock di salinan DB**, bukan akun Google live. Aktuator tetap simulasi database.
- MySQL diuji untuk gate migrasi, unique constraint, ingestion konkuren, autentikasi, skor nol dan histori kontrol; **bukan** menjalankan seluruh 297 test pada MySQL. Suite penuh berjalan pada SQLite memori terisolasi.
- Browser: Microsoft Edge desktop headless, empat viewport emulasi. Safari/Firefox, perangkat fisik dan screen reader belum diuji. Axe melaporkan nol pelanggaran pada aturan WCAG yang dipilih di 12 halaman mobile, tetapi masih mempunyai pemeriksaan incomplete untuk review manual (misalnya teks pada gradient). Ini bukan sertifikasi WCAG menyeluruh.
- Query grafik masih memilih jendela observasi sesuai backend existing (G12); ringkasan laporan dapat berbeda saat filter sumber/kondisi tertentu (G08). Footer/legenda PDF G22, kebijakan cursor/mapping Sheets, scheduler, dependency security, role pencatatan manual dan requirement gap lain tetap mengikuti [risiko Phase 16.2](PHASE_16_2_REMAINING_RISKS.md). Tidak mengubah logika tersebut untuk menutupi masalah melalui UI.
- Data demo existing tidak dianggap telemetry live; nilai null tetap N/A atau empty state. Tidak menambah dummy data ke database utama.


Enam laporan fase tersedia. **STOP setelah Phase 16.3; tidak melanjutkan Phase 16.4 otomatis.**
