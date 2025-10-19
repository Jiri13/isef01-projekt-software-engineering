<?php
// api/questionDetails.php
// Gibt alle Details zu einer Frage zurück (inkl. Optionen, Ratings, Ersteller, create-date)


header('Content-Type: application/json');
require __DIR__ . '/dbConnection.php';

$questionID = isset($_GET['questionID']) ? (int)$_GET['questionID'] : 0;
if ($questionID <= 0) { http_response_code(400); echo json_encode(['error'=>'questionID required']); exit; }

$sql = "SELECT q.*, u.userName AS creatorName, u.userID AS creatorID
        FROM Question q
        LEFT JOIN Users u ON u.userID = q.creatorID
        WHERE q.questionID = :q LIMIT 1";
$st = $pdo->prepare($sql);
$st->execute([':q'=>$questionID]);
$q = $st->fetch();
if (!$q) { http_response_code(404); echo json_encode(['error'=>'question not found']); exit; }

// Optionen
$stO = $pdo->prepare("SELECT optionID, option_text, is_correct, explanation FROM AnswerOption WHERE questionID = :q ORDER BY optionID ASC");
$stO->execute([':q'=>$questionID]);
$opts = $stO->fetchAll();

// Ratings
$stR = $pdo->prepare("SELECT COUNT(*) AS cnt, AVG(rating) AS avg FROM QuestionRating WHERE questionID = :q");
$stR->execute([':q'=>$questionID]);
$r = $stR->fetch();

$out = [
    'questionID'=>(int)$q['questionID'],
    'text'=>$q['question_text'],
    'type'=>strtolower($q['type']),
    'difficulty'=>strtolower($q['difficulty']),
    'moduleID'=> (int)$q['moduleID'],
    'quizID'=> $q['quizID'] !== null ? (int)$q['quizID'] : null,
    'creator'=> ['id'=>(int)$q['creatorID'],'name'=>$q['creatorName']],
    'createdAt'=>$q['created_at'],
    'ratings'=> ['count'=>(int)$r['cnt'], 'avg'=> $r['avg'] !== null ? (float)$r['avg'] : 0],
    'options'=>array_map(function($o){ return ['optionID'=>(int)$o['optionID'],'text'=>$o['option_text'],'isCorrect'=> (int)$o['is_correct'] === 1, 'explanation'=>$o['explanation']]; }, $opts)
];

echo json_encode($out);
