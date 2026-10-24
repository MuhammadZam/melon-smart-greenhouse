# CHECKLIST UJI MANUAL KUALITAS (MANUAL QA CHECKLIST)
## SISTEM WEB MONITORING TANAMAN MELON (PHASE 16)

**Tujuan Dokumen:** Panduan pengujian operasional langsung (*hand-on verification*) bagi tim pengembang, dosen pembimbing, dan tim penguji saat demonstrasi akademik.  
**Penanggung Jawab:** Quality Assurance Engineer & Usability Specialist  

---

## 1. PROTOKOL UJI TAMPILAN RESPONSIF (RESPONSIVE VIEWPORT)

| Skenario Uji | Prosedur Langkah | Hasil yang Diharapkan | Status |
|---|---|---|:---:|
| **Mobile Drawer (375px / 390px)** | 1. Buka browser pada ukuran layar ponsel.<br>2. Klik tombol burger menu di pojok kiri atas.<br>3. Klik salah satu item navigasi.<br>4. Klik di luar area menu (backdrop). | Sidebar muncul mulus sebagai off-canvas drawer. Saat tautan diklik atau backdrop di-tap, drawer menutup otomatis tanpa glitch visual. | [ ] PASS |
| **Grid Sensor Mobile** | 1. Buka dashboard pada layar ponsel.<br>2. Periksa Section B (Sensor Overview). | 8 kartu sensor tersusun rapi dalam 2 kolom. Angka nilai sensor, ikon, dan satuan tidak bertumpuk atau terpotong. | [ ] PASS |
| **Tabel Data Observasi** | 1. Buka halaman Laporan Monitoring pada ponsel.<br>2. Usap jari secara horizontal pada tabel data. | Tabel memiliki horizontal scroll yang mulus (`overflow-x-auto`). Teks tetap terbaca jelas dan tidak merusak layout kontainer luar. | [ ] PASS |
| **Tablet Viewport (768px - 820px)** | 1. Buka halaman dashboard dan laporan pada tablet iPad/Android.<br>2. Periksa tata letak filter dan grafik. | Form filter tersusun proporsional, grafik Chart.js merentang sempurna menyesuaikan lebar kontainer. | [ ] PASS |

---

## 2. PROTOKOL UJI DASHBOARD UTAMA (SECTIONS A–G)

| Section | Hal yang Harus Diperiksa | Kriteria Keberhasilan | Status |
|---|---|---|:---:|
| **Section A — Overview** | Total Tanaman, Tanaman Aktif, Monitoring Hari Ini, Waktu Update. | Angka sinkron dengan rekaman database nyata. Waktu update menampilkan format waktu lokal yang valid. | [ ] PASS |
| **Section B — Sensor** | 8 Parameter Sensor (Suhu, Lembap, Tanah, pH, EC, Cahaya, Suhu Nutrisi, Tandon). | Mengadopsi desain minimalis Smart Greenhouse (kartu hitam pekat elegan). Nilai telemetri tebal dan presisi. | [ ] PASS |
| **Section C — Trends** | Grafik Chart.js (Suhu, Lembap, pH, EC, dll). | Tooltip interaktif berfungsi saat kursor diarahkan ke titik grafik. Filter tanggal dan tanaman memperbarui grafik dengan mulus. | [ ] PASS |
| **Section D — Alarms** | Panel Ringkasan Alarm. | Hanya menampilkan alarm dari 4 parameter terverifikasi (Suhu, Lembap Udara, Lembap Tanah, pH). Parameter lain tidak memicu alarm palsu. | [ ] PASS |
| **Section E — Fuzzy** | Skor Kondisi, Status Kategori, Rekomendasi Agronomis. | Skor berkisar 0–100. Rekomendasi ditampilkan informatif sebagai pendukung keputusan. Label disclaimer `PROVISIONAL_FOR_SOFTWARE_TESTING` terlihat jelas. | [ ] PASS |
| **Section F — Actuator** | Panel Kontrol Simulasi Pompa Irigasi & Kipas Exhaust. | Tombol aksi memunculkan modal konfirmasi. Terdapat label jelas "Simulasi Perangkat Lunak (Bukan Kontrol Relay Fisik)". Pengamat tidak dapat mengklik tombol. | [ ] PASS |
| **Section G — Integration** | Status Google Sheets & Telemetri ESP32. | Menampilkan badge status: `CONFIGURED` / `NOT_CONFIGURED` / `STALE_TELEMETRY`. Tidak membocorkan private key service account. | [ ] PASS |

---

## 3. PROTOKOL UJI MODUL LAPORAN MONITORING

| Skenario Uji | Prosedur Langkah | Hasil yang Diharapkan | Status |
|---|---|---|:---:|
| **Validasi Tanggal Terbalik** | 1. Buka `/reports/monitoring`.<br>2. Masukkan `date_from = 2026-10-20` dan `date_to = 2026-10-10`.<br>3. Klik "Terapkan Filter". | Sistem menolak pengiriman dan menampilkan pesan error validasi yang jelas di samping input tanggal. | [ ] PASS |
| **Filter Tanaman Spesifik** | 1. Pilih salah satu tanaman melon dari dropdown.<br>2. Klik "Terapkan Filter". | Tabel dan statistik hanya menampilkan rekaman tanaman tersebut. Judul laporan memperbarui nama tanaman terpilih. | [ ] PASS |
| **Filter Sumber Data** | 1. Pilih sumber data "Google Sheets Sync" atau "Web Form".<br>2. Klik "Terapkan Filter". | Rekaman observasi tersaring presisi berdasarkan kolom sumber data telemetri. | [ ] PASS |
| **Perhitungan Data NULL** | 1. Filter periode observasi lama yang belum memiliki data EC/Cahaya.<br>2. Periksa kartu statistik. | Kartu menampilkan tanda strip (`-`) atau `N/A`, BUKAN angka nol ($0.0$). Rata-rata parameter lain tetap akurat. | [ ] PASS |

---

## 4. PROTOKOL UJI EKSPOR DOKUMEN (PDF & CSV)

| Format Ekspor | Prosedur Langkah | Hasil yang Diharapkan | Status |
|---|---|---|:---:|
| **Ekspor PDF Formal** | 1. Atur filter tertentu pada halaman laporan.<br>2. Klik tombol "Cetak PDF".<br>3. Buka file PDF yang terunduh di Adobe Acrobat / Browser. | - Format kertas A4 Landscape rapi.<br>- Header resmi dan nomor dokumen tercetak.<br>- Ringkasan statistik 8 parameter dan rekapitulasi alarm/fuzzy tertera.<br>- Tabel observasi rapi dan mudah dibaca.<br>- Catatan disclaimer akademik dan kotak pengesahan tanda tangan muncul di bagian bawah. | [ ] PASS |
| **Ekspor CSV Data** | 1. Klik tombol "Ekspor CSV".<br>2. Buka berkas `.csv` langsung menggunakan Microsoft Excel. | - Karakter Unicode dan simbol derajat (°C) terbuka sempurna tanpa karakter aneh (karena UTF-8 BOM).<br>- Teks catatan yang diawali tanda `=`, `+`, atau `-` disanitasi aman dengan tanda petik tunggal (`'`).<br>- Data terpisah rapi per kolom. | [ ] PASS |

---

## 5. CATATAN PENGUJIAN AKADEMIK

- **Kondisi Perangkat Keras ESP32:** Pada Phase 16, pengujian fisik mikrokontroler ESP32 di greenhouse ditandai sebagai `PENDING (Phase 17)`.
- **Kondisi Google Sheets:** Integrasi menggunakan mock data dan simulasi terverifikasi; pengujian spreadsheet resmi Tim Elektro dijadwalkan pada Phase 17.
