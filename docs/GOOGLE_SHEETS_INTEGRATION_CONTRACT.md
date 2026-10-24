# KONTRAK DATA INTEGRASI GOOGLE SHEETS & ESP32 (PHASE 13)

> **Pembaruan Phase 16.2:** sinkronisasi memakai ledger unik terpisah dengan namespace sumber + perangkat + tanaman + waktu. Dua perangkat/sumber berbeda pada waktu sama tidak lagi digabung. Kontrak 15 kolom A:O tetap. Skor numerik nol valid; sel skor kosong berarti tidak tersedia; skor nonnumerik/nonfinite/di luar 0–100 menggagalkan baris. Label untuk skor baru dinormalisasi mengikuti konfigurasi kategori Laravel, termasuk bila label dari client berbeda; histori lama tidak diubah. Replay tidak mengulang alarm/inferensi. Cursor append-only dan kredensial Google live masih memiliki keterbatasan yang dicatat di [risiko tersisa](PHASE_16_2_REMAINING_RISKS.md).
## SISTEM WEB MONITORING TANAMAN MELON

**Versi Dokumen:** 1.0  
**Tanggal:** 16 Oktober 2026  
**Status:** Draf Perencanaan Integrasi (Belum Diaktifkan di Kode Produksi)  
**Tujuan Integrasi:** ESP32 → Google Sheets → Laravel Webhook/Sync Engine → MySQL → Dashboard  

---

## 1. PENDAHULUAN & BATASAN PHASE 13

Dokumen ini disusun sebagai panduan kontrak data teknis antara **Tim Elektro (Hardware/Firmware ESP32)** dan **Tim Web Laravel**. 

### Batasan Penting Phase 13:
> [!IMPORTANT]
> - Pada Phase 13, integrasi fisik Google Sheets **TIDAK DIAKTIFKAN** karena Spreadsheet ID riil, kredensial Service Account (`credentials.json`), dan izin akses belum diserahkan oleh Tim Elektro.
> - Dilarang keras menggunakan Spreadsheet dengan izin publik (*Anyone with the link can edit/view*) sebagai jalan pintas karena melanggar standar privasi dan keamanan data akademik.
> - REST API (`POST /api/monitorings`) dan Python IoT Simulator eksisting **tetap dipertahankan penuh** sebagai kanal pengujian telemetri utama.
> - Dilarang membangun mekanisme polling yang terus-menerus membaca seluruh baris Spreadsheet dari baris pertama.

---

## 2. ARSITEKTUR ALIRAN DATA (DATA PIPELINE)

```
+──────────────+      HTTPS POST      +──────────────────────────+
| ESP32 Node   | ───────────────────► | Google Apps Script (GAS)  |
| (Greenhouse) |                      | / Google Sheets Document |
+──────────────+                      +──────────────────────────+
                                                    │
                                                    │ Sync Engine /
                                                    │ Incremental Cursor
                                                    ▼
                                      +──────────────────────────+
                                      | Laravel Ingestion Worker |
                                      | (GoogleSheetsSyncService)|
                                      +──────────────────────────+
                                                    │
                                                    │ Validasi & Deduplikasi
                                                    ▼
                                      +──────────────────────────+
                                      | Database MySQL &         |
                                      | MonitoringAlarmService   |
                                      +──────────────────────────+
                                                    │
                                                    ▼
                                      +──────────────────────────+
                                      | Web Dashboard & Charts   |
                                      +──────────────────────────+
```

---

## 3. MAPPING KOLOM SPREADSHEET KE DATABASE

Format baris pertama (Row 1) Google Sheets didefinisikan sebagai header kolom baku berikut:

| No | Nama Kolom Header (Spreadsheet) | Tipe Data | Satuan | Wajib/Opsional | Aturan Validasi | Target Field Database |
| :-: | :--- | :--- | :---: | :---: | :--- | :--- |
| 1 | `device_id` | String | - | **Wajib** | Alfanumerik, max 50 char (contoh: `ESP32-MELON-01`) | Disimpan di payload meta / identifier |
| 2 | `timestamp` | Datetime (ISO 8601) | - | **Wajib** | Format: `YYYY-MM-DD HH:mm:ss` atau ISO 8601 | `monitorings.monitored_at` |
| 3 | `plant_code` | String | - | Opsional | Kode tanaman (contoh: `PLANT-01`), fallback ke default tanaman aktif | `monitorings.plant_id` (via resolver) |
| 4 | `temperature` | Float | °C | **Wajib** | Rentang: 0.00 – 60.00 | `monitorings.temperature` |
| 5 | `humidity` | Float | % | **Wajib** | Rentang: 0.00 – 100.00 | `monitorings.humidity` |
| 6 | `soil_moisture` | Float | % | **Wajib** | Rentang: 0.00 – 100.00 | `monitorings.soil_moisture` |
| 7 | `ph` | Float | pH | **Wajib** | Rentang: 0.00 – 14.00 | `monitorings.ph` |
| 8 | `ec` | Float | mS/cm | Opsional | Rentang: 0.00 – 10.00 | `monitorings.ec` |
| 9 | `light_intensity` | Float | lux | Opsional | Rentang: 0.00 – 100,000.00 | `monitorings.light_intensity` |
| 10 | `nutrient_temp` | Float | °C | Opsional | Rentang: 0.00 – 60.00 | `monitorings.nutrient_temperature` |
| 11 | `tank_level` | Float | % | Opsional | Rentang: 0.00 – 100.00 | `monitorings.tank_level` |
| 12 | `pump_status` | String | - | Opsional | Nilai: `ON`, `OFF` | Draf telemetri aktuator |
| 13 | `fan_status` | String | - | Opsional | Nilai: `ON`, `OFF` | Draf telemetri aktuator |
| 14 | `fuzzy_score` | Float | - | Opsional | Nilai: 0.00 – 100.00 | Draf inferensi AI |
| 15 | `fuzzy_label` | String | - | Opsional | Contoh: `OPTIMAL`, `WASPADA_SUHU` | Draf inferensi AI |

---

## 4. STRATEGI REKAYASA SISTEM & EXCEPTION HANDLING

### A. Pencegahan Duplikasi Data (Deduplication & Idempotency)
- Data telemetri dinyatakan unik berdasarkan kombinasi:  
  `hash(device_id + timestamp + plant_id)`.
- Sebelum melakukan `Monitoring::create()`, Laravel memeriksa keberadaan kombinasi `plant_id` dan `monitored_at` yang sama.
- Baris duplikat akan diabaikan (*ignored*) dan dicatat dalam log audit tanpa menghentikan proses sinkronisasi baris lainnya.

### B. Mekanisme Kursor Sinkronisasi (Incremental Cursor / High-Water Mark)
- **Dilarang membaca seluruh sheet:** Membaca ribuan baris setiap sinkronisasi akan membebani kuota API Google Sheets (*Rate Limit: 60 requests per minute per user*).
- **Strategi Kursor:** Laravel menyimpan `last_processed_row` (atau `last_synced_timestamp`) pada tabel `system_sync_states`.
- Pembacaan Google Sheets menggunakan rentang spesifik (misal: `Data!A101:O150`) hanya untuk baris-baris baru.

### C. Penanganan Data Terlambat (Delayed / Out-of-Order Readings)
- Jika ESP32 mengalami *network outage* di lapangan dan mengirimkan data secara *bulk* dengan timestamp lampau:
  - Data tetap disimpan dengan `monitored_at` sesuai timestamp pengukuran asli, bukan waktu saat diterima Laravel.
  - Grafik tren Chart.js akan menampilkan data secara kronologis sesuai `monitored_at`.
  - Evaluasi alarm hanya memicu status *active* jika data tersebut adalah data terkini dari tanaman terkait.

### D. Penanganan Data Anomali & Nilai Kosong (Missing Fields)
- Kolom wajib (`temperature`, `humidity`, `soil_moisture`, `ph`) yang bernilai kosong atau non-numerik akan menyebabkan baris tersebut ditandai sebagai `REJECTED_INVALID_PAYLOAD`.
- Kolom opsional yang bernilai kosong akan disimpan sebagai `NULL` di database MySQL tanpa menghentikan proses penyimpanan kolom sensor lainnya.

### E. Penanganan Kegagalan Koneksi (Google Sheets Unavailable / Rate Limit)
- Menggunakan arsitektur Laravel Queue (`sync-google-sheets-job`) dengan kebijakan *exponential backoff retry* (1 menit, 5 menit, 15 menit).
- Sirkuit proteksi (*circuit breaker*): Jika gagal 5 kali berturut-turut, notifikasi audit dikirimkan ke administrator dan job dipause sementara.

### F. Mekanisme Autentikasi
- Wajib menggunakan **Google Cloud Service Account** resmi dengan *private key JSON* yang aman disimpan pada direktori penyimpanan terisolasi (`storage/app/credentials/google-service-account.json`) dan tidak di-*commit* ke Git.
- Akses Spreadsheet diberikan secara spesifik ke *Service Account Client Email* dengan hak akses *Viewer*.

---

## 5. REKOMENDASI UNTUK TIM ELEKTRO SEBELUM IMPLEMENTASI PHASE BERIKUTNYA

Agar integrasi Google Sheets dapat diaktifkan pada phase berikutnya, Tim Elektro dimohon melengkapi:
1. **Spreadsheet ID Resmi:** Bagian ID unik dari URL Google Sheets.
2. **Sheet Name Baku:** Nama lembar kerja yang disepakati (misal: `Telemetry_Greenhouse_A`).
3. **Format Tanggal Baku:** Disepakati menggunakan format ISO 8601 (`YYYY-MM-DD HH:mm:ss`) zona waktu Asia/Jakarta (WIB).
4. **Service Account Email:** Email akun layanan Google Cloud yang telah diberi izin akses.
