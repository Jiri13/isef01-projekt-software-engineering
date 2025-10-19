<?php
//verst.
// api/addAnswerOption.php
// [WHY] Fügt einer bestehenden Frage eine oder mehrere Antwortoptionen hinzu (kollaborativ).
// JSON: {"questionID":123, "creatorID":5, "options":[{"text":"...","isCorrect":true,"explanation":"..."}, ...]}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require __DIR__ . '/dbConnection.php';

$input = json_decode(file_get_contents('php://input'), true);

//Werte aus dem JSON herausziehen und Standardwerte setzen
$questionID = isset($input['questionID']) ? (int)$input['questionID'] : 0;
$creatorID = isset($input['creatorID']) ? (int)$input['creatorID'] : 0;
$options = isset($input['options']) && is_array($input['options']) ? $input['options'] : [];

// Mindestangaben prüfen
if ($questionID <= 0 || $creatorID <= 0 || empty($options)) { http_response_code(400); echo json_encode(['error'=>'questionID, creatorID and options[] required']); exit; }

// Quick existence checks
// überprüfe, ob Frage existiert und ob es sich um eine MC-Frage handelt.
$stQ = $pdo->prepare("SELECT type FROM Question WHERE questionID = :q LIMIT 1");
$stQ->execute([':q'=>$questionID]);
$q = $stQ->fetch();
if (!$q) { http_response_code(404); echo json_encode(['error'=>'question not found']); exit; }
if (strtolower($q['type']) !== 'mc') { http_response_code(400); echo json_encode(['error'=>'cannot add options to non-multiple-choice question']); exit; }

//Antwortoptionen werden in der DB gespeichert
try {
    $pdo->beginTransaction();
    $ins = $pdo->prepare("INSERT INTO AnswerOption (questionID, option_text, is_correct, explanation) VALUES (:q, :ot, :ic, :ex)");
    foreach ($options as $opt) {
        $text = trim((string)($opt['text'] ?? ''));
        if ($text === '') {continue;}
        $isCorrect = !empty($opt['isCorrect']) ? 1 : 0;
        $ex = isset($opt['explanation']) ? trim((string)$opt['explanation']) : null;
        $ins->execute([':q'=>$questionID, ':ot'=>$text, ':ic'=>$isCorrect, ':ex'=>$ex]);
    }
    $pdo->commit();
    echo json_encode(['ok'=>true,'questionID'=>$questionID]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error'=>'Insert options failed','details'=>$e->getMessage()]);
}

