<?php
//Marie
// api/getAllQuestions.php
// Liefert alle Fragen aus der DB (ohne Optionen) – für CreateQuizModal.vue

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require __DIR__ . '/dbConnection.php';

try {
    $stmt = $pdo->query("
        SELECT
            questionID,
            question_text,
            question_type,
            difficulty,
            time_limit,
            explanation
        FROM question
        ORDER BY created_at DESC
    ");

    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($questions, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error'   => 'Failed to fetch questions',
        'details' => $e->getMessage()
    ]);
}
