# IoT Sensor Data Simulator & ESP32 Integration Contract (Phase 11)

> **Kontrak aktif Phase 16.2:** token perangkat wajib pada semua environment. Isi `DEVICE_TOKEN` dengan token perangkat terdaftar atau gunakan `--device-token`. API baca internal memerlukan sesi pengguna dan tidak dapat dibaca dengan token perangkat. Simulator mengirim `event_id` unik untuk setiap observasi; kirim ulang payload yang sama dengan ID yang sama saat retry. Respons baru `201`, replay `200`; keduanya berhasil. Lihat [kontrak dan hasil Phase 16.2](../docs/PHASE_16_2_DEDUPLICATION_FIX.md).

Python portable untuk Windows tersedia setelah setup pengujian ini di `docs/phase16_2/tools/python/python.exe`. Dari root project:

```powershell
# DEVICE_TOKEN harus diisi di environment atau iot-simulator/.env sesuai akun perangkat.
.\docs\phase16_2\tools\python\python.exe .\iot-simulator\simulator.py --once --plant-id 1
```

Folder runtime portable diabaikan Git. Pada komputer lain gunakan Python 3.10+ dan dependensi opsional dari requirements.txt. Pengujian ini tidak mengklaim ESP32 fisik sudah terhubung.

Direktori ini berisi simulator data sensor eksternal untuk **Sistem Web Monitoring Tanaman Melon** serta spesifikasi kontrak komunikasi IoT (ESP32 Ready).

> **PENTING — PERNYATAAN STATUS INTEGRASI FISIK:**
> Sistem aplikasi saat ini berada pada tahap **IoT Integration Readiness (Phase 11)**. Seluruh endpoint API, skema database, validasi payload, dan visualisasi dashboard telah siap menerima data telemetri. Data yang dihasilkan simulator ini adalah **DATA SIMULASI**, BUKAN data sensor fisik aktual. Sensor fisik, kalibrasi ADC/I2C/OneWire, dan mikrokontroler ESP32 dikembangkan serta divalidasi oleh **Tim Teknik Elektro**.

---

## 1. Pembagian Tanggung Jawab Tim (Division of Responsibility)

| Bidang | Tim Bertanggung Jawab | Cakupan Kerja |
|---|---|---|
| **Perangkat Keras & Firmware** | **Teknik Elektro** | Pemilihan sensor fisik, wiring/rangkaian proteksi, kalibrasi analog/digital, firmware ESP32 (C++/Arduino), koneksi Wi-Fi/GSM, dan kontrol aktuator fisik (relay/pompa). |
| **Sistem Backend, API & Web** | **Teknik Informatika** | Arsitektur REST API ingestion, validasi rentang fisik, penyimpanan database, konfigurasi ambang batas agronomis (configurable), otorisasi RBAC, dashboard monitoring & analitik Chart.js. |

---

## 2. Arsitektur Komunikasi IoT

```text
┌────────────────────────────────────────────────────────────────────────┐
│ PRODI TEKNIK ELEKTRO                                                   │
│ [ESP32 / Firmware C++] OR [Python IoT Simulator (Data Sintetis)]        │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
                                    │ HTTP POST (application/json)
                                    │ Endpoint: /api/monitorings
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│ PRODI TEKNIK INFORMATIKA (Web & API Backend)                           │
│ 1. Ingestion Layer: StoreMonitoringApiRequest (Validasi Batas Fisik)   │
│ 2. Persistence: MySQL / SQLite (Eloquent ORM, Nullable Fields)         │
│ 3. Threshold Evaluation: Parameter Monitoring Configurable             │
│ 4. Analytics & UI: Dashboard, Chart.js Visualizations (Null-safe)       │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Spesifikasi 8 Parameter Sensor IoT

| No | Nama Field JSON | Tipe Data | Satuan | Batas Validasi Fisik (API) | Ambang Agronomis Ideal (Default Melon) | Keterangan |
|---|---|---|---|---|---|---|
| 1 | `temperature` | Float / Nullable | °C | 0.00 – 60.00 | 20.00 – 32.00 | Suhu udara mikroklimat greenhouse |
| 2 | `humidity` | Float / Nullable | % RH | 0.00 – 100.00 | 60.00 – 85.00 | Kelembapan relatif udara |
| 3 | `soil_moisture` | Float / Nullable | % | 0.00 – 100.00 | 60.00 – 80.00 | Kelembapan media tanam / cocopeat |
| 4 | `ph` | Float / Nullable | pH | 0.00 – 14.00 | 5.80 – 7.20 | Derajat keasaman larutan nutrisi |
| 5 | `ec` | Float / Nullable | mS/cm | 0.00 – 20.00 | 1.80 – 2.80 | Konduktivitas elektrik larutan nutrisi |
| 6 | `light_intensity` | Float / Nullable | lux | 0.0 – 200,000.0 | 15,000.0 – 50,000.0 | Intensitas radiasi cahaya matahari |
| 7 | `nutrient_temperature` | Float / Nullable | °C | 0.00 – 60.00 | 20.00 – 26.00 | Suhu larutan nutrisi tandon |
| 8 | `tank_level` | Float / Nullable | % | 0.00 – 100.00 | 40.00 – 95.00 | Cadangan volume air tandon nutrisi |

### Parameter Tambahan (Morfologi & Observasi Lapangan):
- `plant_id` (Integer, Required): ID tanaman yang dipantau (referensi tabel `plants`).
- `monitored_at` (Datetime/String, Optional): Timestamp observasi (`YYYY-MM-DD HH:MM:SS`). Jika diabaikan, server menggunakan waktu saat ini (`now()`).
- `plant_height` (Float, Optional): Tinggi tanaman (cm). Batas fisik: 0.0 – 500.0 cm.
- `canopy_density` / `plant_density` (Float, Optional): Kerapatan kanopi daun (%). Batas fisik: 0.0 – 100.0 %.
- `condition` / `plant_condition` (String, Optional): Status kualitatif (`Subur & Baik`, `Baik`, `Perlu Perhatian`, `Kurang Baik / Kritis`, `optimal`, `warning`, `critical`).
- `leaf_color` / `plant_color` (String, Optional): Warna visual tajuk/daun (`Hijau Segar`, `Hijau Pekat`, `Hijau Muda`, `Hijau Kekuningan`, `Kekuningan`, `Cokelat Kering`).
- `notes` (String, Optional): Catatan log observasi.

---

## 4. Kontrak JSON Payload ESP32

### A. Payload Lengkap 8 Sensor (Full Payload)
```json
{
  "plant_id": 1,
  "monitored_at": "2026-10-14 10:30:00",
  "temperature": 28.5,
  "humidity": 72.0,
  "soil_moisture": 68.5,
  "ph": 6.35,
  "ec": 2.25,
  "light_intensity": 32500.0,
  "nutrient_temperature": 23.8,
  "tank_level": 85.0,
  "plant_height": 75.0,
  "canopy_density": 65.0,
  "condition": "optimal",
  "leaf_color": "Hijau Segar",
  "notes": "Telemetri lengkap ESP32 node greenhouse A"
}
```

### B. Payload Parsial (Partial Telemetry)
Jika modul sensor tertentu sedang offline, belum terpasang, atau dalam kalibrasi:
```json
{
  "plant_id": 1,
  "temperature": 29.1,
  "humidity": 70.4,
  "ec": 2.10,
  "ph": 6.40
}
```
*Field yang tidak disertakan akan bernilai `null` di database tanpa menyebabkan error validasi 422.*

### C. Payload Legacy (Kompatibilitas Penuh Fase 1–10)
```json
{
  "plant_id": 1,
  "temperature": 27.5,
  "ph": 6.2,
  "humidity": 78.0,
  "plant_height": 55.0,
  "canopy_density": 60.0
}
```

---

## 5. Rekomendasi Keamanan & Autentikasi ESP32

1. **Header Standar**:
   - `Content-Type: application/json`
   - `Accept: application/json`
   - `User-Agent: ESP32-MelonClient/1.0`
2. **Kredensial & Token**:
   - Untuk Phase 11, endpoint ingestion terbuka dalam jaringan intranet/greenhouse lokal atau diotentikasi via session/Sanctum.
   - Pada Phase lanjutan (Phase 12), siapkan header `X-Device-Token: <SECRET_TOKEN>` atau `Authorization: Bearer <TOKEN>` yang dikonfigurasi per ESP32 tanpa melakukan hardcode kredensial publik.
3. **Penanganan Error Validasi (HTTP 422)**:
   - Jika nilai sensor melampaui batas fisik (misal: sensor rusak mengirim pH = 99 atau temperatur = -50), server merespons HTTP 422 dengan pesan JSON deskriptif. ESP32 harus memprogram fallback retry atau error indicator lokal (LED).

---

## 6. Penggunaan Python Simulator

### Persyaratan:
- Python 3.9+ (tanpa dependensi eksternal wajib, menggunakan library bawaan `urllib`).

### Perintah Eksekusi:

```bash
# 1. Pengiriman satu kali data simulasi (One-time test)
python simulator.py --once

# 2. Pengiriman berkala setiap 5 detik
python simulator.py --interval 5

# 3. Menargetkan tanaman ID tertentu
python simulator.py --plant-id 2 --once

# 4. Menargetkan URL server tertentu
python simulator.py --api-url http://192.168.1.100:8000/api/monitorings --once
```
