<?php
require __DIR__.'/mysql_bootstrap.php';
$input = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
$barrier = __DIR__.'/mysql.ready';
touch($barrier.'.'.getmypid());
$deadline = microtime(true)+20;
while (!file_exists($barrier)) {if(microtime(true)>$deadline) throw new RuntimeException('Barrier timeout');usleep(10000);}
$request=Illuminate\Http\Request::create('/api/monitorings','POST',[],[],[],['CONTENT_TYPE'=>'application/json','HTTP_ACCEPT'=>'application/json','HTTP_X_DEVICE_TOKEN'=>$input['token'],'HTTP_IDEMPOTENCY_KEY'=>'mysql-parallel-event'],json_encode($input['payload']));
$response=$app->make(Illuminate\Contracts\Http\Kernel::class)->handle($request);
$body=json_decode($response->getContent(),true);
echo json_encode(['status'=>$response->getStatusCode(),'id'=>$body['data']['id']??null]);
