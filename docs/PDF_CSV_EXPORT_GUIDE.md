# PANDUAN TEKNIS EKSPOR LAPORAN PDF & CSV (PHASE 16)
## SISTEM WEB MONITORING TANAMAN MELON

**Tanggal:** 17 Oktober 2026  
**Status Panduan:** PANDUAN RESMI SISTEM  
**Role:** Reporting Engineer, Security Specialist, dan Systems Integrator  

---

## 1. IKHTISAR TEKNIS EKSPOR

Sistem Web Monitoring Tanaman Melon menyediakan dua format ekspor laporan yang dirancang untuk kebutuhan yang berbeda:
1. **Format PDF (Portable Document Format):**
   - Ditujukan untuk dokumentasi resmi, laporan akademik, pengarsipan berkala, dan peninjauan fisik/cetak.
   - Menggunakan tata letak formal A4 Landscape, tipografi terstruktur, ringkasan statistik, audit alarm/fuzzy, serta kolom pengesahan pengamat.
2. **Format CSV (Comma-Separated Values):**
   - Ditujukan untuk analisis data kuantitatif lebih lanjut menggunakan perangkat lunak spreadsheet (Microsoft Excel, Google Sheets, LibreOffice Calc, R, atau Python Pandas).
   - Menggunakan encoding UTF-8 dengan BOM (*Byte Order Mark*), pemisah koma terstandarisasi, dan proteksi dari injeksi formula formula jahat (*CSV Formula Injection*).

---

## 2. PANDUAN EKSPOR PDF (AKADEMIK & FORMAL)

### A. Arsitektur Mesin Rendering:
- **Library:** `barryvdh/laravel-dompdf` (versi 3.1.2) berbasis `dompdf/dompdf`.
- **Ukuran Kertas:** `A4` dalam orientasi `Landscape` ($297\text{ mm} \times 210\text{ mm}$).
- **Margin:** 10 mm di setiap sisi (`margin: 10mm 12mm`).
- **Font:** Font standar web bebas lisensi (`Helvetica`, `Arial`, sans-serif) yang terkompilasi secara bawaan oleh engine DomPDF untuk performa rendering cepat tanpa ketergantungan font eksternal.

### B. Struktur Dokumen PDF:
1. **Header Dokumen:**
   - Judul: `LAPORAN PEMANTAUAN GREENHOUSE TANAMAN MELON`
   - Sub-judul: `Sistem Web Monitoring Pertanian Presisi Berbasis IoT & Logika Fuzzy Mamdani`
   - Nomor Dokumen: Format otomatis `DOC-RPT-{Ymd}-{HIS}`
   - Tanggal Cetak: Waktu lokal server saat PDF diunduh
2. **Metadata & Filter Aktif:**
   - Periode tanggal yang dipilih (`Semua Waktu` atau rentang spesifik)
   - Identitas tanaman (Tanaman spesifik dengan kode varietas dan lokasi bedengan, atau `Semua Tanaman`)
   - Sumber data telemetri yang difilter
   - Kondisi tanaman yang difilter
3. **Ringkasan Statistik 8 Parameter Sensor:**
   - Tabel ringkasan yang memuat Rata-rata, Nilai Minimum, dan Nilai Maksimum untuk seluruh 8 parameter sensor.
4. **Ringkasan Alarm & Status Logika Fuzzy:**
   - Rekapitulasi jumlah alarm aktif/teratasi pada periode laporan.
   - Rekapitulasi rata-rata skor fuzzy dan distribusi kondisi (`BAIK`, `PERLU_PERHATIAN`, `BURUK`).
5. **Tabel Rekaman Observasi Detail:**
   - Kolom: No, Waktu, Tanaman, Sumber, Suhu (°C), Lembap (%), Tanah (%), pH, EC (mS/cm), Cahaya (lux), Suhu Nutrisi (°C), Tandon (%), Kondisi Fisik, Skor Fuzzy.
6. **Catatan Disclaimer Akademik & Kolom Pengesahan:**
   - Penegasan status rule fuzzy `PROVISIONAL_FOR_SOFTWARE_TESTING`.
   - Penegasan status aktuator berupa simulasi software database.
   - Kotak tanda tangan untuk Teknisi/Pengamat Lapangan dan Dosen Pembimbing/Pakar Agronomi.

### C. Manajemen Memori & Skalabilitas PDF:
- Rendering HTML ke PDF pada engine PHP memerlukan alokasi memori yang signifikan.
- Untuk mencegah kehabisan memori (*PHP Out of Memory / OOM*), kueri PDF dibatasi secara aman hingga maksimum **500 rekaman terbaru** per pengunduhan PDF.
- Jika rekaman melampaui 500 baris, pengguna disarankan untuk mempersempit rentang tanggal filter atau memanfaatkan ekspor CSV (yang menggunakan metode *streaming* berkapasitas tak terbatas).

---

## 3. PANDUAN EKSPOR CSV (DATA SCIENCE & ANALISIS)

### A. Arsitektur Streaming Response:
- Menggunakan `Symfony\Component\HttpFoundation\StreamedResponse`.
- Data dialirkan langsung dari kursor database MySQL/SQLite ke *output buffer* PHP (`php://output`) menggunakan `fputcsv()`.
- **Efisiensi Memori:** Penggunaan memori tetap konstan ($\sim 2\text{ MB}$) meskipun mengekspor puluhan ribu baris data monitoring, karena data tidak diakumulasikan dalam array memori PHP.

### B. Kompatibilitas UTF-8 BOM:
- File CSV dimulai dengan 3-byte UTF-8 BOM (`\xEF\xBB\xBF`).
- **Manfaat:** Memastikan Microsoft Excel secara otomatis mengenali karakter Unicode, simbol derajat Celsius (`°C`), dan pemisah baris tanpa memerlukan konfigurasi manual *Text Import Wizard*.

### C. Mitigasi Keamanan: CSV Formula Injection (CWE-1236):
- Jika pengguna atau simulator memasukkan teks catatan (*notes*) atau kondisi tanaman yang diawali oleh karakter formula:
  - `=` (Equals)
  - `+` (Plus)
  - `-` (Minus)
  - `@` (At sign)
  - `\t` (Tab)
  - `\r` (Carriage return)
- Spreadsheet seperti Excel dapat mengeksekusi rumus atau perintah sistem berbahaya (`=cmd|' /C calc'!A0`).
- **Solusi Sanitasi:** `MonitoringReportExportService` memeriksa setiap field teks. Jika diawali karakter berbahaya tersebut, sistem menyisipkan tanda petik tunggal (`'`) di depannya, menonaktifkan eksekusi formula dan memperlakukannya murni sebagai teks biasa.

---

## 4. CARA PENGGUNAAN EKSPOR PADA SISTEM

1. Masuk ke menu **Laporan Monitoring** pada sidebar kiri navigasi.
2. Atur kriteria filter yang diinginkan (Rentang Tanggal, Pilihan Tanaman, Sumber Data, Kondisi).
3. Klik tombol **Terapkan Filter**.
4. Untuk mengunduh PDF: Klik tombol hijau **Cetak PDF**. Berkas `laporan_monitoring_melon_{timestamp}.pdf` akan otomatis terunduh.
5. Untuk mengunduh CSV: Klik tombol biru **Ekspor CSV**. Berkas `laporan_monitoring_melon_{timestamp}.csv` akan segera dialirkan ke peramban.
