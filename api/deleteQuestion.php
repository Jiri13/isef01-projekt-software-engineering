<?php
// api/deleteQuestion.php
// [WHY] Löscht eine Frage und deren Antwortoptionen (falls vorhanden)

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require __DIR__ . '/dbConnection.php';

$input = json_decode(file_get_contents('php://input'), true);
$questionID = isset($input['questionID']) ? (int)$input['questionID'] : 0;

if ($questionID <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'questionID required']);
    exit;
}

try {
    $pdo->beginTransaction();

    // zuerst Optionen löschen
    $pdo->prepare("DELETE FROM question_option WHERE questionID = :qid")
        ->execute([':qid' => $questionID]);

    // dann eigentliche Frage
    $pdo->prepare("DELETE FROM question WHERE questionID = :qid")
        ->execute([':qid' => $questionID]);

    $pdo->commit();

    echo json_encode(['ok' => true, 'deletedID' => $questionID]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'error' => 'Delete failed',
        'details' => $e->getMessage()
    ]);
}
