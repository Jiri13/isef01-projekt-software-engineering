<?php
// api/validateAnswer.php
// Validiert eine Antwort (MC oder Freitext). Liefert richtig/falsch und ggf. Erklärung.
// Input: {"questionID":123, "answer": "text"} für Freitext oder {"selectedOptionID": 44} für MC


header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {exit;}
require __DIR__ . '/dbConnection.php';

$input = json_decode(file_get_contents('php://input'), true);
$questionID = isset($input['questionID']) ? (int)$input['questionID'] : 0;
$selectedOptionID = isset($input['selectedOptionID']) ? (int)$input['selectedOptionID'] : null;
$freeText = isset($input['answer']) ? trim((string)$input['answer']) : null;

// Mindestangaben prüfen
if ($questionID <= 0) { http_response_code(400); echo json_encode(['error'=>'questionID required']); exit; }

// Fragentyp ermitteln
$st = $pdo->prepare("SELECT type FROM Question WHERE questionID = :q LIMIT 1");
$st->execute([':q'=>$questionID]);
$q = $st->fetch();
if (!$q) { http_response_code(404); echo json_encode(['error'=>'question not found']); exit; }
$type = strtolower($q['type']);

// Validierung je nach Fragentyp
if ($type === 'mc') {
    if (!$selectedOptionID) { http_response_code(400); echo json_encode(['error'=>'selectedOptionID required for MC questions']); exit; }
    $stO = $pdo->prepare("SELECT is_correct, explanation FROM AnswerOption WHERE optionID = :o AND questionID = :q LIMIT 1");
    $stO->execute([':o'=>$selectedOptionID, ':q'=>$questionID]);
    $o = $stO->fetch();
    if (!$o) { http_response_code(404); echo json_encode(['error'=>'option not found for question']); exit; }
    $isCorrect = (int)$o['is_correct'] === 1;
    $ex = $o['explanation'];
    echo json_encode(['questionID'=>$questionID,'isCorrect'=>$isCorrect,'explanation'=>$ex]);
    exit;
} else {
    // Simple freetext validation: exact match against AnswerOption.option_text marked as correct
    if ($freeText === null || $freeText === '') { http_response_code(400); echo json_encode(['error'=>'answer text required for free-text questions']); exit; }
    $stA = $pdo->prepare("SELECT option_text, explanation FROM AnswerOption WHERE questionID = :q AND is_correct = 1");
    $stA->execute([':q'=>$questionID]);
    $matches = [];
    while ($row = $stA->fetch()) {
        // Hier: einfache Case-insensitive comparison; kann erweitert werden (levenshtein, NLP etc.)
        if (strcasecmp(trim($row['option_text']), $freeText) === 0) {
            $matches[] = $row;
        }
    }
    $isCorrect = !empty($matches);
    $ex = !empty($matches) ? $matches[0]['explanation'] : null;
    echo json_encode(['questionID'=>$questionID,'isCorrect'=>$isCorrect,'explanation'=>$ex]);
    exit;
}

