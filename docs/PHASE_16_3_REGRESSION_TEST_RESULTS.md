# Phase 16.3 — Regression Test Results

Tanggal: **24 Oktober 2026 (Asia/Jakarta)**. Project existing: `D:\Compro`. Lingkup: pemulihan UI; tidak memulai Phase 16.4.

## PASS

| Perintah / pemeriksaan | Hasil | Bukti |
| --- | --- | --- |
| php artisan test --log-junit … | **297 passed,2075 assertions,0 failures/errors/skipped** | phase16_3/evidence/regression.xml dan regression.txt |
| npm.cmd run build | **PASS**,63 modules | phase16_3/evidence/build.txt |
| php artisan route:list --json | **55 routes** | phase16_3/evidence/routes.json |
| php artisan migrate:status | **12 Ran**, SQLite utama | phase16_3/evidence/migrations.txt |
| php artisan view:cache + php -l compiled views | **PASS** | phase16_3/evidence/blade-syntax.txt |
| Browser | **40 viewport-page**,0 overflow,0 pageerror | phase16_3/evidence/browser-after.json |
| Interaksi | **52 checks PASS** | phase16_3/evidence/interactions.json |
| MySQL | **12 gate checks PASS**,10 concurrent workers | phase16_3/evidence/mysql-results.json |
| Axe | **12 pages**,0 detected violations; incomplete retained | phase16_3/evidence/accessibility.json |
| Main history | **18 counts + SHA-256 identical**,0 FK violations | phase16_3/evidence/history-before.json / history-after.json |
| Source preservation | **Backend,config,migrations,routes,tests identical** | phase16_3/evidence/source-before.json / source-after.json |

Suite PHP mencakup authentication/RBAC, private API, device auth, concurrent deduplication, fuzzy zero, monitoring/dashboard/report/alarm/simulation dan Sheets. Tidak menambah/menghapus PHP test atau mengubah assertion agar hasil lulus. Pemeriksaan UI baru dijalankan sebagai script Playwright dan axe pada salinan DB;52 checks bukan tambahan pada hitungan PHPUnit297.

## Preservasi Phase16.2

Hash membuktikan seluruh source backend dan test sama dengan awal Phase16.3. Ledger, API policy, payload fingerprint, transaction ordering, normalization score0, alarm/fuzzy rules dan simulation-only policy tidak berubah. Hanya18 file di resources berubah/ditambahkan, termasuk2 file font+license. Histori utama monitoring959, fuzzy493, alarm1, actuator log1, tanaman150 dan user10 tetap sama; checksum memeriksa seluruh row tanpa mengekspor token/password.

## Reproduksi

Script browser.mjs membutuhkan server loopback8019 dengan DB copy dari setup.php. Credentials dibaca dari file lokal yang diabaikan Git, tidak hardcoded. setup.php menolak menimpa salinan yang sudah ada dan mengecek DB target. Browser msedge sudah terpasang; tidak perlu download Chromium atau extension IDE. Tools axe-core dan sumber font ada di tools lokal ignored; artefak font runtime berada di resources/fonts. Pengujian MySQL memakai bootstrap terjaga untuk port3319/schema phase163_test dan worker barrier. Lihat implementation report untuk menjalankan aplikasi biasa dengan data utama.

## Batas dan masalah tersisa

- PASS ini berlaku untuk lingkup perangkat lunak lokal yang diuji, bukan seluruh SRS atau kesiapan production. Status keseluruhan project tetap **NOT READY** sesuai gap yang belum ditutup.
- Google Sheets live, ESP32 fisik, relay dan validasi agronomi tetap belum diverifikasi. Sinkronisasi browser diuji pada **mode mock di salinan DB**, bukan akun Google live. Aktuator tetap simulasi database.
- MySQL diuji untuk gate migrasi, unique constraint, ingestion konkuren, autentikasi, skor nol dan histori kontrol; **bukan** menjalankan seluruh 297 test pada MySQL. Suite penuh berjalan pada SQLite memori terisolasi.
- Browser: Microsoft Edge desktop headless, empat viewport emulasi. Safari/Firefox, perangkat fisik dan screen reader belum diuji. Axe melaporkan nol pelanggaran pada aturan WCAG yang dipilih di 12 halaman mobile, tetapi masih mempunyai pemeriksaan incomplete untuk review manual (misalnya teks pada gradient). Ini bukan sertifikasi WCAG menyeluruh.
- Query grafik masih memilih jendela observasi sesuai backend existing (G12); ringkasan laporan dapat berbeda saat filter sumber/kondisi tertentu (G08). Footer/legenda PDF G22, kebijakan cursor/mapping Sheets, scheduler, dependency security, role pencatatan manual dan requirement gap lain tetap mengikuti [risiko Phase 16.2](PHASE_16_2_REMAINING_RISKS.md). Tidak mengubah logika tersebut untuk menutupi masalah melalui UI.
- Data demo existing tidak dianggap telemetry live; nilai null tetap N/A atau empty state. Tidak menambah dummy data ke database utama.

