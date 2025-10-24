<?php
// api/getQuizzes.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require __DIR__ . '/dbConnection.php';

$userID = isset($_GET['userID']) ? (int)$_GET['userID'] : 0;

try {
    $sql = "
        SELECT
            q.quizID,
            q.title,
            q.quiz_description,
            q.category,
            q.created_at,
            u.username AS creatorName
        FROM Quiz q
        LEFT JOIN User u ON q.userID = u.userID
        WHERE (:userID = 0 OR q.userID = :userID)
        ORDER BY q.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':userID' => $userID]);
    $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($quizzes, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Fehler beim Laden der Quizzes', 'details' => $e->getMessage()]);
}
