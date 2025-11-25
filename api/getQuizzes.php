<?php
// api/getQuizzes.php
// [WHY] Endpoint zum Abrufen aller Quizzes inklusive Erstellername
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
            CONCAT(u.first_name, ' ', u.last_name) AS creatorName
        FROM quiz q
        LEFT JOIN users u ON q.userID = u.userID
        ORDER BY q.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($quizzes, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Fehler beim Laden der Quizzes', 'details' => $e->getMessage()]);
}
