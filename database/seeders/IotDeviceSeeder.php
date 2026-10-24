<?php

namespace Database\Seeders;

use App\Models\IotDevice;
use Illuminate\Database\Seeder;

class IotDeviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Phase 14: IoT Device Registry & Authentication Tokens
     */
    public function run(): void
    {
        $devices = [
            [
                'device_id'    => 'SIMULATOR-01',
                'name'         => 'Python IoT Simulator Node 01',
                'type'         => 'python_simulator',
                'token'        => 'dev-melon-simulator-token-2026',
            ],
            [
                'device_id'    => 'ESP32-MELON-01',
                'name'         => 'Greenhouse Primary ESP32 Node',
                'type'         => 'esp32_hardware',
                'token'        => 'esp32-greenhouse-alpha-token-2026',
            ],
            [
                'device_id'    => 'GSHEETS-GW-01',
                'name'         => 'Google Sheets Telemetry Ingestion Gateway',
                'type'         => 'google_sheets_gateway',
                'token'        => 'gsheets-gateway-token-2026',
            ],
        ];

        foreach ($devices as $dev) {
            $token = $dev['token'];
            IotDevice::firstOrCreate(
                ['device_id' => $dev['device_id']],
                [
                    'name'         => $dev['name'],
                    'type'         => $dev['type'],
                    'token_hash'   => hash('sha256', $token),
                    'token_prefix' => substr($token, 0, 8),
                    'is_active'    => true,
                ]
            );
        }
    }
}
