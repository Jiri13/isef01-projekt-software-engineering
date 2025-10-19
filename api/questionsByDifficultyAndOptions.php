<?php
// api/questionsByDifficultyAndOptions.php
// Liefert Fragen nach Schwierigkeitsgrad inkl. Optionen. Erweiterung: count per difficulty


header('Content-Type: application/json');
require __DIR__ . '/dbConnection.php';

// GET params: moduleID (required), difficulty (optional: easy|medium|hard), limit
$moduleID = isset($_GET['moduleID']) ? (int)$_GET['moduleID'] : 0;
$diff = isset($_GET['difficulty']) ? strtolower(trim((string)$_GET['difficulty'])) : null;
$limit = isset($_GET['limit']) ? max(1,(int)$_GET['limit']) : 50;

if ($moduleID <= 0) { http_response_code(400); echo json_encode(['error'=>'moduleID required']); exit; }

$where = 'q.moduleID = :moduleID';
$params = [':moduleID'=>$moduleID];
if ($diff && in_array($diff,['easy','medium','hard'],true)) { $where .= ' AND LOWER(q.difficulty) = :diff'; $params[':diff']=$diff; }

$sql = "SELECT q.questionID, q.question_text, LOWER(q.difficulty) AS difficulty
        FROM Question q
        WHERE $where
        ORDER BY q.created_at DESC
        LIMIT :lim";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':moduleID',$moduleID,PDO::PARAM_INT);
if (isset($params[':diff'])) $stmt->bindValue(':diff',$params[':diff']);
$stmt->bindValue(':lim',(int)$limit,PDO::PARAM_INT);
$stmt->execute();
$questions = $stmt->fetchAll();

$qIds = array_map(function($r){return (int)$r['questionID'];}, $questions);
$opts = [];
if (!empty($qIds)) {
    $ph = implode(',', array_fill(0, count($qIds), '?'));
    $sqlO = "SELECT optionID, questionID, option_text FROM AnswerOption WHERE questionID IN ($ph) ORDER BY optionID ASC";
    $stO = $pdo->prepare($sqlO);
    $stO->execute($qIds);
    foreach ($stO->fetchAll() as $o) {
        $qid = (int)$o['questionID'];
        if (!isset($opts[$qid])) $opts[$qid]=[];
        $opts[$qid][] = ['optionID'=>(int)$o['optionID'],'text'=>$o['option_text']];
    }
}

$out = [];
foreach ($questions as $q) {
    $qid = (int)$q['questionID'];
    $out[] = ['questionID'=>$qid,'text'=>$q['question_text'],'difficulty'=>$q['difficulty'],'options'=>$opts[$qid] ?? []];
}

echo json_encode($out);
