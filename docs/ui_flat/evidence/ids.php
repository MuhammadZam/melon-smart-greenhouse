<?php
$pdo=new PDO('sqlite:'.__DIR__.'/browser.sqlite');$pdo->exec('PRAGMA query_only=ON');
$out=[];foreach(['monitorings','plants','fuzzy_inference_results','monitoring_alarms']as$table)$out[$table]=(int)$pdo->query('SELECT id FROM '.$table.' ORDER BY id LIMIT 1')->fetchColumn();
file_put_contents(__DIR__.'/ids.json',json_encode($out));
