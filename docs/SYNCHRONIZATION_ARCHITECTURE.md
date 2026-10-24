# ARSITEKTUR SINKRONISASI TELEMETRI (PHASE 14)
## SISTEM WEB MONITORING TANAMAN MELON

**Versi Dokumen:** 1.0  
**Tanggal:** 16 Oktober 2026  
**Komponen Utama:** `GoogleSheetsSyncService`, `GoogleSheetsTelemetryService`, `SyncGoogleSheetsTelemetryCommand`

---

## 1. DIAGRAM ALUR DATA SINKRONISASI (SEQUENCE DIAGRAM)

```
+──────────────+      +───────────────────+      +───────────────────────+      +──────────────────+      +──────────────────+
|  Cron / CLI  |      | GoogleSheetsSync  |      |  TelemetryService /   |      |  Database MySQL  |      | MonitoringAlarm  |
| / Web Admin  |      |      Service      |      |   SheetsApiClient     |      |  (monitorings)   |      |     Service      |
+──────────────+      +───────────────────+      +───────────────────────+      +──────────────────+      +──────────────────+
       │                        │                            │                            │                         │
       │─── 1. sync() ─────────►│                            │                            │                         │
       │                        │─── 2. Acquire Cache Lock ─►│                            │                         │
       │                        │    (prevent overlap)       │                            │                         │
       │                        │                            │                            │                         │
       │                        │─── 3. fetchRows(range) ───►│                            │                         │
       │                        │    "Telemetry!A101:O150"   │                            │                         │
       │                        │◄── 4. raw rows array ──────│                            │                         │
       │                        │                            │                            │                         │
       │                        │─── 5. parseAndValidate() ──│                            │                         │
       │                        │    - bounds checking       │                            │                         │
       │                        │    - timestamp normalize   │                            │                         │
       │                        │    - plant_code resolve    │                            │                         │
       │                        │    - hash source_id        │                            │                         │
       │                        │                            │                            │                         │
       │                        │─── 6. Check Idempotency ───────────────────────────────►│                         │
       │                        │    (source_identifier exists?)                          │                         │
       │                        │◄── 7. False (Unique Record) ────────────────────────────│                         │
       │                        │                                                         │                         │
       │                        │─── 8. DB::transaction() Monitoring::create() ──────────►│                         │
       │                        │                                                         │                         │
       │                        │─── 9. evaluate(monitoring) ──────────────────────────────────────────────────────►│
       │                        │    (Strict 4-parameter rule)                            │                         │
       │                        │◄── 10. Alarm evaluated / resolved ────────────────────────────────────────────────│
       │                        │                                                         │                         │
       │                        │─── 11. Update cursor & Write SyncLog ──────────────────►│                         │
       │                        │─── 12. Release Lock ───────────────────────────────────►│                         │
       │◄── 13. SyncLog Record ─│                                                         │                         │
       │                        │                                                         │                         │
```

---

## 2. STRATEGI KURSOR INKREMENTAL (HIGH-WATER MARK CURSOR)

### Batasan Google Sheets API
Google Sheets REST API memberlakukan kuota:
- Maksimum **60 request per menit per user**.
- Pembacaan seluruh spreadsheet (ribuan baris) secara berulang pada setiap polling akan memboroskan bandwidth, meningkatkan latensi hingga hitungan detik, dan memicu error `HTTP 429 Rate Limit`.

### Solusi Desain Kursor
1. Tabel `google_sheets_sync_configs` menyimpan `last_synced_row` (nilai integer, awal: 1).
2. Setiap kali sinkronisasi dijalankan:
   - Baris awal dihitung: $\text{startRow} = \text{last\_synced\_row} + 1$.
   - Baris akhir dihitung: $\text{endRow} = \text{startRow} + \text{batchLimit} - 1$.
   - Format query: `Telemetry!A{startRow}:O{endRow}` (misal: `Telemetry!A101:O200`).
3. Hanya data baru yang ditarik dari Google API.
4. Setelah baris diproses, `last_synced_row` diperbarui ke indeks baris tertinggi yang berhasil dibaca.

---

## 3. IDENTITAS SUMBER & IDEMPOTENSI (DEDUPLICATION)

Spreadsheet dapat diurutkan ulang (*sorted*), difilter, atau baris dapat disisipkan di tengah oleh pengguna. Oleh karena itu, **nomor baris tidak dapat dijadikan identitas permanen**.

### Algoritma Hash `source_identifier`
Setiap pembacaan telemetri menghasilkan signature kriptografis:

$$\text{source\_identifier} = \text{SHA256}(\text{device\_id} \parallel \text{"\|"} \parallel \text{monitored\_at} \parallel \text{"\|"} \parallel \text{plant\_id})$$

Sebelum melakukan `Monitoring::create()`:
```php
$isDuplicate = Monitoring::where('source_identifier', $sourceIdentifier)
    ->orWhere(function ($q) use ($data) {
        $q->where('plant_id', $data['plant_id'])
          ->where('monitored_at', $data['monitored_at']);
    })
    ->exists();
```
Jika duplikat terdeteksi:
- Baris dilewati secara aman (*skipped*).
- Penghitung `records_skipped` bertambah.
- Tidak terjadi duplikasi data pada database MySQL.
- Histori data lama tetap terjaga utuh.

---

## 4. INTEGRASI EVALUASI ALARM (PIPELINE CONTINUITY)

Data telemetri yang diimpor dari Google Sheets langsung dialirkan ke `MonitoringAlarmService::evaluate($monitoring)`.
Aturan evaluasi:
1. **Kebijakan 4-Parameter:** Hanya parameter `temperature`, `humidity`, `soil_moisture`, dan `ph` yang dapat menciptakan atau memperbarui alarm aktif (`status = 'active'`).
2. **Sensor Lain Bersifat Visual:** Parameter seperti `ec`, `light_intensity`, `nutrient_temperature`, dan `tank_level` tetap disimpan di database untuk analisis grafik, namun tidak memicu alarm keselamatan.
3. **Pencegahan Alarm Palsu pada Data Out-of-Order:** Evaluasi alarm hanya membandingkan threshold terhadap timestamp observasi aktual `monitored_at`.
