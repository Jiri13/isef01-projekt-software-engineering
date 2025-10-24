<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require __DIR__ . '/dbConnection.php';

try {
    $stmt = $pdo->query("
        SELECT
            questionID AS id,
            question_text AS text,
            question_type AS type,
            difficulty,
            time_limit AS timeLimit,
            explanation,
            quizID,
            userID
        FROM Question
        ORDER BY created_at DESC
    ");
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Optionen für jede Frage laden
    foreach ($questions as &$q) {
        $optStmt = $pdo->prepare("SELECT option_text AS text, is_correct AS isCorrect FROM Question_Option WHERE questionID = :id");
        $optStmt->execute([':id' => $q['id']]);
        $q['options'] = $optStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode($questions);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
}
