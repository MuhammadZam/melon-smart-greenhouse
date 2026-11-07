<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $mysql = getenv('COMPRO_REVISION_MYSQL') === '1';
        $safe = $mysql
            ? config('database.default') === 'mysql' && config('database.connections.mysql.database') === 'compro_revision_test' && (string) config('database.connections.mysql.port') === '3327'
            : config('database.default') === 'sqlite' && config('database.connections.sqlite.database') === ':memory:';
        if (!app()->environment('testing') || !$safe) {
            throw new \RuntimeException('Tests require isolated revision MySQL or SQLite :memory:.');
        }
        if (!\Illuminate\Support\Facades\Schema::hasTable('users')) {
            foreach (glob(database_path('migrations/*.php')) as $file) {
                (require $file)->up();
            }
        }
        \Illuminate\Support\Facades\DB::beginTransaction();
        $this->beforeApplicationDestroyed(function () {
            \Illuminate\Support\Facades\DB::rollBack();
        });
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
