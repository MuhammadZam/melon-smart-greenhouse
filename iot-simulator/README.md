# Simulator IoT ? revisi tiga sensor (7 November 2026)

Simulator menghasilkan data sintetis `temperature` (?C), `soil_moisture` (%), dan `water_ph` (tanpa satuan). Nilai acak adalah fixture uji perangkat lunak, bukan threshold agronomis.

```powershell
$env:DEVICE_TOKEN = '<token-perangkat-terdaftar>'
python iot-simulator/simulator.py --once --plant-id 1
```

Token perangkat wajib; sesi pengguna tidak menggantikan token ingestion. `send_reading` membuat `event_id` sekali per payload dan mempertahankannya saat retry. Respons baru 201, replay 200, konflik identitas/payload 409. Timestamp dan tiga sensor wajib dikirim. Morfologi dan observasi manual bersifat opsional.

```json
{
  "plant_id": 1,
  "monitored_at": "2026-11-07T10:00:00+07:00",
  "temperature": 27,
  "soil_moisture": 70,
  "water_ph": 6.5,
  "event_id": "simulator-observation-001"
}
```

Alias input lama `ph` masih diterima; bila dikirim bersama `water_ph`, nilainya harus sama. Kolom sensor nonaktif diabaikan pada ingestion baru. Payload tanpa kelembapan tanah ditolak 422; nilai tanah historis yang kosong tidak dipalsukan menjadi nol.

Format Google Sheets dan batas kompatibilitas dijelaskan di [SENSOR_REQUIREMENT_REVISION](../docs/SENSOR_REQUIREMENT_REVISION.md). PUMP-01 hanya simulasi database; integrasi ESP32 dan kontrol relay fisik belum tersedia.
