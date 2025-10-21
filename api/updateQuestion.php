<?php
// api/updateQuestion.php
// [WHY] Endpoint zum Bearbeiten einer bestehenden Frage (Text, Schwierigkeit, Typ, Zuordnung zu Modul)


header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {exit;}
require __DIR__ . '/dbConnection.php';

$input = json_decode(file_get_contents('php://input'), true);

$questionID = isset($input['questionID']) ? (int)$input['questionID'] : 0;
$questionText = array_key_exists('question_text', $input) ? trim((string)$input['question_text']) : null;
$diffIn = array_key_exists('difficulty', $input) ? strtolower(trim((string)$input['difficulty'])) : null;
$typeIn = array_key_exists('type', $input) ? strtolower(trim((string)$input['type'])) : null;

if ($questionID <= 0) { 
    http_response_code(400);
    echo json_encode(['error'=>'questionID required']); exit;
}

// Prüfen, ob die Frage existiert.
$st = $pdo->prepare("SELECT questionID FROM Question WHERE questionID = :q LIMIT 1");
$st->execute([':q'=>$questionID]);
$q = $st->fetch();
if (!$q) {
    http_response_code(404); 
    echo json_encode(['error'=>'question not found']);
    exit;
}

try {
    $pdo->beginTransaction();

    $pdo->prepare("
        UPDATE Question
        SET question_text = :qt, questionType = :ty, difficulty = :df
        WHERE questionID = :qid
    ")->execute([
        ':qt' => $questionText,
        ':ty' => $type,
        ':df' => $difficulty,
        ':qid' => $questionID
    ]);

    // Alte Optionen löschen und neu anlegen
    $pdo->prepare("DELETE FROM Question_Option WHERE questionID = :qid")->execute([':qid' => $questionID]);

    if (!empty($options) && $type === 'mc') {
        $insOpt = $pdo->prepare("
            INSERT INTO Question_Option (questionID, option_text, is_correct, explanation)
            VALUES (:qid, :txt, :isc, :exp)
        ");
        foreach ($options as $opt) {
            $insOpt->execute([
                ':qid' => $questionID,
                ':txt' => $opt['text'] ?? '',
                ':isc' => !empty($opt['isCorrect']) ? 1 : 0,
                ':exp' => $opt['explanation'] ?? null
            ]);
        }
    }

    $pdo->commit();
    echo json_encode(['status' => 'success']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Update failed', 'details' => $e->getMessage()]);
}
