<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

session_start();
require __DIR__ . '/dbConnection.php';

if (empty($_SESSION['userID'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$me = (int)$_SESSION['userID'];
$role = (string)($_SESSION['user_role'] ?? '');

$input = json_decode(file_get_contents('php://input'), true);
$quizID = (int)($input['quizID'] ?? 0);

if ($quizID <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'quizID required']);
    exit;
}

try {
    // Owner prüfen
    $ownStmt = $pdo->prepare("SELECT userID FROM quiz WHERE quizID = :id LIMIT 1");
    $ownStmt->execute([':id' => $quizID]);
    $row = $ownStmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'Quiz not found']);
        exit;
    }

    $ownerID = (int)$row['userID'];

    if ($role !== 'Admin' && $ownerID !== $me) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden: not owner']);
        exit;
    }

    $pdo->beginTransaction();

    $pdo->prepare("DELETE FROM quizquestion WHERE quizID = :q")
        ->execute([':q' => $quizID]);

    $pdo->prepare("DELETE FROM quiz WHERE quizID = :q")
        ->execute([':q' => $quizID]);

    $pdo->commit();

    echo json_encode(['ok' => true]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Delete failed', 'details' => $e->getMessage()]);
}
