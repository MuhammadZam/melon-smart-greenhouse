# PANDUAN KEAMANAN & OTENTIKASI PERANGKAT IOT (PHASE 14)

> **Pembaruan normatif Phase 16.2:** mode tanpa token telah dihentikan, termasuk pada local/testing. `ENFORCE_DEVICE_AUTH=false` tidak lagi membuka akses. `POST /api/monitorings` wajib `X-Device-Token` atau Bearer token perangkat aktif. GET API internal memakai sesi web pengguna; token perangkat saja memperoleh 403. Lihat [perbaikan keamanan](PHASE_16_2_SECURITY_FIX.md). Pernyataan transisi tanpa token pada bagian lama di bawah sudah tidak berlaku.
## SISTEM WEB MONITORING TANAMAN MELON

**Versi Dokumen:** 1.0  
**Tanggal:** 16 Oktober 2026  
**Ruang Lingkup:** Endpoint REST API Ingestion (`POST /api/monitorings`)

---

## 1. LATAR BELAKANG KEAMANAN

Pada implementasi prototipe awal (Phase 8–11), endpoint `POST /api/monitorings` menerima data telemetri secara anonim tanpa otentikasi untuk mempermudah pengujian simulator. 

Pada **Phase 14**, untuk memenuhi standar keamanan arsitektur IoT dan persiapan deployment fisik:
1. **Otentikasi Berbasis Token Perangkat:** Setiap node sensor (ESP32 atau simulator) wajib memiliki token otentikasi unik.
2. **Kriptografi One-Way Hashing:** Token asli (*plaintext*) **tidak pernah disimpan di database**. Basis data hanya menyimpan representasi hash kriptografis **SHA-256** (`token_hash`).
3. **Pencabutan & Rotasi Instan:** Administrator dapat mencabut (*revoke*) atau merotasi token perangkat kapan saja melalui antarmuka web tanpa mematikan server.
4. **Strategi Transisi Lingkungan (Backwards Compatibility):**
   - **Lingkungan Produksi (`APP_ENV=production` atau `ENFORCE_DEVICE_AUTH=true`):** Permintaan anonim **ditolak mutlak** dengan HTTP 401 Unauthorized.
   - **Lingkungan Pengembangan Lokal / Testing:** Permintaan anonim diizinkan dengan header respons peringatan `X-Device-Auth-Warning`, sehingga tidak merusak skrip atau pengujian eksisting.

---

## 2. FORMAT HEADER PERMINTAAN HTTP

Klien telemetri (ESP32 fisik, simulator Python, atau gateway) harus menyertakan header berikut pada setiap permintaan `POST /api/monitorings`:

```http
POST /api/monitorings HTTP/1.1
Host: melon-monitor.local
Content-Type: application/json
Accept: application/json
X-Device-Token: dev-melon-simulator-token-2026
X-Device-Id: SIMULATOR-01
```

*Alternatif Standar RFC 6750:*  
Header `Authorization: Bearer dev-melon-simulator-token-2026` juga didukung secara transparan oleh middleware `AuthenticateDeviceToken`.

---

## 3. CONTOH KODE FIRMWARE ESP32 (ARDUINO C++)

Berikut adalah implementasi standar pengiriman telemetri berotentikasi pada mikrokontroler ESP32:

```cpp
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

const char* ssid = "GREENHOUSE_WIFI";
const char* password = "WIFI_PASSWORD_SECURE";
const char* serverUrl = "https://melon-monitor.ac.id/api/monitorings";
const char* deviceToken = "esp32-greenhouse-alpha-token-2026";
const char* deviceId = "ESP32-MELON-01";

void sendTelemetry(float temp, float humidity, float soil, float ph) {
    if (WiFi.status() == WL_CONNECTED) {
        HTTPClient http;
        http.begin(serverUrl);
        
        // Header Keamanan & Konten
        http.addHeader("Content-Type", "application/json");
        http.addHeader("Accept", "application/json");
        http.addHeader("X-Device-Token", deviceToken);
        http.addHeader("X-Device-Id", deviceId);
        
        // Buat Payload JSON
        StaticJsonDocument<256> doc;
        doc["plant_id"] = 1;
        doc["monitored_at"] = "2026-10-16 14:00:00";
        doc["temperature"] = temp;
        doc["humidity"] = humidity;
        doc["soil_moisture"] = soil;
        doc["ph"] = ph;
        doc["plant_height"] = 45.0;
        doc["plant_density"] = 60.0;
        doc["condition"] = "optimal";
        
        String requestBody;
        serializeJson(doc, requestBody);
        
        int httpResponseCode = http.POST(requestBody);
        if (httpResponseCode == 201) {
            Serial.println("Telemetri berhasil terkirim ke Laravel!");
        } else {
            Serial.printf("Gagal mengirim telemetri. HTTP Code: %d\n", httpResponseCode);
        }
        
        http.end();
    }
}
```

---

## 4. TATA CARA PENGGUNAAN PYTHON SIMULATOR

Python simulator pada `iot-simulator/simulator.py` telah diperbarui dengan argumen `--device-token` dan `--device-id`:

```bash
# Pengiriman periodik dengan token terotentikasi:
python iot-simulator/simulator.py --device-id SIMULATOR-01 --device-token dev-melon-simulator-token-2026

# Pengiriman satu kali observasi (--once):
python iot-simulator/simulator.py --once --device-token dev-melon-simulator-token-2026
```

Variabel lingkungan dapat dikonfigurasi pada `iot-simulator/.env`:
```env
API_URL=http://localhost:8000/api/monitorings
PLANT_ID=1
DEVICE_ID=SIMULATOR-01
DEVICE_TOKEN=dev-melon-simulator-token-2026
```

---

## 5. KODE KESALAHAN OTENTIKASI & RESPON SERVER

| Status HTTP | Error Code JSON | Keterangan | Tindakan Klien |
| :---: | :--- | :--- | :--- |
| **401 Unauthorized** | `DEVICE_TOKEN_REQUIRED` | Header `X-Device-Token` tidak disertakan di mode produksi | Konfigurasi token pada firmware ESP32 |
| **401 Unauthorized** | `DEVICE_TOKEN_INVALID` | Token tidak cocok dengan hash terdaftar mana pun | Periksa kembali token atau hubungi Admin |
| **403 Forbidden** | `DEVICE_TOKEN_REVOKED` | Token telah dicabut oleh Administrator sistem | Minta token baru kepada Administrator |
| **429 Too Many Requests** | - | Melampaui batas rate limit (60 request / menit per IP) | Perbesar interval transmisi telemetri |
