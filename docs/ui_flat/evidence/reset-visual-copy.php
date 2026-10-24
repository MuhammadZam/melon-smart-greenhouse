<?php
// Reset only the disposable browser fixture after mutation tests, for comparable screenshots.
$root=dirname(__DIR__,3);$target=__DIR__.'/browser.sqlite';
if(realpath(dirname($target))!==realpath($root.'/docs/ui_flat/evidence'))throw new RuntimeException('Unsafe target');
$credentials=json_decode(file_get_contents(__DIR__.'/credentials.json'),true,512,JSON_THROW_ON_ERROR);
copy($root.'/database/database.sqlite',$target);
putenv('DB_CONNECTION=sqlite');putenv('DB_DATABASE='.$target);
require $root.'/vendor/autoload.php';$app=require $root.'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if(realpath(config('database.connections.sqlite.database'))!==realpath($target))throw new RuntimeException('Unsafe configured database');
App\Models\User::create(['name'=>'UI Audit Admin','email'=>$credentials['email'],'password'=>Illuminate\Support\Facades\Hash::make($credentials['password']),'role'=>'admin']);
echo "Disposable visual copy reset; primary database unchanged.\n";
