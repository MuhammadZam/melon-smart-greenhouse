# INTEGRATION READINESS REPORT
## Sistem Web Monitoring Tanaman Melon — Phase 16.1

| Attribute | Details |
| :--- | :--- |
| **System Version** | Phase 16.1 Integration Audit |
| **Framework** | Laravel 11.57.0 / PHP 8.2.12 |
| **Database** | SQLite 3 (`database/database.sqlite`) / MySQL 8.0 Compatible |
| **Telemetry Pipeline** | ESP32 → Google Sheets → Laravel Sync → MySQL/DB → Dashboard → Alarm → Fuzzy → Reports |
| **Audit Date** | October 2026 |
| **Audit Status** | **SOFTWARE MOCK VERIFIED / HARDWARE & LIVE CREDENTIALS PENDING** |

---

## 1. EXECUTIVE SUMMARY

Pengujian kesiapan integrasi (Integration Readiness) pada Phase 16.1 bertujuan untuk memvalidasi seluruh rantai pipa telemetri sistem pemantauan melon, mulai dari sumber data hingga pelaporan akhir. Pengujian ini memastikan bahwa seluruh kontrak data, sanitasi, parser, pencegahan duplikasi, evaluasi alarm, inferensi fuzzy, dan rendering antarmuka berfungsi tanpa celah logika.

Berdasarkan hasil pengujian mendalam, seluruh **arsitektur perangkat lunak, parser telemetri, kontrak 15 kolom, pencegahan duplikasi, dan pipeline sinkronisasi internal telah diverifikasi 100% lulus (SOFTWARE MOCK VERIFIED)**. Namun demikian, karena perangkat keras ESP32 fisik dan kredensial akun layanan Google Cloud resmi dari tim Teknik Elektro belum dipasang pada lingkungan pengujian ini, status integrasi eksternal secara jujur dan transparan diklasifikasikan sebagai **PENDING**.

---

## 2. INTEGRATION STAGE STATUS MATRIX

Pipa integrasi telemetri terbagi menjadi 7 (tujuh) tahapan berurutan. Status kesiapan dari masing-masing tahapan adalah sebagai berikut:

```
[1. ESP32 Sensor]
       │ (HTTP POST / Apps Script)
       ▼
[2. Google Sheets Sheet1]
       │ (Google Sheets API v4 / Mock Client)
       ▼
[3. Laravel Sync Service]
       │ (Eloquent ORM / DB Transaction)
       ▼
[4. Database Storage]
       ├─────────────────┬─────────────────┐
       ▼                 ▼                 ▼
[5. Dashboard Web]  [6. Alarm Engine]  [7. Fuzzy Logic]
       │                 │                 │
       └─────────────────┼─────────────────┘
                         ▼
                [8. PDF & CSV Reports]
```

| No | Tahapan Pipeline | Status Kesiapan | Rincian & Bukti Verifikasi |
| :---: | :--- | :---: | :--- |
| **1** | **ESP32 Telemetry Dispatch** | `ESP32 HARDWARE PENDING` | Firmware ESP32 dan modul sensor fisik berada di bawah kendali tim Teknik Elektro. Kontrak 15 kolom disimulasikan menggunakan generator payload dan Python IoT simulator (`simulator/iot_simulator.py`). |
| **2** | **Google Sheets Ingestion** | `GOOGLE SHEETS LIVE PENDING` | Spreadsheet mock teruji penuh via `MockGoogleSheetsClient`. Integrasi live menunggu injeksi kredensial Google Service Account JSON resmi (`credentials/google_sheets_credentials.json`). |
| **3** | **Laravel Synchronization** | `SOFTWARE MOCK VERIFIED` | `GoogleSheetsSyncService` dan `GoogleSheetsTelemetryService` lulus 22 skenario feature test. Mendukung pemrosesan batch, sanitasi tipe data, parsing timestamp fleksibel, dan log sinkronisasi terperinci. |
| **4** | **Database Persistence** | `SOFTWARE MOCK VERIFIED` | Tabel `monitorings`, `plants`, `iot_devices`, `monitoring_alarms`, `fuzzy_inferences`, dan `google_sheets_sync_logs` menyimpan data secara atomik dan konsisten. |
| **5** | **Dashboard & Visualisasi** | `SOFTWARE MOCK VERIFIED` | 7 section dashboard (Greenhouse, Sensors, Trends, Alarm, Fuzzy, Actuator, Health) membaca data aktual secara langsung tanpa data palsu/hardcoded. |
| **6** | **Alarm Evaluation** | `SOFTWARE MOCK VERIFIED` | Alarm dievaluasi seketika saat data disimpan. Kebijakan strictly 4 parameter terbukti berjalan tanpa memicu alarm palsu pada 4 parameter non-alarm. |
| **7** | **Fuzzy Inference Logic** | `SOFTWARE MOCK VERIFIED` | Mesin Mamdani PHP mengeksekusi defuzzifikasi centroid dengan presisi numerik `86.1700` (BAIK) pada baseline 27°C/70%/70%/6.5. Validasi agronomis lapangan berstatus `AGRONOMIC FUZZY VALIDATION PENDING`. |
| **8** | **Reporting (PDF & CSV)** | `SOFTWARE MOCK VERIFIED` | Data telemetri yang disinkronkan langsung muncul pada filter laporan, ringkasan statistik, ekspor PDF A4 landscape, dan CSV UTF-8 streaming. |

---

## 3. TELEMETRY CONTRACT & MAPPING SPECIFICATION

Kontrak data telemetri terdiri dari 15 kolom wajib dan opsional yang disepakati bersama tim integrasi IoT:

| Indeks Kolom | Nama Kolom Spreadsheet | Tipe Data Target | Kolom Database (`monitorings`) | Sifat | Validasi & Penanganan |
| :---: | :--- | :--- | :--- | :---: | :--- |
| **0** | `device_id` | `VARCHAR(50)` | `device_id` | **Wajib** | Harus terdaftar atau otomatis diregistrasikan di `iot_devices`. Jika kosong, baris ditolak. |
| **1** | `recorded_at` | `DATETIME` | `recorded_at` | **Wajib** | Mendukung `Y-m-d H:i:s`, ISO 8601, dan Unix timestamp. Baris dengan format tanggal cacat ditolak. |
| **2** | `plant_id` / `plant_name`| `BIGINT` | `plant_id` | Opsional | Dicocokkan ke nama tanaman aktif (`Plant::firstWhere('name', ...)`). Fallback ke tanaman aktif pertama. |
| **3** | `temperature` | `DECIMAL(5,2)` | `temperature` | **Wajib (Alarm 1)** | Rentang valid: 0.0 - 60.0 °C. Cacat jika non-numerik. |
| **4** | `humidity` | `DECIMAL(5,2)` | `humidity` | **Wajib (Alarm 2)** | Rentang valid: 0.0 - 100.0 %. Cacat jika non-numerik. |
| **5** | `soil_moisture` | `DECIMAL(5,2)` | `soil_moisture` | **Wajib (Alarm 3)** | Rentang valid: 0.0 - 100.0 %. Cacat jika non-numerik. |
| **6** | `ph` | `DECIMAL(4,2)` | `ph` | **Wajib (Alarm 4)** | Rentang valid: 0.0 - 14.0. Cacat jika non-numerik. |
| **7** | `ec` | `DECIMAL(5,2)` | `ec` | Opsional | Nilai null disimpan sebagai `NULL` (tidak diisi `0.00`). Bukan parameter alarm. |
| **8** | `light_intensity` | `DECIMAL(8,2)` | `light_intensity` | Opsional | Nilai null disimpan sebagai `NULL`. Bukan parameter alarm. |
| **9** | `nutrient_temp` | `DECIMAL(5,2)` | `nutrient_temp` | Opsional | Nilai null disimpan sebagai `NULL`. Bukan parameter alarm. |
| **10** | `water_tank_level`| `DECIMAL(5,2)` | `water_tank_level` | Opsional | Nilai null disimpan sebagai `NULL`. Bukan parameter alarm. |
| **11** | `reported_pump_state`| `VARCHAR(10)` | `reported_pump_state`| Opsional | Hanya merekam status observasi ('ON'/'OFF'/'AUTO'). Tidak mengontrol hardware. |
| **12** | `reported_fan_state` | `VARCHAR(10)` | `reported_fan_state` | Opsional | Hanya merekam status observasi ('ON'/'OFF'/'AUTO'). Tidak mengontrol hardware. |
| **13** | `fuzzy_score` | `DECIMAL(5,2)` | `fuzzy_score` | Opsional | Jika disediakan ESP32, nilai dipertahankan (`external_device`). Jika null, dihitung oleh engine PHP. |
| **14** | `fuzzy_label` | `VARCHAR(20)` | `fuzzy_label` | Opsional | Label kategori kondisi (OPTIMAL / BAIK / WASPADA / BURUK). |

---

## 4. DUPLICATE & CURSOR BEHAVIOR VERIFICATION

### A. Pencegahan Duplikasi Data
Pencegahan duplikasi diuji secara komprehensif pada pengujian unit dan integrasi:
- Kombinasi unik `(device_id, recorded_at)` dicegah dari penyimpanan ganda melalui mekanisme verifikasi keberadaan rekaman sebelum `insert`.
- Jika baris dengan `device_id` dan `recorded_at` yang sama disinkronkan kembali, sistem menandai baris tersebut sebagai duplikat, tidak melakukan penimpaan, dan mencatatnya ke dalam `records_skipped`.

### B. Perilaku Kursor (High-Water Mark) & Mitigasi Baris Sisipan
- **Kondisi Awal**: Sinkronisasi membaca baris Google Sheets mulai dari `last_synced_row + 1` hingga batas `batch_limit`.
- **Risiko Teridentifikasi**: Jika operator memasukkan baris baru di tengah spreadsheet (misalnya baris 5 disisipkan saat kursor berada di baris 50), kursor high-water mark berpotensi melewatkan baris sisipan tersebut.
- **Solusi & Perbaikan**:
  1. Ditambahkan fitur **Reset Kursor** (`reset_cursor = 1`) pada antarmuka admin dan backend controller.
  2. Ketika kursor di-reset ke baris 1, seluruh baris dipindai ulang secara aman. Karena mekanisme anti-duplikasi aktif, baris yang sudah ada sebelumnya dilewati tanpa error, sementara baris baru/sisipan berhasil diimpor.
  3. Fitur ini diuji dan lulus pada `GoogleSheetsTelemetryAndSecurityTest::test_22_manual_sync_with_reset_cursor_resets_high_water_mark_safely()`.

---

## 5. HARDWARE ISOLATION & ACTUATOR SAFETY AUDIT

Salah satu persyaratan terpenting sistem ini adalah **pemisahan mutlak antara modul perangkat lunak dengan kontrol aktuator fisik**:
1. **Mode Operasi**: Seluruh kontrol aktuator berstatus **SIMULATION ONLY**.
2. **Flag Hardware**: Kolom `hardware_dispatched` pada tabel `actuator_command_logs` secara permanen bernilai `false` (0).
3. **Ketiadaan Relay Driver**: Backend tidak memiliki endpoint jaringan, socket, maupun serial port yang membuka sinyal relay ke fisik.
4. **Fuzzy Decoupling**: Hasil inferensi fuzzy bertindak murni sebagai Sistem Pendukung Keputusan (Decision Support System / DSS) dan **tidak pernah memicu kontrol otomatis pompa maupun kipas**.

---

## 6. SINKRONISASI STALE & ANOMALOUS TELEMETRY

Sistem telah diuji terhadap skenario data anomali:
1. **Stale Telemetry Warning**: Jika data telemetri yang diterima memiliki selisih waktu `recorded_at` lebih dari 1 jam terhadap waktu server, sistem mencatat peringatan telemetry kadaluarsa pada log sinkronisasi dan antarmuka.
2. **Missing Essential Fields**: Baris data tanpa salah satu dari 4 parameter utama (`temperature`, `humidity`, `soil_moisture`, `ph`) ditolak dan dicatat pada `records_failed` dengan pesan kegagalan spesifik.
3. **Out-of-Range Guardrails**: Nilai suhu > 100°C atau pH < 0 / > 14 ditolak untuk mencegah polusi basis data dan eror numerik fuzzy.

---

## 7. KESIMPULAN & LANGKAH MENUJU PHASE 17

1. **Software Pipeline**: 100% SIAP. Kontrak 15 kolom, sanitasi, database, alarm 4 parameter, fuzzy engine, dan export laporan berfungsi sempurna.
2. **Hardware & Cloud Deployment**: Membutuhkan serah terima kredensial Google Cloud Service Account dan perangkat fisik ESP32 dari tim Teknik Elektro sebelum pilot lapangan Phase 17 dijalankan.
