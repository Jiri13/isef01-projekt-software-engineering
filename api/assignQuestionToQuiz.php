<?php
// api/assignQuestionToQuiz.php
// Ordnet eine bestehende Frage einem bestehenden Quiz zu.
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require __DIR__ . '/dbConnection.php';

$input = json_decode(file_get_contents('php://input'), true);

$quizID     = (int)($input['quizID'] ?? 0);
$questionID = (int)($input['questionID'] ?? 0);

if ($quizID <= 0 || $questionID <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'quizID and questionID required']);
    exit;
}

try {
    // 1. Prüfen, ob Zuordnung schon existiert (um Fehler zu vermeiden)
    $checkStmt = $pdo->prepare("SELECT 1 FROM quizquestion WHERE quizID = :qid AND questionID = :qpid");
    $checkStmt->execute([':qid' => $quizID, ':qpid' => $questionID]);

    if ($checkStmt->fetch()) {
        // Schon da? Dann einfach Erfolg melden
        echo json_encode(['ok' => true, 'message' => 'Already assigned']);
        exit;
    }

    // 2. Einfügen
    $stmt = $pdo->prepare("INSERT INTO quizquestion (quizID, questionID) VALUES (:qid, :qpid)");
    $stmt->execute([':qid' => $quizID, ':qpid' => $questionID]);

    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Assignment failed', 'details' => $e->getMessage()]);
}