<?php
$root = dirname(__DIR__, 3);
$credentials = json_decode(file_get_contents(__DIR__.'/mysql-credentials.json'), true, 512, JSON_THROW_ON_ERROR);
if ($credentials['database'] !== 'phase163_test' || (int)$credentials['port'] !== 3319) throw new RuntimeException('Unsafe MySQL target');
foreach (['APP_ENV'=>'testing','DB_CONNECTION'=>'mysql','DB_HOST'=>'127.0.0.1','DB_PORT'=>'3319','DB_DATABASE'=>'phase163_test','DB_USERNAME'=>'root','DB_PASSWORD'=>$credentials['password'],'DB_URL'=>'','CACHE_STORE'=>'array','SESSION_DRIVER'=>'array'] as $k=>$v) {putenv("$k=$v");$_ENV[$k]=$v;$_SERVER[$k]=$v;}
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.connections.mysql.database') !== 'phase163_test') throw new RuntimeException('Unsafe config');
