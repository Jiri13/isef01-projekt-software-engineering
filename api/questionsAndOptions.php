<?php
// api/questionsAndOptions.php
// Liefert Fragen inkl. Antwortoptionen eines Moduls bzw. Quiz.
// Filter-Params: moduleID|quizID optional, limit, offset, includeExplanations


header('Content-Type: application/json');
require __DIR__ . '/dbConnection.php';

// GET params: moduleID OR quizID, limit, offset, difficulty, minRating
$moduleID = isset($_GET['moduleID']) ? (int)$_GET['moduleID'] : 0;
$quizID = isset($_GET['quizID']) ? (int)$_GET['quizID'] : 0;
$limit = isset($_GET['limit']) ? max(1,(int)$_GET['limit']) : 50;
$offset = isset($_GET['offset']) ? max(0,(int)$_GET['offset']) : 0;
$difficulty = isset($_GET['difficulty']) ? strtolower(trim((string)$_GET['difficulty'])) : null;
$minRating = isset($_GET['minRating']) ? (float)$_GET['minRating'] : 0;
$includeExplanations = !empty($_GET['includeExplanations']);

$sqlWhere = [];
$params = [];
if ($moduleID > 0) { $sqlWhere[] = 'q.moduleID = :moduleID'; $params[':moduleID']=$moduleID; }
if ($quizID > 0) { $sqlWhere[] = 'q.quizID = :quizID'; $params[':quizID']=$quizID; }
if ($difficulty && in_array($difficulty,['easy','medium','hard'],true)) { $sqlWhere[] = 'LOWER(q.difficulty) = :diff'; $params[':diff']=$difficulty; }
if ($minRating > 0) { $sqlWhere[] = 'COALESCE(qr.avg_rating,0) >= :minr'; $params[':minr']=$minRating; }
$where = empty($sqlWhere) ? '' : 'WHERE '.implode(' AND ', $sqlWhere);

// Frage-Hauptabfrage mit optionaler Aggregation der Ratings
$sql = "SELECT q.questionID, q.question_text, q.type, LOWER(q.difficulty) AS difficulty, q.moduleID, q.quizID, COALESCE(qr.avg_rating,0) AS avg_rating
        FROM Question q
        LEFT JOIN (SELECT questionID, AVG(rating) AS avg_rating FROM QuestionRating GROUP BY questionID) qr ON qr.questionID = q.questionID
        $where
        ORDER BY q.created_at DESC
        LIMIT :lim OFFSET :off";

$stmt = $pdo->prepare($sql);
$params[':lim'] = $limit; $params[':off'] = $offset;
// PDO: bind ints explicitly to avoid emulation issues
foreach ($params as $k=>$v) {
    if ($k === ':lim' || $k === ':off') $stmt->bindValue($k, (int)$v, PDO::PARAM_INT);
    else $stmt->bindValue($k, $v);
}
$stmt->execute();
$questions = $stmt->fetchAll();

$qIds = array_map(function($r){return (int)$r['questionID'];}, $questions);
$opts = [];
if (!empty($qIds)) {
    $ph = implode(',', array_fill(0, count($qIds), '?'));
    $sqlO = "SELECT optionID, questionID, option_text, is_correct, explanation FROM AnswerOption WHERE questionID IN ($ph) ORDER BY optionID ASC";
    $stO = $pdo->prepare($sqlO);
    $stO->execute($qIds);
    foreach ($stO->fetchAll() as $o) {
        $qid = (int)$o['questionID'];
        if (!isset($opts[$qid])) $opts[$qid]=[];
        $optEntry = ['optionID'=>(int)$o['optionID'],'text'=>$o['option_text']];
        if ($includeExplanations) $optEntry['explanation'] = $o['explanation'];
        // is_correct nicht standardmäßig für Clients ausliefern
        $opts[$qid][] = $optEntry;
    }
}

$out = [];
foreach ($questions as $q) {
    $qid = (int)$q['questionID'];
    $out[] = [
        'questionID'=>$qid,
        'text'=>$q['question_text'],
        'type'=>strtolower($q['type']),
        'difficulty'=>$q['difficulty'],
        'moduleID'=>(int)$q['moduleID'],
        'quizID'=>$q['quizID'] !== null ? (int)$q['quizID'] : null,
        'avgRating'=> (float)$q['avg_rating'],
        'options' => $opts[$qid] ?? []
    ];
}

echo json_encode($out);

