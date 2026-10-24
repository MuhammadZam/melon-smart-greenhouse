#!/usr/bin/env python3
"""
=============================================================================
SISTEM MONITORING TANAMAN MELON — IoT SENSOR DATA SIMULATOR (PROTOTYPE)
=============================================================================
Simulator data sensor berbasis HTTP client untuk memverifikasi dan menguji
endpoint ingestion data monitoring (POST /api/monitorings) tanpa perangkat fisik.
"""

import argparse
import datetime
import json
import os
import random
import sys
import time
import uuid
import urllib.request
import urllib.error

# Coba memuat python-dotenv jika tersedia
try:
    from dotenv import load_dotenv
    load_dotenv()
except ImportError:
    # Parser manual sederhana jika python-dotenv belum terpasang
    env_file = os.path.join(os.path.dirname(__file__), '.env')
    if os.path.exists(env_file):
        with open(env_file, 'r', encoding='utf-8') as f:
            for line in f:
                line = line.strip()
                if line and not line.startswith('#') and '=' in line:
                    k, v = line.split('=', 1)
                    os.environ.setdefault(k.strip(), v.strip())


def generate_sensor_payload(plant_id: int) -> dict:
    """
    Menghasilkan data pembacaan sensor tiruan (DATA SIMULASI) dalam rentang realistis melon.
    
    PENTING / DISCLAIMER:
    Data ini adalah DATA SIMULASI perangkat lunak untuk keperluan pengujian dan verifikasi
    prototipe integrasi IoT/API, BUKAN hasil pembacaan sensor fisik aktual lapangan.
    Validasi batas toleransi dan kalibrasi sensor fisik sebenarnya dikembangkan bersama Tim Elektro.
    """
    now_str = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")

    # 1. Parameter Mikroklimat Greenhouse
    temperature = round(random.uniform(25.0, 31.0), 1)          # °C
    humidity = round(random.uniform(62.0, 78.0), 1)             # % RH
    light_intensity = round(random.uniform(18000.0, 42000.0), 1) # lux (simulasi cahaya siang)

    # 2. Parameter Fertigasi & Larutan Nutrisi
    ph = round(random.uniform(6.0, 6.8), 2)                     # pH
    ec = round(random.uniform(1.9, 2.6), 2)                     # mS/cm
    nutrient_temperature = round(random.uniform(21.5, 25.0), 1) # °C
    tank_level = round(random.uniform(70.0, 92.0), 1)           # %

    # 3. Parameter Media Tanam & Morfologi
    soil_moisture = round(random.uniform(65.0, 78.0), 1)        # %
    plant_height = round(random.uniform(45.0, 110.0), 1)        # cm
    canopy_density = round(random.uniform(55.0, 85.0), 1)       # %

    condition = "optimal"
    leaf_color = "Hijau Segar"

    # Evaluasi sederhana status kondisi simulasi
    if temperature > 31.5 or ph > 7.1 or ph < 5.8 or ec > 2.8 or ec < 1.6 or soil_moisture < 55.0:
        condition = "warning"
        leaf_color = "Hijau Kekuningan"

    return {
        "plant_id": plant_id,
        "monitored_at": now_str,
        # 8 Parameter Sensor IoT:
        "temperature": temperature,
        "humidity": humidity,
        "soil_moisture": soil_moisture,
        "ph": ph,
        "ec": ec,
        "light_intensity": light_intensity,
        "nutrient_temperature": nutrient_temperature,
        "tank_level": tank_level,
        # Parameter Morfologi & Observasi:
        "plant_height": plant_height,
        "canopy_density": canopy_density,
        "condition": condition,
        "leaf_color": leaf_color,
        "notes": f"[DATA SIMULASI PROTOKOL UJI IOT] Pembacaan parameter sintetis untuk verifikasi sistem pada {now_str}",
    }


def send_reading(api_url: str, payload: dict, token: str = None, device_id: str = None) -> tuple[bool, int, str]:
    """
    Mengirimkan payload data sensor ke Laravel REST API menggunakan HTTP POST.
    Mendukung 'requests' jika ada, atau fallback native ke 'urllib.request'.
    Mendukung header autentikasi X-Device-Token (Phase 14 Device Security).
    """
    if device_id and "device_id" not in payload:
        payload["device_id"] = device_id
    # Retrying this payload reuses the event ID; a fresh measurement gets a fresh ID.
    payload.setdefault("event_id", uuid.uuid4().hex)

    data_bytes = json.dumps(payload).encode("utf-8")
    headers = {
        "Content-Type": "application/json",
        "Accept": "application/json",
        "User-Agent": "MelonMonitor-Simulator/2.0-Phase14",
    }

    if token:
        headers["X-Device-Token"] = token
    if device_id:
        headers["X-Device-Id"] = device_id

    req = urllib.request.Request(api_url, data=data_bytes, headers=headers, method="POST")

    try:
        with urllib.request.urlopen(req, timeout=10) as response:
            status_code = response.getcode()
            response_body = response.read().decode("utf-8")
            return True, status_code, response_body
    except urllib.error.HTTPError as e:
        status_code = e.code
        err_body = e.read().decode("utf-8")
        return False, status_code, err_body
    except urllib.error.URLError as e:
        return False, 0, str(e.reason)
    except Exception as e:
        return False, 0, str(e)


def main():
    parser = argparse.ArgumentParser(
        description="Melon Monitoring - Simulated Sensor Client (IoT Security Phase 14)"
    )
    parser.add_argument(
        "--api-url",
        default=os.getenv("API_URL", "http://127.0.0.1:8000/api/monitorings"),
        help="Target endpoint ingestion API (default: http://127.0.0.1:8000/api/monitorings)",
    )
    parser.add_argument(
        "--plant-id",
        type=int,
        default=int(os.getenv("PLANT_ID", "1")),
        help="ID tanaman melon yang dipantau (default: 1)",
    )
    parser.add_argument(
        "--device-id",
        default=os.getenv("DEVICE_ID", "SIMULATOR-01"),
        help="Identifier perangkat IoT (default: SIMULATOR-01)",
    )
    parser.add_argument(
        "--device-token",
        default=os.getenv("DEVICE_TOKEN", ""),
        help="Token perangkat X-Device-Token (wajib pada semua environment)",
    )
    parser.add_argument(
        "--interval",
        type=int,
        default=int(os.getenv("SIMULATION_INTERVAL", "10")),
        help="Interval pengiriman berkala dalam detik (default: 10)",
    )
    parser.add_argument(
        "--once",
        action="store_true",
        help="Kirimkan satu data observasi simulasi kemudian keluar",
    )

    args = parser.parse_args()
    if not args.device_token.strip():
        parser.error("DEVICE_TOKEN atau --device-token wajib diisi dengan token perangkat terdaftar.")

    print("================================================================")
    print(" MELON MONITOR - SENSOR SIMULATOR (PHASE 11 IoT EXPANSION)")
    print(" PERINGATAN: Seluruh data yang dikirim adalah DATA SIMULASI,")
    print(" bukan pembacaan sensor fisik aktual lapangan ESP32.")
    print("================================================================")
    print(f"API Target : {args.api_url}")
    print(f"Plant ID   : {args.plant_id}")
    print(f"Mode       : {'One-time transmission (--once)' if args.once else f'Periodic every {args.interval}s'}")
    print("================================================================\n")

    iteration = 0

    try:
        while True:
            iteration += 1
            payload = generate_sensor_payload(args.plant_id)
            timestamp = datetime.datetime.now().strftime("%H:%M:%S")

            print(f"[{timestamp}] Transmitting simulated reading #{iteration} (Device: {args.device_id})...")

            success, status_code, response_body = send_reading(args.api_url, payload, token=args.device_token, device_id=args.device_id)

            if success:
                print(f"[{timestamp}] SUCCESS (HTTP {status_code})")
                print("  [Mikroklimat Greenhouse]")
                print(f"    - Suhu Udara         : {payload['temperature']} °C")
                print(f"    - Kelembapan Udara   : {payload['humidity']} % RH")
                print(f"    - Intensitas Cahaya  : {payload['light_intensity']} lux")
                print("  [Larutan Nutrisi & Tandon]")
                print(f"    - pH Larutan         : {payload['ph']}")
                print(f"    - Konduktivitas (EC) : {payload['ec']} mS/cm")
                print(f"    - Suhu Nutrisi       : {payload['nutrient_temperature']} °C")
                print(f"    - Level Tandon       : {payload['tank_level']} %")
                print("  [Media Tanam & Morfologi]")
                print(f"    - Kelembapan Tanah   : {payload['soil_moisture']} %")
                print(f"    - Tinggi Tajuk       : {payload['plant_height']} cm")
                print(f"    - Kerapatan Kanopi   : {payload['canopy_density']} %")
                print(f"    - Kondisi / Warna    : {payload['condition']} / {payload['leaf_color']}")
            else:
                if status_code > 0:
                    print(f"[{timestamp}] ERROR: Server returned HTTP {status_code}")
                    try:
                        err_json = json.loads(response_body)
                        print(f"  Message : {err_json.get('message', 'Validation error')}")
                        if "errors" in err_json:
                            print(f"  Errors  : {err_json.get('errors')}")
                    except Exception:
                        print(f"  Detail  : {response_body[:200]}")
                else:
                    print(f"[{timestamp}] ERROR: Cannot connect to Laravel API at {args.api_url}")
                    print(f"  Detail : {response_body}")
                    print("  Pastikan server Laravel aktif (misal: 'php artisan serve')")

            print("-" * 64)

            if args.once:
                print("Simulation completed (--once mode). Exiting.")
                sys.exit(0 if success else 1)
                sys.exit(0 if success else 1)

            time.sleep(args.interval)

    except KeyboardInterrupt:
        print("\nSimulation stopped by user (Ctrl+C). Exiting.")
        sys.exit(0)


if __name__ == "__main__":
    main()
