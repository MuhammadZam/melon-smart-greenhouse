# UI/UX Audit Report

Tanggal audit: **22 Oktober 2026 (Asia/Jakarta)**. Project: `D:\Compro`. Audit read-only terhadap kode aplikasi dan database utama; artefak audit disimpan di `docs/audit`.

## Hasil aktual: FAIL

Build Vite berhasil menghasilkan CSS/JS, tetapi CSS tidak menyediakan theme/utilitas yang diharapkan markup. resources/css/app.css masih memakai direktif v3 sementara package-lock mengunci Tailwind4.3.3; PostCSS memakai @tailwindcss/postcss. Dokumentasi resmi menggunakan import CSS pada v4 ([Tailwind upgrade guide](https://tailwindcss.com/docs/upgrade-guide)). Root cause ini didukung bukti screenshot dan computed style, bukan sekadar asumsi versi.

Computed body background transparan dan font Times New Roman; ikon SVG berukuran sangat besar, spacing/card/sidebar/table tidak sesuai desain. Seluruh viewport dashboard/laporan meluber horizontal.

| Viewport | Halaman | Lebar dokumen | Overflow | Hasil visual |
| --- | --- | --- | --- | --- |
| 375×812 | /dashboard | 484 | YA | FAIL — style/theme hilang |
| 375×812 | /reports/monitoring | 441 | YA | FAIL — style/theme hilang |
| 768×1024 | /dashboard | 877 | YA | FAIL — style/theme hilang |
| 768×1024 | /reports/monitoring | 834 | YA | FAIL — style/theme hilang |
| 1366×768 | /dashboard | 1475 | YA | FAIL — style/theme hilang |
| 1366×768 | /reports/monitoring | 1432 | YA | FAIL — style/theme hilang |
| 1920×1080 | /dashboard | 2029 | YA | FAIL — style/theme hilang |
| 1920×1080 | /reports/monitoring | 1986 | YA | FAIL — style/theme hilang |

## Komponen yang ditinjau

Sidebar dan navbar tersedia; Alpine state sidebarOpen ada, tetapi visual drawer dan icon sizing tidak layak dinyatakan PASS. Kartu sensor, tabel, form, button, dropdown, notifikasi dan empty/error state memiliki markup serta backend terkait; layout aktual gagal konsistensi/profesionalitas/responsiveness. Halaman admin accounts hanya redirect dan tidak menyediakan workflow manajemen. Modal/keyboard focus dan loading state tidak diuji menyeluruh.

## Interaksi dan akses

| Role | URL | HTTP | Final path / catatan |
| --- | --- | --- | --- |
| guest | /dashboard | 200 | login redirect |
| guest | /reports/monitoring | 200 | login redirect |
| guest | /admin/thresholds | 200 | login redirect |
| admin | /dashboard | 200 | /dashboard |
| admin | /plants | 200 | /plants |
| admin | /monitorings | 200 | /monitorings |
| admin | /alarms | 200 | /alarms |
| admin | /actuators | 200 | /actuators |
| admin | /actuators/history | 200 | /actuators/history |
| admin | /fuzzy/history | 200 | /fuzzy/history |
| admin | /admin/fuzzy/config | 200 | /admin/fuzzy/config |
| admin | /admin/thresholds | 200 | /admin/thresholds |
| admin | /admin/integrations/google-sheets | 200 | /admin/integrations/google-sheets |
| admin | /reports/monitoring | 200 | /reports/monitoring |
| admin | /admin/users | 200 | /dashboard |
| pengamat | /admin/thresholds | 403 | lihat browser.json |
| pengamat | /admin/fuzzy/config | 403 | lihat browser.json |
| pengamat | /admin/integrations/google-sheets | 403 | lihat browser.json |
| pengamat | /plants/create | 403 | lihat browser.json |
| pengamat | /monitorings/create | 200 | lihat browser.json |
| pengamat | /reports/monitoring | 200 | lihat browser.json |
| pengamat | /actuators | 200 | lihat browser.json |

Pengamat diizinkan membuat monitoring (200 GET create) oleh implementasi; ini konflik terhadap read-only SRS, bukan diklaim RBAC PASS menyeluruh. Akses admin pengamat403 menjadi bukti protection halaman terkait.

## Ekspor

CSV hasil aktual: 174384 bytes. PDF: {"status":200,"ms":36348,"bytes":209292,"signature":"255044462d312e37"}.
Parser memeriksa teks 23 halaman; seluruh MediaBox sekitar841.89×595.28pt (A4 landscape). Warning500=true; PROVISIONAL=true. Render visual sampel halaman1, 12, 23 tersedia; halaman lain tidak diberi klaim visual PASS.

## Bukti dan batas

Screenshot terbaru di evidence/*_viewport.png dan full-page evidence/*_dashboard.png / *_reports.png. Screenshot lama docs/qa adalah bukti17 Oktober, tidak menggantikan pengujian22Oktober. Browser errors/harness timeout tersimpan; timeout download awal bukan bukti format PDF salah.

Uji WCAG contrast4.5:1, screen reader, keyboard, Safari/Firefox, smartphone fisik dan UAT85%: **NOT EXECUTED/NOT VERIFIED**. Prioritas pertama memperbaiki build CSS setelah persetujuan, kemudian mengulang screenshot serta interaksi form/nav/filter pada empat viewport.

## Temuan PDF setelah inspeksi visual

Halaman 1, 12 dan 23 tidak clipping tabel yang terlihat; header tabel diulang. **Nomor halaman tidak muncul** di teks maupun tiga sampel render: template memakai script text/php sementara enable_php=false. Jangan mengaktifkan eksekusi PHP template sebagai jalan pintas; gunakan API Canvas. Legenda PERLU_PERHATIAN 40–70 dan BURUK 0–45 salah dibanding config 40–64.99 dan 0–39.99. PDF adalah PARTIAL. Tidak semua 23 halaman diberi klaim visual PASS.


## Addendum — Phase16.3,24 Oktober2026

Laporan di atas adalah snapshot audit22 Oktober dan tetap dipertahankan. Phase16.2 memperbaiki keamanan API/deduplikasi/fuzzy0; Phase16.3 kini **PASS untuk UI lokal yang diverifikasi**:40 halaman-viewport tanpa overflow,52 interaksi browser,297 test/2075 assertions,build PASS,dan gate MySQL8.0.46 PASS. Temuan CSS G01 ditutup dalam lingkup tersebut. Data utama18 tabel tetap identik.

Lihat [implementasi Phase16.3](../PHASE_16_3_IMPLEMENTATION_REPORT.md) dan [perbandingan screenshot](../phase16_3/visual-comparison.html). **Kesiapan keseluruhan tetap NOT READY**: gap kebutuhan/backend di luar fase,production security,Sheets live,perangkat fisik dan validasi agronomi belum ditutup. Tidak memulai Phase16.4.
