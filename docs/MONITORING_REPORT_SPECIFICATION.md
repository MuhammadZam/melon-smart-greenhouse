# SPESIFIKASI MODUL LAPORAN MONITORING GREENHOUSE (PHASE 16)
## SISTEM WEB MONITORING TANAMAN MELON

**Tanggal:** 17 Oktober 2026  
**Status Dokumen:** RESMI (APPROVED)  
**Role:** Software Architect, Reporting Engineer, dan Database Specialist  

---

## 1. DESKRIPSI MODUL

Modul **Laporan Monitoring Greenhouse** (`/reports/monitoring`) adalah sub-sistem analitik terpadu yang dirancang untuk mengagregasikan rekaman observasi telemetri tanaman melon dari berbagai sumber data (Web Form, REST API, Google Sheets, dan IoT Simulator), menyajikan ringkasan statistik komprehensif, serta memfasilitasi ekspor data ke format CSV dan dokumen PDF berstandar akademik.

---

## 2. ARSITEKTUR KOMPONEN & PEMISAHAN TANGGUNG JAWAB (SoC)

Modul laporan dibangun mengikuti prinsip *Separation of Concerns*:
1. **Controller Layer (`MonitoringReportController`):**
   - Menangani HTTP Request, validasi input parameter filter, otorisasi RBAC (Admin & Pengamat), dan mengembalikan response (HTML View, Streamed CSV, atau Binary PDF).
2. **Service Layer (`MonitoringReportService`):**
   - Membangun *reusable base query* yang menjamin konsistensi dataset antara tampilan layar, CSV, dan PDF.
   - Melakukan kalkulasi statistik numerik (Rata-rata, Nilai Minimum, Nilai Maksimum).
   - Menghitung rekapitulasi alarm 4-parameter dan rekapitulasi inferensi fuzzy pada periode waktu terkait.
3. **Export Service Layer (`MonitoringReportExportService`):**
   - Menangani formatting data ekspor, pengkodean karakter UTF-8 BOM, sanitasi injeksi formula CSV, serta konfigurasi layout dan rendering mesin DomPDF.

---

## 3. SPESIFIKASI PARAMETER FILTER

| Parameter | Tipe Data | Aturan Validasi | Nilai Default | Penjelasan |
|---|---|---|---|---|
| `date_from` | Date (`Y-m-d`) | Opsional, format tanggal valid, `before_or_equal:date_to` | Null (Awal observasi) | Batas bawah waktu observasi |
| `date_to` | Date (`Y-m-d`) | Opsional, format tanggal valid, `after_or_equal:date_from` | Null (Hari ini) | Batas atas waktu observasi |
| `plant_id` | Integer / String | Opsional, `all` atau `exists:plants,id` | `all` | Filter spesifik tanaman melon |
| `source` | String | Opsional, `in:all,web,api,google_sheets,simulator` | `all` | Filter sumber telemetri |
| `condition` | String | Opsional, string sanitasi atau `all` | `all` | Filter kondisi fisik tanaman |
| `sort_by` | String | Opsional, whitelist: `monitored_at`, 8 parameter sensor | `monitored_at` | Kolom pengurutan data |
| `sort_direction` | String | Opsional, `in:asc,desc` | `desc` | Arah urutan pengurutan |
| `per_page` | Integer | Opsional, integer antara 5 hingga 100 | `15` | Jumlah rekaman per halaman web |

---

## 4. METODOLOGI PERHITUNGAN STATISTIK

Statistik dihitung menggunakan agregasi SQL langsung pada query yang telah difilter:

### Aturan Perlakuan Data NULL (Non-Zero Imputation):
- **Wajib:** Nilai sensor yang bernilai `NULL` (misalnya observasi manual lama yang belum merekam parameter IoT baru seperti EC, Suhu Nutrisi, atau Intensitas Cahaya) **TIDAK BOLEH** dihitung sebagai angka nol ($0$).
- Fungsi SQL `AVG(column)`, `MIN(column)`, dan `MAX(column)` secara inheren mengabaikan nilai `NULL` di level mesin database MySQL dan SQLite.
- Jika suatu parameter memiliki 0 rekaman valid (seluruh observasi pada filter tersebut bernilai `NULL`), sistem menampilkan label `N/A` pada antarmuka web dan dokumen cetak, bukan angka $0.0$.

### Parameter Sensor yang Dihitung:
1. **Suhu Udara (`temperature`):** Rata-rata, Min, Maks (Satuan: °C, pembulatan 1 desimal).
2. **Kelembapan Udara (`humidity`):** Rata-rata, Min, Maks (Satuan: %, pembulatan 1 desimal).
3. **Kelembapan Tanah (`soil_moisture`):** Rata-rata, Min, Maks (Satuan: %, pembulatan 1 desimal).
4. **Derajat Keasaman (`ph`):** Rata-rata, Min, Maks (Satuan: pH, pembulatan 2 desimal).
5. **Konduktivitas Elektrik Nutrisi (`ec`):** Rata-rata, Min, Maks (Satuan: mS/cm, pembulatan 2 desimal).
6. **Intensitas Cahaya (`light_intensity`):** Rata-rata, Min, Maks (Satuan: lux, pembulatan bilangan bulat).
7. **Suhu Larutan Nutrisi (`nutrient_temperature`):** Rata-rata, Min, Maks (Satuan: °C, pembulatan 1 desimal).
8. **Level Tandon Nutrisi (`tank_level`):** Rata-rata, Min, Maks (Satuan: %, pembulatan 1 desimal).

### Agregasi Tunggal vs Agregasi Kolektif:
- Jika filter `plant_id` dipilih secara spesifik, statistik mencerminkan metrik lingkungan mikroklimat tanaman terkait.
- Jika filter `plant_id = all`, statistik mencerminkan kondisi rata-rata seluruh blok greenhouse, dan diberikan catatan penjelas pada footer tabel laporan.

---

## 5. INTEGRASI DENGAN ALARM & FUZZY LOGIC

Laporan monitoring tidak hanya menyajikan data mentah, tetapi juga merangkum anomali dan analisis kecerdasan buatan:
1. **Ringkasan Alarm 4-Parameter:**
   - Menghitung total alarm yang tercatat dalam rentang waktu yang dipilih, dipisahkan berdasarkan status `ACTIVE` dan `RESOLVED`, serta distribusi per parameter (Suhu, Lembap Udara, Lembap Tanah, pH).
2. **Ringkasan Inferensi Fuzzy Logic:**
   - Menghitung rata-rata skor fuzzy kondisi tanaman pada periode laporan.
   - Menampilkan distribusi status kondisi: `BAIK`, `PERLU_PERHATIAN`, dan `BURUK`.
   - Melampirkan label status *provisional disclaimer* pada setiap ekspor dokumen.

---

## 6. OPTIMASI QUERY & KINERJA

1. **Eager Loading:**
   Query laporan selalu memuat relasi terkait menggunakan eager loading `with(['plant', 'latestFuzzyInference'])`, mencegah timbulnya masalah $N+1$ query saat merender puluhan baris tabel.
2. **Indexing:**
   Query memanfaatkan indeks komposit yang ada pada tabel `monitorings` (`monitored_at`, `plant_id`, `source`) dan tabel `plants`.
3. **Paginasi di Antarmuka Web:**
   Antarmuka web dibatasi secara default 15 rekaman per halaman menggunakan `LengthAwarePaginator` untuk meminimalkan beban rendering DOM browser.
