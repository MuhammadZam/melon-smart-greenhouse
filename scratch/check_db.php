<?php

$pdo = new PDO('sqlite:' . __DIR__ . '/../database/database.sqlite');
$stmt = $pdo->query('SELECT rule_version, inference_source, COUNT(*) as cnt FROM fuzzy_inference_results GROUP BY rule_version, inference_source');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Rule Version: {$row['rule_version']} | Source: {$row['inference_source']} | Count: {$row['cnt']}\n";
}

