<?php
// Disposable browser database. Never boot the application against the primary database.
$root = dirname(__DIR__, 3);
$target = __DIR__.'/browser.sqlite';
if (file_exists($target)) throw new RuntimeException('Disposable database already exists.');
copy($root.'/database/database.sqlite', $target);
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE='.$target);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (realpath(config('database.connections.sqlite.database')) !== realpath($target)) throw new RuntimeException('Unsafe database');
$password = bin2hex(random_bytes(20));
App\Models\User::create(['name'=>'UI Audit Admin','email'=>'ui-audit@example.test','password'=>Illuminate\Support\Facades\Hash::make($password),'role'=>'admin']);
file_put_contents(__DIR__.'/credentials.json', json_encode(['email'=>'ui-audit@example.test','password'=>$password]));
echo "Isolated browser database ready.\n";
