<?php
// api/updateQuiz.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require __DIR__ . '/dbConnection.php';

$input = json_decode(file_get_contents('php://input'), true);

$quizID      = (int)($input['quizID'] ?? 0);
$title       = trim($input['title'] ?? '');
$description = trim($input['description'] ?? '');
$category    = trim($input['category'] ?? '');
$timeLimit   = (int)($input['timeLimit'] ?? 0);

if ($quizID <= 0 || empty($title)) {
    http_response_code(400);
    echo json_encode(['error' => 'quizID and title required']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE quiz
        SET title = :title,
            quiz_description = :desc,
            category = :cat,
            time_limit = :limit
        WHERE quizID = :id
    ");

    $stmt->execute([
        ':title' => $title,
        ':desc'  => $description,
        ':cat'   => $category,
        ':limit' => $timeLimit,
        ':id'    => $quizID
    ]);

    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Update failed', 'details' => $e->getMessage()]);
}