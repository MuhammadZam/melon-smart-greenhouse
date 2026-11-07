# Revisi persyaratan sensor — 7 November 2026

Status implementasi: tiga sensor aktif; validasi agronomis fuzzy dan threshold masih **PROVISIONAL**. Dokumen ini menggantikan kontrak empat/delapan sensor pada dokumentasi fase sebelumnya. Dokumen fase lama dipertahankan sebagai catatan historis.

## Kontrak aktif

| Parameter | JSON baru | Penyimpanan | Satuan | Validasi penerimaan |
|---|---|---|---|---|
| Suhu udara | `temperature` | `temperature` | °C | Numerik 0–60, wajib |
| Kelembapan tanah/media | `soil_moisture` | `soil_moisture` | % | Numerik 0–100, wajib |
| pH air | `water_ph` | `ph` | Tanpa satuan | Numerik 0–14, wajib |

Rentang penerimaan diwarisi dari validasi perangkat lunak sebelumnya; bukan persetujuan agronomis. Domain fuzzy suhu tetap 10–50 °C; observasi valid API di luar domain tersebut tersimpan, tetapi pipeline menghasilkan INSUFFICIENT_DATA.

Kelembapan udara, EC, intensitas cahaya, suhu nutrisi, level tandon, dan FAN-01 tidak ditampilkan sebagai fitur sensor/aktuator aktif. Dashboard memiliki tiga kartu sensor, tiga statistik sensor, dan tiga grafik sensor dalam satu baris responsif. Grafik distribusi tanaman/status/frekuensi tetap tersedia. Tinggi tanaman dan kerapatan kanopi dipertahankan sebagai observasi manual opsional pada pencatatan/tabel/laporan; keduanya bukan input fuzzy atau alarm aktif. Grafik dan statistik pertumbuhan pada dashboard dinonaktifkan.

Autentikasi pengguna, RBAC admin/pengamat, kebijakan CRUD tanaman/monitoring, token perangkat, pembatasan akses API baca, throttling, ledger idempotency, filter tanggal/tanaman/sumber, sanitasi CSV, ekspor PDF, dan audit perintah pompa dipertahankan.

## Payload API

`POST /api/monitorings` tetap memerlukan token perangkat terdaftar pada `X-Device-Token`. `plant_id`, `monitored_at`, dan tiga sensor wajib. Identitas perangkat tetap ditentukan middleware, bukan dipercaya dari payload.

```json
{
  "plant_id": 1,
  "monitored_at": "2026-11-07T10:00:00+07:00",
  "temperature": 27,
  "soil_moisture": 70,
  "water_ph": 6.5,
  "event_id": "observation-20261107-001"
}
```

Header `Idempotency-Key` atau `event_id` tetap didukung dengan aturan konsistensi yang sama. 201 berarti baru, 200 berarti replay, 409 berarti identitas sama dengan isi berbeda/ambigu. API baca tetap membutuhkan sesi pengguna; token perangkat tidak memberikan akses baca internal.

Alias input `ph` diterima. Jika kedua alias dikirim, nilai harus numerik dan sama; konflik ditolak 422. Resource, statistik, dan ringkasan API mempublikasikan `water_ph`/`latest_water_ph`/`average_water_ph`; konsumen API baca perlu menyesuaikan nama lama. Error validasi rentang pH menggunakan key storage `ph`; konflik alias menggunakan `water_ph`.

Field sensor nonaktif pada payload lama diabaikan, termasuk nilai lama yang tidak dapat diparsing. Nilainya tidak ditulis ulang pada observasi baru. Ketiadaan soil_moisture pada payload baru ditolak 422, bukan diganti nol. Data historis tetap dapat ditampilkan dengan nilai kosong. Retry dengan receipt sebelum revisi dapat menghasilkan 409 karena canonical payload lama memuat field yang kini diabaikan; receipt dan observasi lama tidak ditimpa atau diduplikasi. Identitas yang konflik perlu ditinjau, jangan diganti event ID untuk memaksa retry.

## Google Sheets

Format baru berurutan A:I:

```text
device_id,timestamp,plant_code,temperature,soil_moisture,water_ph,pump_status,fuzzy_score,fuzzy_label
```

Kolom A:F adalah identitas/waktu/tanaman dan tiga sensor; G:I opsional. `pump_status` hanya status yang dilaporkan, terpisah dari simulasi. `fuzzy_score` eksternal tetap menerima nol; provenance diberi `external-unverified` dan tidak dianggap model tiga input tervalidasi.

Untuk array numerik, set `GOOGLE_SHEETS_TELEMETRY_LAYOUT=three_input` sebelum memakai susunan baru. Default **legacy** sengaja mempertahankan susunan A:O lembar lama:

```text
device_id,timestamp,plant_code,temperature,humidity,soil_moisture,ph,ec,light_intensity,nutrient_temp,tank_level,pump_status,fan_status,fuzzy_score,fuzzy_label
```

Posisi lama tetap dibaca sehingga nilai tidak bergeser; sensor nonaktif dan fan_status diabaikan. Array asosiatif menerima kontrak baru maupun alias `ph`. Parser tidak menebak layout berdasarkan jumlah kolom karena Sheets dapat memotong sel kosong di ujung baris. Jangan mencampur layout numerik dalam satu lembar/rentang. Untuk perpindahan, selesaikan sinkronisasi format lama, gunakan sheet/rentang baru dengan layout three_input, lalu tetapkan cursor yang sesuai; jangan menggeser baris historis yang belum diproses. Koneksi live masih memerlukan kredensial dan validasi perangkat nyata.

## Artefak dan konfirmasi

- [Dampak database](SENSOR_DATABASE_IMPACT.md)
- [Rancangan fuzzy tiga input](FUZZY_THREE_INPUT_REDESIGN.md)
- [Revisi pompa saja](ACTUATOR_PUMP_ONLY_REVISION.md)
- [Hasil pengujian](REVISION_TEST_RESULTS.md)
- [Daftar lengkap file berubah](revision/CHANGED_FILES.txt)

Tim Elektro/pembimbing perlu mengesahkan makna dan kalibrasi soil_moisture/pH air, threshold alarm, membership, delapan aturan provisional, cakupan yang belum terwakili, klasifikasi skor, dan kebutuhan kontrol pompa ESP32. Tidak ada klaim bahwa kontrol pompa fisik sudah tersedia.
