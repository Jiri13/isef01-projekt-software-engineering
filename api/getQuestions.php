<?php
// api/getQuestions.php
// [WHY] Endpoint zum Abrufen aller Fragen inklusive deren Antwortoptionen
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require __DIR__ . '/dbConnection.php';

try {
    $stmt = $pdo->query("
        SELECT
            questionID      AS id,
            question_text   AS text,
            question_type   AS type,
            difficulty,
            time_limit      AS timeLimit,
            explanation,
            quizID,
            userID,
            created_at      AS createdAt
        FROM question
        ORDER BY created_at DESC, questionID DESC
    ");

    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Optionen für jede Frage laden
    if ($questions) {
        $optStmt = $pdo->prepare("
            SELECT
                optionID      AS id,
                option_text   AS text,
                is_correct    AS isCorrect
            FROM question_option
            WHERE questionID = :id
            ORDER BY optionID ASC
        ");

        foreach ($questions as &$q) {
            $optStmt->execute([':id' => $q['id']]);
            $q['options'] = $optStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($q);
    }

    echo json_encode($questions);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
}
