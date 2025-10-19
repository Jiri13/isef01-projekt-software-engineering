<?php
// api/quizzesCatalogue.php
// Liefert Fragenkataloge (Module/Quizzes). Plus Anzahl Fragen pro Katalog


header('Content-Type: application/json');
require __DIR__ . '/dbConnection.php';

// GET: optional moduleID to fetch single
$moduleID = isset($_GET['moduleID']) ? (int)$_GET['moduleID'] : 0;

if ($moduleID > 0) {
    $st = $pdo->prepare("SELECT m.moduleID, m.name, COALESCE(qs.cnt,0) AS questionsCount FROM Module m LEFT JOIN (SELECT moduleID, COUNT(*) AS cnt FROM Question GROUP BY moduleID) qs ON qs.moduleID = m.moduleID WHERE m.moduleID = :m LIMIT 1");
    $st->execute([':m'=>$moduleID]);
    $r = $st->fetch();
    if (!$r) { http_response_code(404); echo json_encode(['error'=>'module not found']); exit; }
    echo json_encode(['moduleID'=>(int)$r['moduleID'],'name'=>$r['name'],'questionsCount'=>(int)$r['questionsCount']]);
    exit;
}

$st = $pdo->query("SELECT m.moduleID, m.name, COALESCE(qs.cnt,0) AS questionsCount FROM Module m LEFT JOIN (SELECT moduleID, COUNT(*) AS cnt FROM Question GROUP BY moduleID) qs ON qs.moduleID = m.moduleID ORDER BY m.name ASC");
$rows = $st->fetchAll();
$out = array_map(function($r){ return ['moduleID'=>(int)$r['moduleID'],'name'=>$r['name'],'questionsCount'=>(int)$r['questionsCount']]; }, $rows);

echo json_encode($out);
