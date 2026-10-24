# PANDUAN PENYIAPAN & INTEGRASI GOOGLE SHEETS (PHASE 14)
## SISTEM WEB MONITORING TANAMAN MELON

**Versi Dokumen:** 1.0  
**Status Integrasi Fisik:** **PENDING** (Menunggu Kredensial Resmi Tim Elektro)  
**Mode Operasional Saat Ini:** Adapter Mock (Pengujian Otomatis & Simulasi)

---

## 1. PENDAHULUAN

Panduan ini ditujukan bagi Administrator Sistem dan Tim Teknik Elektro untuk mengaktifkan jalur telemetri resmi Google Sheets ke sistem Laravel:

$$\text{ESP32 Node} \longrightarrow \text{Google Sheets} \longrightarrow \text{Laravel Sync Engine} \longrightarrow \text{MySQL} \longrightarrow \text{Dashboard}$$

> [!IMPORTANT]
> **Peringatan Keamanan & Privasi:**
> Dilarang keras membagikan Spreadsheet dengan izin *"Anyone with the link can edit/view"* sebagai jalan pintas. Integrasi produksi wajib menggunakan **Google Cloud Service Account** resmi dengan hak akses baca (*Viewer*) khusus.

---

## 2. LANGKAH PENYIAPAN GOOGLE CLOUD SERVICE ACCOUNT

### Langkah 1: Buat Proyek di Google Cloud Console
1. Buka [Google Cloud Console](https://console.cloud.google.com/).
2. Buat proyek baru dengan nama: `Melon-Monitoring-IoT`.
3. Aktifkan API:
   - Navigasi ke **APIs & Services > Library**.
   - Cari **Google Sheets API**, lalu klik **Enable**.

### Langkah 2: Buat Service Account
1. Navigasi ke **APIs & Services > Credentials**.
2. Klik **Create Credentials > Service Account**.
3. Beri nama: `melon-sheets-ingestion`.
4. Salin email Service Account yang dihasilkan (contoh: `melon-sheets-ingestion@melon-monitoring-iot.iam.gserviceaccount.com`).
5. Pada tab **Keys**, klik **Add Key > Create new key > JSON**.
6. Simpan file JSON yang terunduh.

### Langkah 3: Penempatan Kredensial di Server Laravel
1. Tempatkan file JSON di direktori aman yang tidak dapat diakses publik:
   ```bash
   storage/app/credentials/google-service-account.json
   ```
2. Pastikan file ini terlindungi oleh `.gitignore` (sudah dikonfigurasi pada Phase 14).
3. Atur permission file di server Linux (jika deployment production):
   ```bash
   chmod 600 storage/app/credentials/google-service-account.json
   ```

### Langkah 4: Bagikan Akses Spreadsheet
1. Buka Google Sheets dokumen telemetri yang dibuat oleh Tim Elektro.
2. Klik tombol **Share** di pojok kanan atas.
3. Tambahkan email Service Account (`melon-sheets-ingestion@...`).
4. Berikan peran: **Viewer** (hanya hak baca; sistem tidak pernah mengubah spreadsheet sumber).
5. Hilangkan centang *Notify people*, lalu klik **Share**.

---

## 3. FORMAT STRUKTUR SPREADSHEET (HEADER BAKU)

Lembar kerja harus diberi nama sheet yang disepakati (default: `Telemetry`).
Baris 1 wajib memuat header dengan urutan dan nama kolom persis sebagai berikut:

| Kolom | Nama Kolom Header | Tipe Data | Wajib/Opsional | Deskripsi |
| :---: | :--- | :--- | :---: | :--- |
| **A** | `device_id` | String | **Wajib** | Identifier perangkat ESP32 (contoh: `ESP32-MELON-01`) |
| **B** | `timestamp` | Datetime | **Wajib** | Format: `YYYY-MM-DD HH:mm:ss` (WIB) atau ISO 8601 |
| **C** | `plant_code` | String | Opsional | Kode/nama tanaman (contoh: `Melon Golden Test`). Fallback ke tanaman aktif pertama |
| **D** | `temperature` | Float | **Wajib** | Suhu udara (0.00 – 60.00 °C) |
| **E** | `humidity` | Float | **Wajib** | Kelembapan udara (0.00 – 100.00 % RH) |
| **F** | `soil_moisture` | Float | **Wajib** | Kelembapan media tanam (0.00 – 100.00 %) |
| **G** | `ph` | Float | **Wajib** | Derajat keasaman larutan (0.00 – 14.00 pH) |
| **H** | `ec` | Float | Opsional | Konduktivitas nutrisi (0.00 – 10.00 mS/cm) |
| **I** | `light_intensity` | Float | Opsional | Intensitas cahaya (0.00 – 100,000.00 lux) |
| **J** | `nutrient_temp` | Float | Opsional | Suhu larutan nutrisi (0.00 – 60.00 °C) |
| **K** | `tank_level` | Float | Opsional | Level tandon nutrisi (0.00 – 100.00 %) |
| **L** | `pump_status` | String | Opsional | Status fisik aktual: `ON` atau `OFF` |
| **M** | `fan_status` | String | Opsional | Status fisik aktual: `ON` atau `OFF` |
| **N** | `fuzzy_score` | Float | Opsional | Draf inferensi: Skor kenyamanan (0.00 – 100.00) |
| **O** | `fuzzy_label` | String | Opsional | Draf label: Contoh `OPTIMAL`, `WASPADA_SUHU` |

---

## 4. KONFIGURASI ENVIRONMENT LARAVEL (`.env`)

Tambahkan konfigurasi berikut pada `.env` untuk beralih dari mode Mock ke mode Live Service Account:

```env
# Mode Autentikasi: 'mock' (pengujian lokal) atau 'service_account' (live Google Cloud)
GOOGLE_SHEETS_AUTH_MODE=service_account

# Spreadsheet ID unik dari URL browser (https://docs.google.com/spreadsheets/d/<ID_INI>/edit)
GOOGLE_SHEETS_SPREADSHEET_ID=1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms

# Nama Sheet Tab Telemetri
GOOGLE_SHEETS_SHEET_NAME=Telemetry

# Path ke Kredensial Service Account JSON
GOOGLE_SHEETS_CREDENTIALS_PATH=storage/app/credentials/google-service-account.json

# Interval Polling Otomatis (Menit) & Timeout Jaringan (Detik)
GOOGLE_SHEETS_POLLING_INTERVAL=5
GOOGLE_SHEETS_TIMEOUT_SECONDS=10
```

---

## 5. PENGUJIAN & EKSEKUSI SINKRONISASI

### Melalui Artisan CLI:
```bash
# Uji sinkronisasi batch pertama (default 100 baris):
php artisan telemetry:sync-google-sheets

# Reset kursor baris kembali ke baris 1 (jika spreadsheet diisi ulang):
php artisan telemetry:sync-google-sheets --reset-cursor

# Menjalankan paksa meskipun konfigurasi ditandai non-aktif:
php artisan telemetry:sync-google-sheets --force
```

### Melalui Web Admin Panel:
1. Login sebagai Administrator (`admin@melon.com`).
2. Buka menu **Administrasi > Integrasi Google Sheets** (`/admin/integrations/google-sheets`).
3. Tinjau metrik kesegaran data dan parameter kursor.
4. Klik tombol **Sinkronkan Sekarang**.
