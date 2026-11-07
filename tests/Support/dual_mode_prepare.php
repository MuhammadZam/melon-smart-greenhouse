<?php

require __DIR__.'/dual_mode_mysql_bootstrap.php';

use Illuminate\Support\Facades\Schema;

if (!Schema::hasTable('users')) {
    foreach (glob(database_path('migrations/*.php')) as $file) (require $file)->up();
} elseif (!Schema::hasColumn('actuators', 'control_mode')) {
    (require database_path('migrations/2026_11_07_000002_add_dual_mode_pump_control.php'))->up();
}
echo json_encode(['database' => config('database.connections.mysql.database'), 'ready' => Schema::hasColumn('actuators', 'control_mode'), 'legacy_monitorings_preserved' => App\Models\Monitoring::count()]);
