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
    Menghasilkan data pembacaan sensor tiruan dalam rentang realistis melon.
    CATATAN: Rentang ini untuk simulasi pengujian prototype, bukan standar biologis mutlak.
    """
    now_str = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")

    # Variasi acak parameter lingkungan greenhouse melon
    temperature = round(random.uniform(26.5, 30.5), 1)
    ph = round(random.uniform(6.1, 6.9), 2)
    humidity = round(random.uniform(65.0, 80.0), 1)
    plant_height = round(random.uniform(45.0, 110.0), 1)
    canopy_density = round(random.uniform(55.0, 85.0), 1)

    condition = "optimal"
    leaf_color = "Hijau Segar"

    # Evaluasi ambang batas simulasi
    if temperature > 30.0 or ph > 7.1 or ph < 6.0 or humidity > 82.0:
        condition = "warning"
        leaf_color = "Hijau Kekuningan"

    return {
        "plant_id": plant_id,
        "monitored_at": now_str,
        "temperature": temperature,
        "ph": ph,
        "humidity": humidity,
        "plant_height": plant_height,
        "canopy_density": canopy_density,
        "condition": condition,
        "leaf_color": leaf_color,
        "notes": f"Simulated sensor reading via IoT prototype at {now_str}",
    }


def send_reading(api_url: str, payload: dict) -> tuple[bool, int, str]:
    """
    Mengirimkan payload data sensor ke Laravel REST API menggunakan HTTP POST.
    Mendukung 'requests' jika ada, atau fallback native ke 'urllib.request'.
    """
    data_bytes = json.dumps(payload).encode("utf-8")
    headers = {
        "Content-Type": "application/json",
        "Accept": "application/json",
        "User-Agent": "MelonMonitor-Simulator/1.0",
    }

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
        description="Melon Monitoring - Simulated Sensor Client (IoT Prototype)"
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

    print("========================================")
    print(" MELON MONITOR - SENSOR SIMULATOR")
    print(" (IoT Integration Prototype)")
    print("========================================")
    print(f"API Target : {args.api_url}")
    print(f"Plant ID   : {args.plant_id}")
    print(f"Mode       : {'One-time transmission (--once)' if args.once else f'Periodic every {args.interval}s'}")
    print("========================================\n")

    iteration = 0

    try:
        while True:
            iteration += 1
            payload = generate_sensor_payload(args.plant_id)
            timestamp = datetime.datetime.now().strftime("%H:%M:%S")

            print(f"[{timestamp}] Sending simulated reading #{iteration}...")

            success, status_code, response_body = send_reading(args.api_url, payload)

            if success:
                print(f"[{timestamp}] SUCCESS (HTTP {status_code})")
                print(f"  Temperature : {payload['temperature']} °C")
                print(f"  pH          : {payload['ph']}")
                print(f"  Humidity    : {payload['humidity']} % RH")
                print(f"  Height      : {payload['plant_height']} cm")
                print(f"  Canopy      : {payload['canopy_density']} %")
                print(f"  Condition   : {payload['condition']}")
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

            print("-" * 40)

            if args.once:
                print("Simulation completed (--once mode). Exiting.")
                sys.exit(0 if success else 1)

            time.sleep(args.interval)

    except KeyboardInterrupt:
        print("\nSimulation stopped by user (Ctrl+C). Exiting.")
        sys.exit(0)


if __name__ == "__main__":
    main()
