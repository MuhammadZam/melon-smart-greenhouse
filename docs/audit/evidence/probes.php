<?php
// All mutations in this script target a new, isolated SQLite :memory: database.
foreach (['APP_ENV'=>'testing','DB_CONNECTION'=>'sqlite','DB_DATABASE'=>':memory:','CACHE_STORE'=>'array','SESSION_DRIVER'=>'array','LOG_CHANNEL'=>'null'] as $k=>$v) {
    putenv("$k=$v"); $_ENV[$k]=$v; $_SERVER[$k]=$v;
}
require dirname(__DIR__,3).'/vendor/autoload.php';
$app=require dirname(__DIR__,3).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if(config('database.connections.sqlite.database')!==':memory:') throw new RuntimeException('Isolation guard failed');
Illuminate\Support\Facades\Artisan::call('migrate',['--database'=>'sqlite']);
$p=App\Models\Plant::factory()->create(['name'=>'AUDIT-PLANT','status'=>'active']);
$m=App\Models\Monitoring::factory()->create(['plant_id'=>$p->id,'temperature'=>27,'humidity'=>70,'soil_moisture'=>70,'ph'=>6.5,'monitored_at'=>now(),'source'=>'web']);
$out=['isolation'=>':memory:'];
$kernel=$app->make(Illuminate\Contracts\Http\Kernel::class);
function api($method,$url,$data=[],$headers=[]) {
    global $kernel;
    $server=['HTTP_ACCEPT'=>'application/json','CONTENT_TYPE'=>'application/json'];
    foreach($headers as $k=>$v) $server['HTTP_'.strtoupper(str_replace('-','_',$k))]=$v;
    $r=$kernel->handle(Illuminate\Http\Request::create($url,$method,[],[],[],$server,json_encode($data)));
    return ['status'=>$r->getStatusCode(),'json'=>json_decode($r->getContent(),true)];
}
foreach(['/api/plants','/api/monitorings','/api/monitorings/statistics','/api/dashboard/summary'] as $url) {
    $r=api('GET',$url);$out['guest'][$url]=['status'=>$r['status'],'has_data'=>isset($r['json']['data'])];
}
$telemetry=app(App\Services\GoogleSheets\GoogleSheetsTelemetryService::class);
$row=['device_id'=>'UNREGISTERED','timestamp'=>now()->format('Y-m-d H:i:s'),'plant_code'=>'UNKNOWN-PLANT','temperature'=>27,'humidity'=>70,'soil_moisture'=>70,'ph'=>6.5,'ec'=>'nonsense'];
$parsed=$telemetry->parseAndValidateRow($row,2);
$out['unknown_plant_device_invalid_optional']=['valid'=>$parsed['valid'],'plant_id'=>$parsed['data']['plant_id']??null,'ec'=>$parsed['data']['ec']??null];
$engine=app(App\Services\FuzzyLogic\MamdaniInferenceService::class);
$out['fuzzy']=$engine->infer(['temperature'=>27,'humidity'=>70,'soil_moisture'=>70,'ph'=>6.5]);
$out['fuzzy_config']=config('fuzzy');
foreach([[27,70,70,6.5],[40,35,30,5],[35,90,90,6.5],[27,70,70,4.5],[27,70,70,8.5],[30,78,80,6.9],[10,0,0,0],[50,100,100,14],[24,65,65,6],[22,58,58,5.8],[32,82,82,7.2],[20,55,70,6.5]] as $vector) {
    $input=array_combine(['temperature','humidity','soil_moisture','ph'],$vector);
    $r=$engine->infer($input);
    $out['fuzzy_scenarios'][]=['input'=>$input,'score'=>$r['score'],'status'=>$r['status'],'category'=>$r['category']];
}
unset($out['fuzzy']['aggregated_curve'],$out['fuzzy']['membership_snapshot'],$out['fuzzy']['rule_activation_snapshot']);
$pipe=app(App\Services\FuzzyLogic\FuzzyPipelineService::class);
Illuminate\Support\Facades\Cache::put('fuzzy_inference_runtime_enabled',false);
$r=$pipe->process($m,'api_ingest');
$out['fuzzy_disabled_api']=['created'=>$r!==null,'status'=>$r?->inference_status];
$r=$pipe->process($m,'google_sheets_payload',['score'=>0,'condition_category'=>'BURUK']);
$out['external_score_zero']=['score'=>$r?->fuzzy_score,'category'=>$r?->fuzzy_category];
Illuminate\Support\Facades\Cache::put('fuzzy_inference_runtime_enabled',true);
$payload=['plant_id'=>$p->id,'monitored_at'=>now()->addMinute()->format('Y-m-d H:i:s'),'temperature'=>27,'humidity'=>70,'soil_moisture'=>70,'ph'=>6.5,'plant_height'=>30,'plant_density'=>60,'condition'=>'optimal','device_id'=>'AUDIT-API'];
$first=api('POST','/api/monitorings',$payload);$second=api('POST','/api/monitorings',$payload);
$out['duplicate_api']=['first'=>$first['status'],'second'=>$second['status'],'count'=>App\Models\Monitoring::where('device_id','AUDIT-API')->count()];
$null=$payload;$null['ph']=null;$null['monitored_at']=now()->addMinutes(2)->format('Y-m-d H:i:s');
$r=api('POST','/api/monitorings',$null);$out['nullable_ph']=['status'=>$r['status'],'errors'=>array_keys($r['json']['errors']??[])];
$blueprint=['plant_id'=>$p->id,'monitored_at'=>now()->format('Y-m-d H:i:s'),'temperature'=>27.8,'humidity'=>65.2,'soil_moisture'=>72,'ph'=>6.35,'ec'=>2.45,'light_intensity'=>25400,'nutrient_temperature'=>23.2,'water_tank_level'=>85,'condition'=>'optimal'];
$r=api('POST','/api/monitorings',$blueprint);$out['blueprint_payload']=['status'=>$r['status'],'errors'=>array_keys($r['json']['errors']??[])];
$report=app(App\Services\Reports\MonitoringReportService::class);
$out['report_source_filter']=['monitorings'=>$report->buildQuery(['source'=>'not_present'])->count(),'fuzzy_total'=>$report->calculateFuzzySummary(['source'=>'not_present'])['total']];
Illuminate\Support\Facades\Auth::setUser(App\Models\User::factory()->create(['role'=>'admin']));
for($i=0;$i<40;$i++) App\Models\Monitoring::factory()->create(['plant_id'=>$p->id,'monitored_at'=>now()->subHours(40-$i),'temperature'=>20+$i/10]);
$view=app(App\Http\Controllers\DashboardController::class)->index(Illuminate\Http\Request::create('/dashboard','GET'));
$data=$view->getData();
$out['dashboard_trend']=['total'=>App\Models\Monitoring::count(),'chart_points'=>count($data['chartLabels']),'last_chart_label'=>end($data['chartLabels']),'latest_database_label'=>App\Models\Monitoring::latest('monitored_at')->first()->monitored_at->format('d M H:i')];
(new Database\Seeders\IotDeviceSeeder())->run();
$seed=file_get_contents(dirname(__DIR__,3).'/database/seeders/IotDeviceSeeder.php');
preg_match("/'token'\\s*=>\\s*'([^']+)'/",$seed,$matches);
$app->detectEnvironment(fn()=>'production');
$payload['monitored_at']=now()->addMinutes(5)->format('Y-m-d H:i:s');
$r=api('POST','/api/monitorings',$payload,['X-Device-Token'=>$matches[1]]);
$out['development_seed_token_in_production']=['status'=>$r['status'],'environment'=>$app->environment()];
$out['schema']=[];
foreach(['monitorings','monitoring_alarms','fuzzy_inference_results','iot_devices'] as $table) {
    $out['schema'][$table]=Illuminate\Support\Facades\DB::select("PRAGMA table_info('$table')");
    $out['indexes'][$table]=Illuminate\Support\Facades\DB::select("PRAGMA index_list('$table')");
}
// Independent oracle: geometric trapezoid membership + discrete centroid, no engine code reused.
$num=0;$den=0;
for($z=0;$z<=100;$z++) {$mu=$z<65?0:($z<80?($z-65)/15:1);$num+=$z*$mu;$den+=$mu;}
$out['independent_oracle']=['numerator'=>$num,'denominator'=>$den,'score'=>$num/$den,'rounded'=>round($num/$den,2),'continuous_numerator'=>2362.5,'continuous_denominator'=>27.5,'continuous_centroid'=>2362.5/27.5];
file_put_contents(__DIR__.'/probes.json',json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo json_encode(array_diff_key($out,['schema'=>1,'indexes'=>1,'fuzzy'=>1]),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
