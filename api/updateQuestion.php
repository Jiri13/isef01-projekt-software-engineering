<?php
// api/updateQuestion.php
// [WHY]Endpoint zum Aktualisieren einer bestehenden Frage + Antwortoptionen

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require __DIR__ . '/dbConnection.php';

// JSON-Daten vom Frontend lesen
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing JSON body']);
    exit;
}

// Eingaben prüfen
$questionID  = isset($input['questionID']) ? (int)$input['questionID'] : 0;
$text        = trim((string)($input['text'] ?? ''));
$type        = strtolower(trim((string)($input['type'] ?? 'multiple_choice')));
$difficulty  = strtolower(trim((string)($input['difficulty'] ?? 'medium')));
$explanation = trim((string)($input['explanation'] ?? ''));
$timeLimit   = isset($input['timeLimit']) ? (int)$input['timeLimit'] : 30;
$options     = isset($input['options']) && is_array($input['options']) ? $input['options'] : [];

if ($questionID <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'questionID required']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Frage aktualisieren
    $stmt = $pdo->prepare("
        UPDATE question
        SET question_text = :text,
            question_type = :type,
            difficulty = :difficulty,
            explanation = :explanation,
            time_limit = :timeLimit
        WHERE questionID = :questionID
    ");
    $stmt->execute([
        ':text'        => $text,
        ':type'        => $type,
        ':difficulty'  => $difficulty,
        ':explanation' => $explanation,
        ':timeLimit'   => $timeLimit,
        ':questionID'  => $questionID
    ]);

    // Alte Optionen löschen
    $pdo->prepare("DELETE FROM question_option WHERE questionID = :qid")
        ->execute([':qid' => $questionID]);

    // Neue Optionen einfügen (falls Multiple Choice)
    if ($type === 'multiple_choice' && !empty($options)) {
        $optStmt = $pdo->prepare("
            INSERT INTO question_option (questionID, option_text, is_correct)
            VALUES (:questionID, :text, :isCorrect)
        ");
        foreach ($options as $opt) {
            $optText = trim((string)($opt['text'] ?? ''));
            if ($optText === '') {
                continue;
            }

            $isCorrect = !empty($opt['isCorrect']) ? 1 : 0;
            $optStmt->execute([
                ':questionID' => $questionID,
                ':text'       => $optText,
                ':isCorrect'  => $isCorrect
            ]);
        }
    }

    $pdo->commit();

    echo json_encode(['ok' => true, 'questionID' => $questionID]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'error' => 'Update failed',
        'details' => $e->getMessage()
    ]);
}
