<?php
require __DIR__.'/revision_mysql_bootstrap.php';
foreach ([Database\Seeders\UserSeeder::class, Database\Seeders\PlantSeeder::class, Database\Seeders\ActuatorSeeder::class, Database\Seeders\MonitoringSeeder::class] as $class) {
    Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => $class, '--force' => true]);
}
$m = App\Models\Monitoring::create(['plant_id' => App\Models\Plant::first()->id, 'temperature'=>27, 'soil_moisture'=>70, 'ph'=>6.5, 'humidity'=>91, 'ec'=>2.3, 'plant_condition'=>'optimal', 'plant_color'=>'Hijau', 'monitored_at'=>now(), 'source'=>'web']);
app(App\Services\FuzzyLogic\FuzzyPipelineService::class)->process($m, 'web_form');
$matrix=[];
foreach ([18,27,36] as $t) foreach ([30,70,90] as $s) foreach ([4.5,6.5,8.5] as $p) {
    $r=app(App\Services\FuzzyLogic\MamdaniInferenceService::class)->infer(['temperature'=>$t,'soil_moisture'=>$s,'water_ph'=>$p]);
    $matrix[]=['temperature'=>$t,'soil_moisture'=>$s,'water_ph'=>$p,'status'=>$r['status'],'score'=>$r['score'],'rules'=>array_column($r['activated_rules'],'id')];
}
file_put_contents(base_path('docs/revision/evidence/fuzzy-27-combinations.json'),json_encode($matrix,JSON_PRETTY_PRINT));
echo json_encode(['monitoring_id'=>$m->id,'coverage'=>array_count_values(array_column($matrix,'status'))]);
