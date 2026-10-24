<?php
// Read-only integrity inventory; no row values or authentication secrets are exported.
$root = dirname(__DIR__, 3);
$pdo = new PDO('sqlite:'.$root.'/database/database.sqlite');
$pdo->exec('PRAGMA query_only=ON');
$out = ['database' => 'database/database.sqlite', 'tables' => []];
foreach ($pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN) as $table) {
    if ($table === 'migrations' || $table === 'monitoring_ingestion_keys') continue;
    $hash = hash_init('sha256'); $count = 0;
    foreach ($pdo->query('SELECT * FROM "'.$table.'" ORDER BY rowid', PDO::FETCH_ASSOC) as $row) {
        hash_update($hash, json_encode($row, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION)."\n"); $count++;
    }
    $out['tables'][$table] = ['count' => $count, 'sha256' => hash_final($hash)];
}
$label = ($argv[1] ?? 'before') === 'after' ? 'after' : 'before';
$out['foreign_key_violations'] = $pdo->query('PRAGMA foreign_key_check')->fetchAll(PDO::FETCH_ASSOC);
if ($pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='monitoring_ingestion_keys'")->fetchColumn()) {
    $out['ingestion_ledger'] = [
        'count' => (int) $pdo->query('SELECT COUNT(*) FROM monitoring_ingestion_keys')->fetchColumn(),
        'columns' => $pdo->query("PRAGMA table_info('monitoring_ingestion_keys')")->fetchAll(PDO::FETCH_ASSOC),
    ];
}
file_put_contents(__DIR__.'/history-'.$label.'.json', json_encode($out, JSON_PRETTY_PRINT));
if ($label === 'before') {
    $duplicates = $pdo->query("SELECT source,device_id,plant_id,monitored_at,COUNT(*) AS records FROM monitorings WHERE device_id IS NOT NULL GROUP BY source,device_id,plant_id,monitored_at HAVING COUNT(*)>1")->fetchAll(PDO::FETCH_ASSOC);
    $candidates = $pdo->query("SELECT f.id,f.monitoring_id,f.inference_source,f.fuzzy_score FROM fuzzy_inference_results f JOIN monitorings m ON m.id=f.monitoring_id WHERE m.fuzzy_score=0 AND f.fuzzy_score<>0")->fetchAll(PDO::FETCH_ASSOC);
    $unknown = (int) $pdo->query("SELECT COUNT(*) FROM monitorings WHERE device_id IS NULL AND source IN ('api','simulator','google_sheets')")->fetchColumn();
    $unattributed = $pdo->query("SELECT source,plant_id,monitored_at,COUNT(*) records FROM monitorings WHERE device_id IS NULL GROUP BY source,plant_id,monitored_at HAVING COUNT(*)>1")->fetchAll(PDO::FETCH_ASSOC);
    file_put_contents(__DIR__.'/legacy-findings.json',json_encode(['duplicate_groups'=>$duplicates,'possible_fuzzy_zero_candidates'=>$candidates,'telemetry_rows_without_device'=>$unknown,'unattributed_timestamp_candidates_not_proven_duplicates'=>$unattributed],JSON_PRETTY_PRINT));
}
echo count($out['tables'])." historical tables hashed ($label).\n";
