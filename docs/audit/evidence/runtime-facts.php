<?php
require dirname(__DIR__,3).'/vendor/autoload.php';
$app=require dirname(__DIR__,3).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$pdo=new PDO('sqlite:'.dirname(__DIR__,3).'/database/database.sqlite');
$pdo->exec('PRAGMA query_only=ON');
$out=['date'=>'2026-10-22','php'=>PHP_VERSION,'laravel'=>$app->version(),'db_connection'=>config('database.default'),'app_env'=>$app->environment(),'debug'=>config('app.debug'),'timezone'=>config('app.timezone'),'google_mode'=>config('services.google_sheets.mode'),'google_id_present'=>(bool)config('services.google_sheets.spreadsheet_id'),'google_credentials_present'=>is_file(config('services.google_sheets.credentials_path')),'enforce_device_auth'=>config('services.iot.enforce_device_auth')];
foreach($pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN) as $t) {
    $out['tables'][$t]=(int)$pdo->query('SELECT COUNT(*) FROM "'.$t.'"')->fetchColumn();
    $out['foreign_keys'][$t]=$pdo->query('PRAGMA foreign_key_list("'.$t.'")')->fetchAll(PDO::FETCH_ASSOC);
}
$out['foreign_key_check']=$pdo->query('PRAGMA foreign_key_check')->fetchAll(PDO::FETCH_ASSOC);
$out['monitoring_sources']=$pdo->query('SELECT source,COUNT(*) count FROM monitorings GROUP BY source')->fetchAll(PDO::FETCH_ASSOC);
$out['parameters']=$pdo->query('SELECT parameter_name,parameter_key,unit,minimum_value,maximum_value,critical_min_value,critical_max_value FROM monitoring_parameters')->fetchAll(PDO::FETCH_ASSOC);
$out['last_monitored_at']=$pdo->query('SELECT MAX(monitored_at) FROM monitorings')->fetchColumn();
$out['device_types']=$pdo->query('SELECT type,is_active,COUNT(*) count FROM iot_devices GROUP BY type,is_active')->fetchAll(PDO::FETCH_ASSOC);
file_put_contents(__DIR__.'/runtime.json',json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo json_encode(array_diff_key($out,['foreign_keys'=>1,'parameters'=>1]),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
