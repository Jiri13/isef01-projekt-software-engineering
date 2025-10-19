<?php
// api/filterQuestions.php
// Kombinierbarer Filter-Endpunkt: quality(minRating), difficulty, createdAfter, createdBefore, creatorID, searchText


header('Content-Type: application/json');
require __DIR__ . '/dbConnection.php';

// Accept GET or POST for convenience
$input = $_SERVER['REQUEST_METHOD'] === 'GET' ? $_GET : json_decode(file_get_contents('php://input'), true);
if (!$input) {$input = [];}

$minRating = isset($input['minRating']) ? (float)$input['minRating'] : 0;
$difficulty = isset($input['difficulty']) ? strtolower(trim((string)$input['difficulty'])) : null;
$createdAfter = isset($input['createdAfter']) ? trim((string)$input['createdAfter']) : null;
$createdBefore = isset($input['createdBefore']) ? trim((string)$input['createdBefore']) : null;
$creatorID = isset($input['creatorID']) ? (int)$input['creatorID'] : null;
$search = isset($input['searchText']) ? trim((string)$input['searchText']) : null;
$limit = isset($input['limit']) ? max(1,(int)$input['limit']) : 50;
$offset = isset($input['offset']) ? max(0,(int)$input['offset']) : 0;

$where = [];
$params = [];
if ($minRating > 0) { $where[] = 'COALESCE(qr.avg_rating,0) >= :minr'; $params[':minr']=$minRating; }
if ($difficulty && in_array($difficulty,['easy','medium','hard'],true)) { $where[] = 'LOWER(q.difficulty) = :diff'; $params[':diff']=$difficulty; }
if ($createdAfter) { $t = strtotime($createdAfter); if ($t !== false) { $where[] = 'q.created_at >= :ca'; $params[':ca'] = date('Y-m-d H:i:s', $t); } }
if ($createdBefore) { $t = strtotime($createdBefore); if ($t !== false) { $where[] = 'q.created_at <= :cb'; $params[':cb'] = date('Y-m-d H:i:s', $t); } }
if ($creatorID) { $where[] = 'q.creatorID = :creatorID'; $params[':creatorID']=$creatorID; }
if ($search) { $where[] = '(q.question_text LIKE :s OR EXISTS (SELECT 1 FROM AnswerOption ao WHERE ao.questionID = q.questionID AND ao.option_text LIKE :s))'; $params[':s'] = '%'.$search.'%'; }

$whereSQL = empty($where) ? '' : 'WHERE '.implode(' AND ', $where);

$sql = "SELECT q.questionID, q.question_text, q.type, LOWER(q.difficulty) AS difficulty, COALESCE(qr.avg_rating,0) AS avg_rating
        FROM Question q
        LEFT JOIN (SELECT questionID, AVG(rating) AS avg_rating FROM QuestionRating GROUP BY questionID) qr ON qr.questionID = q.questionID
        $whereSQL
        ORDER BY qr.avg_rating DESC, q.created_at DESC
        LIMIT :lim OFFSET :off";

$stmt = $pdo->prepare($sql);
foreach ($params as $k=>$v) {
    $stmt->bindValue($k,$v);
}
$stmt->bindValue(':lim',(int)$limit,PDO::PARAM_INT);
$stmt->bindValue(':off',(int)$offset,PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

// return IDs + texts
$out = array_map(function($r){ return ['questionID'=>(int)$r['questionID'],'text'=>$r['question_text'],'type'=>strtolower($r['type']),'difficulty'=>$r['difficulty'],'avgRating'=>(float)$r['avg_rating']]; }, $rows);

echo json_encode($out);
