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

$quizID      = (int)($input['quizID'] ?? 0);
$title       = trim($input['title'] ?? '');
$description = trim($input['description'] ?? '');
$category    = trim($input['category'] ?? '');
$timeLimitRaw = $input['timeLimit'] ?? null;
$timeLimit   = ($timeLimitRaw === '' || $timeLimitRaw === null) ? null : (int)$timeLimitRaw;

if ($quizID <= 0 || $title === '') {
    http_response_code(400);
    echo json_encode(['error' => 'quizID and title required']);
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
        ':limit' => $timeLimit, // darf NULL sein
        ':id'    => $quizID
    ]);

    echo json_encode(['ok' => true]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Update failed', 'details' => $e->getMessage()]);
}
