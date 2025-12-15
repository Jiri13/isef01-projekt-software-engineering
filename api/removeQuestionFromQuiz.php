<?php
// api/removeQuestionFromQuiz.php
// Zweck:
// Entfernt eine bestehende Zuordnung zwischen einer Frage und einem Quiz.
// Es wird dabei NUR der Eintrag aus der Beziehungstabelle `quizquestion` gelöscht.
// Die Frage selbst bleibt weiterhin im Fragenkatalog erhalten.
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require __DIR__ . '/dbConnection.php';

// 1) JSON-Body aus dem Request lesen
$input = json_decode(file_get_contents('php://input'), true);

// Quiz- und Frage-ID aus dem Request extrahieren
$quizID     = (int)($input['quizID'] ?? 0);
$questionID = (int)($input['questionID'] ?? 0);

// 2) Eingabevalidierung
if ($quizID <= 0 || $questionID <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'quizID and questionID required']);
    exit;
}

try {
    // 3) Zuordnung zwischen Quiz und Frage löschen
    $stmt = $pdo->prepare("DELETE FROM quizquestion WHERE quizID = :qid AND questionID = :qpid");
    $stmt->execute([':qid' => $quizID, ':qpid' => $questionID]);

    // 4) Erfolgreiche Antwort zurückgeben
    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    // 5) Fehlerbehandlung
    http_response_code(500);
    echo json_encode(['error' => 'Removal failed', 'details' => $e->getMessage()]);
}