<?php
// api/removeQuestionFromQuiz.php
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
    $stmt = $pdo->prepare("DELETE FROM quizquestion WHERE quizID = :qid AND questionID = :qpid");
    $stmt->execute([':qid' => $quizID, ':qpid' => $questionID]);

    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Removal failed', 'details' => $e->getMessage()]);
}