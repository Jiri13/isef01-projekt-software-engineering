<?php
// api/getQuizQuestions.php
// [WHY] Endpoint zum Abrufen aller Fragen für ein bestimmtes Quiz inklusive deren Antwortoptionen
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require __DIR__ . '/dbConnection.php';

$quizID = isset($_GET['quizID']) ? (int)$_GET['quizID'] : 0;

if ($quizID <= 0) {
    echo json_encode(['error' => 'Ungültige quizID']);
    exit;
}

try {
    // Alle Fragen für das angegebene Quiz holen
    $stmt = $pdo->prepare("
        SELECT
            q.questionID AS questionId,
            q.question_text AS questionText,
            q.question_type AS questionType,
            q.difficulty,
            q.time_limit AS timeLimit,
            q.explanation,
            q.quizID,
            q.userID
        FROM question q
        WHERE q.quizID = :quizID
        ORDER BY q.created_at DESC
    ");
    $stmt->execute([':quizID' => $quizID]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Antwortoptionen zu jeder Frage laden
    foreach ($questions as &$q) {
        $optStmt = $pdo->prepare("
            SELECT
                qo.optionID AS optionId,
                qo.option_text AS optionText,
                qo.is_correct AS isCorrect
            FROM question_option qo
            WHERE qo.questionID = :id
        ");
        $optStmt->execute([':id' => $q['questionId']]);
        $q['options'] = $optStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode(['questions' => $questions], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Fehler beim Laden der Fragen',
        'details' => $e->getMessage()
    ]);
}
