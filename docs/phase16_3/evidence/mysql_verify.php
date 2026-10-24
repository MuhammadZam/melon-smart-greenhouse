<?php
require __DIR__.'/mysql_bootstrap.php';
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Models\{Plant,Monitoring,User,IotDevice,FuzzyInferenceResult};
$out=['target'=>'isolated Docker mysql:8.0 / phase163_test / port3319','checks'=>[]];
$check=function($name,$pass,$detail=null)use(&$out){$out['checks'][$name]=['pass'=>(bool)$pass,'detail'=>$detail];};
try {
 if (in_array('--reset-isolated-test', $argv, true)) {
  DB::statement('DROP DATABASE `phase163_test`');
  DB::statement('CREATE DATABASE `phase163_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
  DB::purge('mysql');
  foreach(glob(__DIR__.'/mysql.ready*') as $path) if(is_file($path)) unlink($path);
 }
 Artisan::call('migrate',['--force'=>true]);
 file_put_contents(__DIR__.'/mysql-migrations.txt',Artisan::output());
 $check('migrations',DB::table('migrations')->count()===12);
 foreach ([Database\Seeders\MonitoringParameterSeeder::class,Database\Seeders\AlarmParameterKeySeeder::class] as $seeder) Artisan::call('db:seed',['--class'=>$seeder,'--force'=>true]);
 $plant=Plant::factory()->create();
 $history=Monitoring::factory()->create(['plant_id'=>$plant->id,'monitored_at'=>'2026-10-01 00:00:00']);
 $before=$history->fresh()->getRawOriginal();
 [$device,$token]=IotDevice::createWithToken('MYSQL-UI-AUDIT','Disposable compatibility device');
 $payload=['plant_id'=>$plant->id,'monitored_at'=>'2026-10-24 10:00:00','temperature'=>40,'humidity'=>70,'soil_moisture'=>70,'ph'=>6.5,'plant_height'=>40,'plant_density'=>60,'condition'=>'optimal','fuzzy_score'=>0];
 $kernel=$app->make(Illuminate\Contracts\Http\Kernel::class);
 $request=fn($method,$url,$tokenValue=null)=>Illuminate\Http\Request::create($url,$method,[],[],[],array_filter(['CONTENT_TYPE'=>'application/json','HTTP_ACCEPT'=>'application/json','HTTP_X_DEVICE_TOKEN'=>$tokenValue]),json_encode($payload));
 $check('guest_read_401',$kernel->handle($request('GET','/api/monitorings'))->getStatusCode()===401);
 $check('missing_device_token_401',$kernel->handle($request('POST','/api/monitorings'))->getStatusCode()===401);
 $check('invalid_device_token_401',$kernel->handle($request('POST','/api/monitorings','invalid'))->getStatusCode()===401);
 $processes=[];
 for($i=0;$i<10;$i++) {$p=new Symfony\Component\Process\Process([PHP_BINARY,__DIR__.'/mysql_worker.php']);$p->setInput(json_encode(['payload'=>$payload,'token'=>$token]));$p->setTimeout(60);$p->start();$processes[]=$p;}
 $deadline=microtime(true)+25;
 while(count(glob(__DIR__.'/mysql.ready.*'))<10 && microtime(true)<$deadline)usleep(10000);
 $check('ten_workers_at_barrier',count(glob(__DIR__.'/mysql.ready.*'))===10);
 touch(__DIR__.'/mysql.ready');
 $responses=[];foreach($processes as $p){$p->wait();$responses[]=json_decode($p->getOutput(),true)??['error'=>$p->getErrorOutput()];}
 $statuses=array_count_values(array_column($responses,'status'));$ids=array_unique(array_column($responses,'id'));
 $check('concurrent_http_exactly_once',($statuses[201]??0)===1&&($statuses[200]??0)===9&&count($ids)===1,$responses);
 $m=Monitoring::where('device_id',$device->device_id)->first();
 $check('single_side_effects',Monitoring::where('device_id',$device->device_id)->count()===1&&DB::table('monitoring_ingestion_keys')->count()===1&&FuzzyInferenceResult::where('monitoring_id',$m?->id)->count()===1&&DB::table('monitoring_alarms')->where('plant_id',$plant->id)->count()===1);
 $f=FuzzyInferenceResult::where('monitoring_id',$m?->id)->first();
 $check('external_zero_preserved',$f!==null&&(float)$f->fuzzy_score===0.0&&$f->fuzzy_category==='BURUK',['score'=>$f?->fuzzy_score,'label'=>$f?->fuzzy_category]);
 $index=DB::select("SHOW INDEX FROM monitoring_ingestion_keys WHERE Key_name='PRIMARY'");
 $check('identity_primary_unique',count($index)===1&&$index[0]->Column_name==='identity_hash'&&(int)$index[0]->Non_unique===0);
 try {DB::table('monitoring_ingestion_keys')->insert((array)DB::table('monitoring_ingestion_keys')->first());$unique=false;} catch(Illuminate\Database\UniqueConstraintViolationException $e){$unique=true;}
 $check('database_rejects_duplicate_identity',$unique);
 $check('history_unchanged',$before===$history->fresh()->getRawOriginal());
 $admin=User::factory()->create(['role'=>'admin']);$app['auth']->guard('web')->setUser($admin);
 $check('admin_read_200',$kernel->handle($request('GET','/api/monitorings'))->getStatusCode()===200);
 $out['mysql_version']=DB::selectOne('SELECT VERSION() AS version')->version;
}catch(Throwable $e){$out['error']=$e->getMessage();}
$out['pass']=!isset($out['error'])&&!in_array(false,array_column($out['checks'],'pass'),true);
file_put_contents(__DIR__.'/mysql-results.json',json_encode($out,JSON_PRETTY_PRINT));
echo json_encode($out,JSON_PRETTY_PRINT)."\n";
exit($out['pass']?0:1);
