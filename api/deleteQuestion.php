<?php
// api/deleteQuestion.php
// Löscht eine Frage und deren Antwortoptionen (falls vorhanden)

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

session_start();
require __DIR__ . '/dbConnection.php';

$input = json_decode(file_get_contents('php://input'), true);
$questionID = isset($input['questionID']) ? (int)$input['questionID'] : 0;

if ($questionID <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'questionID required']);
    exit;
}

if (!isset($_SESSION['userID'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$currentUserID   = (int)$_SESSION['userID'];
$currentUserRole = $_SESSION['user_role'] ?? 'Creator';

try {
    // Eigentümer abfragen
    $stmtOwner = $pdo->prepare("SELECT userID FROM question WHERE questionID = :qid");
    $stmtOwner->execute([':qid' => $questionID]);
    $owner = $stmtOwner->fetch(PDO::FETCH_ASSOC);

    if (!$owner) {
        http_response_code(404);
        echo json_encode(['error' => 'Question not found']);
        exit;
    }

    $ownerID = (int)$owner['userID'];

    // Berechtigung prüfen
    if ($ownerID !== $currentUserID && $currentUserRole !== 'Admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Not allowed to delete this question']);
        exit;
    }

    $pdo->beginTransaction();

    // zuerst Optionen löschen
    $pdo->prepare("DELETE FROM question_option WHERE questionID = :qid")
        ->execute([':qid' => $questionID]);

    // dann Frage
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
