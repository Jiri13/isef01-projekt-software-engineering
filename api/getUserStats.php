<?php
// api/getUserStats.php
// Liefert aggregierte Statistiken für einen User über alle Quizzes

header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/dbConnection.php';

$userID = isset($_GET['userID']) ? (int)$_GET['userID'] : 0;
if ($userID <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'userID required']);
    exit;
}

$sql = "
    SELECT
        COALESCE(SUM(games_played), 0)      AS gamesPlayed,
        COALESCE(SUM(total_answers), 0)     AS totalAnswers,
        COALESCE(SUM(correct_answers), 0)   AS correctAnswers,
        COALESCE(SUM(incorrect_answers), 0) AS wrongAnswers
    FROM userquizstats
    WHERE userID = :uid
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':uid' => $userID]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    $row = [
        'gamesPlayed'    => 0,
        'totalAnswers'   => 0,
        'correctAnswers' => 0,
        'wrongAnswers'   => 0
    ];
}

echo json_encode($row);
