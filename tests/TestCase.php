<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!app()->environment('testing') || config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Tests require isolated SQLite :memory:. Refusing to modify another database.');
        }
        // A new application and in-memory connection per test; no destructive refresh.
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--database' => 'sqlite', '--force' => true]);
        $this->seed([
            \Database\Seeders\UserSeeder::class,
            \Database\Seeders\PlantSeeder::class,
            \Database\Seeders\MonitoringParameterSeeder::class,
            \Database\Seeders\AlarmParameterKeySeeder::class,
            \Database\Seeders\MonitoringSeeder::class,
            \Database\Seeders\ActuatorSeeder::class,
        ]);
    }

    protected function authenticateDevice(string $type = 'esp32_hardware'): \App\Models\IotDevice
    {
        [$device, $token] = \App\Models\IotDevice::createWithToken('TEST-'.\Illuminate\Support\Str::random(20), 'Isolated test device', $type);
        $this->withHeader('X-Device-Token', $token);

        return $device;
    }
}
