<?php
// api/removeQuestionFromQuiz.php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/dbConnection.php';

$data = json_decode(file_get_contents('php://input'), true);

$quizID     = (int)($data['quizID'] ?? 0);
$questionID = (int)($data['questionID'] ?? 0);

if ($quizID <= 0 || $questionID <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'quizID and questionID required']);
    exit;
}

$sql = "DELETE FROM quizquestion WHERE quizID = :qid AND questionID = :qidst";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':qid'   => $quizID,
    ':qidst' => $questionID
]);

echo json_encode(['ok' => true]);
