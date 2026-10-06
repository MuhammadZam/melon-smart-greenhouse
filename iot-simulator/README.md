# IoT Sensor Data Simulator (Prototype)

Direktori ini berisi simulator data sensor eksternal untuk **Sistem Web Monitoring Tanaman Melon**.

> **Pemberitahuan Penting:**
> Sistem saat ini belum terhubung dengan perangkat keras fisik (ESP32 / sensor fisik). Simulator ini digunakan sebagai **IoT Data Simulation / Sensor Integration Prototype** untuk membuktikan bahwa arsitektur REST API sistem siap menerima data telemetri berkala dari perangkat luar melalui protokol HTTP.

---

## 1. Arsitektur Komunikasi

```text
Python Sensor Simulator (External Client)
           │
           │ HTTP POST (JSON Payload)
           ▼
Laravel REST API (/api/monitorings)
           │
           ▼
StoreMonitoringApiRequest (Validation)
           │
           ▼
Eloquent Model & Database (MySQL / SQLite)
           │
           ▼
Monitoring Dashboard & Chart.js Visualization
```

---

## 2. Persyaratan & Instalasi

- Python 3.9+
- Paket pendukung (opsional):
  ```bash
  pip install -r requirements.txt
  ```
  *(Simulator telah dirancang dengan library bawaan standar Python `urllib`, sehingga dapat berjalan langsung tanpa dependensi eksternal).*

---

## 3. Konfigurasi

Salin berkas konfigurasi `.env.example` ke `.env`:

```bash
cp .env.example .env
```

Isi berkas `.env`:
```ini
API_URL=http://127.0.0.1:8000/api/monitorings
PLANT_ID=1
SIMULATION_INTERVAL=10
```

---

## 4. Cara Menjalankan

### A. Pengiriman Satu Kali (One-Time Mode)
Sangat berguna untuk pengujian otomatis, validasi CI/CD, atau verifikasi cepat:

```bash
python simulator.py --once
```

### B. Pengiriman Berkala (Periodic Mode)
Mengirimkan data sensor tiruan secara berulang sesuai interval yang ditentukan (misalnya setiap 10 detik):

```bash
python simulator.py --interval 10
```

### C. Menentukan Tanaman Tertentu
Mengirimkan data untuk spesimen tanaman dengan ID 2:

```bash
python simulator.py --plant-id 2 --once
```

### D. Mengubah Alamat Target API
```bash
python simulator.py --api-url http://localhost:8000/api/monitorings --once
```

---

## 5. Format Payload Data

Simulator mengirimkan data dengan format JSON berikut:

```json
{
  "plant_id": 1,
  "monitored_at": "2026-10-07 08:00:00",
  "temperature": 28.4,
  "ph": 6.35,
  "humidity": 74.5,
  "plant_height": 82.0,
  "canopy_density": 68.0,
  "condition": "optimal",
  "leaf_color": "Hijau Segar",
  "notes": "Simulated sensor reading via IoT prototype"
}
```

---

## 6. Contoh Output Terminal

```text
========================================
 MELON MONITOR - SENSOR SIMULATOR
 (IoT Integration Prototype)
========================================
API Target : http://127.0.0.1:8000/api/monitorings
Plant ID   : 1
Mode       : One-time transmission (--once)
========================================

[08:00:01] Sending simulated reading #1...
[08:00:01] SUCCESS (HTTP 201)
  Temperature : 28.4 °C
  pH          : 6.35
  Humidity    : 74.5 % RH
  Height      : 82.0 cm
  Canopy      : 68.0 %
  Condition   : optimal
----------------------------------------
Simulation completed (--once mode). Exiting.
```
