<?php
$file=__DIR__.'/sample-report.csv';$handle=fopen($file,'r');
$bom=fread($handle,3);$headers=fgetcsv($handle);$n=0;$bad=0;$unprotected=0;$protected=0;$stats=[];
$keys=[8=>'temperature',9=>'humidity',10=>'soil_moisture',11=>'ph',12=>'ec',13=>'light_intensity',14=>'nutrient_temperature',15=>'tank_level'];
while(($row=fgetcsv($handle))!==false) {
    if(count($row)===1&&$row[0]===null)continue;$n++;if(count($row)!==23)$bad++;
    foreach($keys as $i=>$key){$v=$row[$i]??null;if(is_numeric($v)){$stats[$key][]=(float)$v;}}
    foreach([3,4,5,6,7,18,19,21,22] as $i){$value=$row[$i]??'';if($value!==''&&in_array($value[0],['=','+','-','@',"\t","\r"]))$unprotected++;if(preg_match('/^\'[=+@-]/',$value))$protected++;}
}
$pdo=new PDO('sqlite:'.dirname(__DIR__,3).'/database/database.sqlite');$pdo->exec('PRAGMA query_only=ON');
$out=['bom'=>$bom==="\xEF\xBB\xBF",'header_count'=>count($headers),'rows'=>$n,'database_rows'=>(int)$pdo->query('SELECT COUNT(*) FROM monitorings')->fetchColumn(),'malformed_rows'=>$bad,'unprotected_formula_prefixes'=>$unprotected,'protected_formula_cells'=>$protected];
foreach($stats as $key=>$values){$db=$pdo->query("SELECT COUNT($key) n,MIN($key) min,MAX($key) max,AVG($key) avg FROM monitorings")->fetch(PDO::FETCH_ASSOC);$out['statistics'][$key]=['csv_count'=>count($values),'db_count'=>(int)$db['n'],'csv_min'=>min($values),'csv_max'=>max($values),'csv_avg'=>array_sum($values)/count($values),'db_avg'=>(float)$db['avg'],'match'=>count($values)==$db['n']&&abs(array_sum($values)/count($values)-$db['avg'])<0.001];}
file_put_contents(__DIR__.'/csv-check.json',json_encode($out,JSON_PRETTY_PRINT));echo json_encode($out,JSON_PRETTY_PRINT);
