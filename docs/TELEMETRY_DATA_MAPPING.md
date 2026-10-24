# PEMETAAN DATA TELEMETRI & KONTRAK INTEGRASI (PHASE 14)

> **Pembaruan Phase 16.2:** sumber REST ditentukan server dari tipe perangkat: `python_simulator` menjadi `simulator`, perangkat lainnya `api`; sinkronisasi Sheets tetap `google_sheets`. Client tidak dapat mengubah sumber atau identitas perangkat terautentikasi melalui body. Metadata tambahan `event_id` / `Idempotency-Key` dan `fuzzy_score` dijelaskan di [kontrak deduplikasi](PHASE_16_2_DEDUPLICATION_FIX.md) dan [fuzzy zero](PHASE_16_2_FUZZY_ZERO_FIX.md). Histori lama tidak direklasifikasi otomatis.
## SISTEM WEB MONITORING TANAMAN MELON

**Versi Dokumen:** 1.0  
**Tanggal:** 16 Oktober 2026  
**Referensi:** `GOOGLE_SHEETS_INTEGRATION_CONTRACT.md` (Phase 13 Baseline)

---

## 1. MATRIKS PEMETAAN KOMPREHENSIF

Tabel berikut mendefinisikan transformasi dan validasi data dari sumber telemetri (Google Sheets dan REST API) ke kolom database MySQL:

| No | Kolom Spreadsheet | Target Kolom Database | Tipe Data DB | Wajib / Opsional | Batas Nilai / Format | Perilaku Jika Kosong / Anomali |
| :-: | :--- | :--- | :--- | :---: | :--- | :--- |
| 1 | `device_id` | `monitorings.device_id` | VARCHAR(255) | **Wajib** | Alfanumerik, max 50 char | Baris ditolak (`REJECTED_MISSING_DEVICE_ID`) |
| 2 | `timestamp` | `monitorings.monitored_at` | DATETIME | **Wajib** | ISO 8601 / `Y-m-d H:i:s` (WIB) | Baris ditolak jika format tanggal tidak valid |
| 3 | `plant_code` | `monitorings.plant_id` | BIGINT UNSIGNED | Opsional | Nama/lokasi tanaman atau ID numerik | Resolusi otomatis ke tanaman aktif pertama di database |
| 4 | `temperature` | `monitorings.temperature` | DECIMAL(5,2) | **Wajib** | 0.00 – 60.00 °C | Ditolak jika non-numerik / di luar rentang |
| 5 | `humidity` | `monitorings.humidity` | DECIMAL(5,2) | **Wajib** | 0.00 – 100.00 % RH | Ditolak jika non-numerik / di luar rentang |
| 6 | `soil_moisture` | `monitorings.soil_moisture` | DECIMAL(5,2) | **Wajib** | 0.00 – 100.00 % | Ditolak jika non-numerik / di luar rentang |
| 7 | `ph` | `monitorings.ph` | DECIMAL(4,2) | **Wajib** | 0.00 – 14.00 pH | Ditolak jika non-numerik / di luar rentang |
| 8 | `ec` | `monitorings.ec` | DECIMAL(5,2) | Opsional | 0.00 – 10.00 mS/cm | Disimpan sebagai `NULL` jika kosong |
| 9 | `light_intensity` | `monitorings.light_intensity` | DECIMAL(8,2) | Opsional | 0.00 – 100,000.00 lux | Disimpan sebagai `NULL` jika kosong |
| 10 | `nutrient_temp` | `monitorings.nutrient_temperature` | DECIMAL(5,2) | Opsional | 0.00 – 60.00 °C | Disimpan sebagai `NULL` jika kosong |
| 11 | `tank_level` | `monitorings.tank_level` | DECIMAL(5,2) | Opsional | 0.00 – 100.00 % | Disimpan sebagai `NULL` jika kosong |
| 12 | `pump_status` | `monitorings.reported_pump_state` | VARCHAR(255) | Opsional | `'ON'`, `'OFF'` | Diabaikan/`NULL` jika tidak valid (terisolasi dari simulasi) |
| 13 | `fan_status` | `monitorings.reported_fan_state` | VARCHAR(255) | Opsional | `'ON'`, `'OFF'` | Diabaikan/`NULL` jika tidak valid (terisolasi dari simulasi) |
| 14 | `fuzzy_score` | `monitorings.fuzzy_score` | DECIMAL(5,2) | Opsional | 0.00 – 100.00 | Draf inferensi AI (disimpan jika valid) |
| 15 | `fuzzy_label` | `monitorings.fuzzy_label` | VARCHAR(255) | Opsional | String kategori (mis: `OPTIMAL`) | Draf label inferensi AI |
| - | - | `monitorings.source` | VARCHAR(255) | Otomatis | `'google_sheets'`, `'api'` | Diisi otomatis oleh service ingestion |
| - | - | `monitorings.source_identifier` | VARCHAR(255) | Otomatis | SHA-256 Hash unik | `sha256(device_id + monitored_at + plant_id)` |

---

## 2. ATURAN NORMALISASI & PARSING DATA

Service `GoogleSheetsTelemetryService` menerapkan filter normalisasi berikut:
1. **Pembersihan Whitespace:** Seluruh string teks di-`trim()` sebelum divalidasi.
2. **Koma Desimal Indonesia vs Titik:** Representasi desimal berkoma (misal: `"28,5"`) secara otomatis dikonversi ke format titik baku (`28.5`). Nilai string seperti `"-"` atau `"null"` dianggap kosong (`NULL`).
3. **Multi-Format Datetime:** Parser mendukung hingga 8 variasi representasi tanggal waktu:
   - `Y-m-d H:i:s` (contoh: `2026-10-16 14:00:00`)
   - `Y-m-d\TH:i:s` (ISO 8601 tanpa timezone)
   - `Y-m-d\TH:i:sP` (ISO 8601 dengan offset)
   - `d/m/Y H:i:s` (format lembar kerja regional Excel/Sheets)
   - `d-m-Y H:i:s`
   Zona waktu otomatis diselaraskan ke **Asia/Jakarta (WIB)**.
4. **Isolasi Status Aktuator:**
   Status telemetri yang dilaporkan oleh sensor lapangan (`reported_pump_state`, `reported_fan_state`) disimpan pada field tersendiri dan **sama sekali tidak mengubah** field `actuators.simulation_state` milik modul simulasi Phase 13.
