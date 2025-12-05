<?php
// api/deleteQuiz.php
// Löscht ein Quiz + alle zugehörigen Einträge in quizquestion

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require __DIR__ . '/dbConnection.php';

$input = json_decode(file_get_contents('php://input'), true);
$quizID = isset($input['quizID']) ? (int)$input['quizID'] : 0;

if ($quizID <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'quizID required']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Beziehungen löschen
    $pdo->prepare("DELETE FROM quizquestion WHERE quizID = :q")
        ->execute([':q' => $quizID]);

    // Quiz löschen
    $pdo->prepare("DELETE FROM quiz WHERE quizID = :q")
        ->execute([':q' => $quizID]);

    $pdo->commit();

    echo json_encode(['ok' => true]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Delete failed', 'details' => $e->getMessage()]);
}
