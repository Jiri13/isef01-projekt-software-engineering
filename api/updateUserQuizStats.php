<?php
// api/updateUserQuizStats.php
// [WHY] Aktualisiert die Statistik eines Users für ein bestimmtes Quiz
//       (gesamt gespielte Spiele, richtige/falsche Antworten).

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit; // CORS-Preflight
}

require __DIR__ . '/dbConnection.php'; // stellt $pdo bereit

function respond($success, $data = [], $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode(array_merge(['success' => $success], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

// --- JSON-Body einlesen ---
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);

$userID        = isset($input['userID'])        ? (int)$input['userID']        : 0;
$quizID        = isset($input['quizID'])        ? (int)$input['quizID']        : 0;
$totalAnswers  = isset($input['totalAnswers'])  ? (int)$input['totalAnswers']  : 0;
$correct       = isset($input['correctAnswers'])? (int)$input['correctAnswers']: 0;

if ($userID <= 0 || $quizID <= 0 || $totalAnswers <= 0 || $correct < 0) {
    respond(false, [
        'error'   => 'Invalid or missing fields',
        'payload' => $input
    ], 400);
}

$incorrect = max($totalAnswers - $correct, 0);

try {
    // Upsert in userquizstats
    $sql = "
        INSERT INTO userquizstats (
            userID, quizID, games_played,
            total_answers, correct_answers, incorrect_answers, last_played
        ) VALUES (
            :userID, :quizID, 1,
            :total_answers, :correct_answers, :incorrect_answers, NOW()
        )
        ON DUPLICATE KEY UPDATE
            games_played       = games_played + 1,
            total_answers      = total_answers + VALUES(total_answers),
            correct_answers    = correct_answers + VALUES(correct_answers),
            incorrect_answers  = incorrect_answers + VALUES(incorrect_answers),
            last_played        = VALUES(last_played)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':userID'            => $userID,
        ':quizID'            => $quizID,
        ':total_answers'     => $totalAnswers,
        ':correct_answers'   => $correct,
        ':incorrect_answers' => $incorrect
    ]);

    respond(true, [
        'message'           => 'Statistiken erfolgreich aktualisiert',
        'userID'            => $userID,
        'quizID'            => $quizID,
        'totalAnswers'      => $totalAnswers,
        'correctAnswers'    => $correct,
        'incorrectAnswers'  => $incorrect,
        'affected_rows'     => $stmt->rowCount()
    ]);

} catch (Throwable $e) {
    respond(false, [
        'error' => 'DB error',
        'details' => $e->getMessage()
    ], 500);
}
