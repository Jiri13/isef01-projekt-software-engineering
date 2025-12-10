<?php
// api/getQuizQuestions.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
require __DIR__ . '/dbConnection.php';

$quizID = isset($_GET['quizID']) ? (int)$_GET['quizID'] : 0;

if ($quizID <= 0) {
    echo json_encode(['questions' => []]);
    exit;
}

try {
    // Wichtig: JOIN muss korrekt sein
    $stmt = $pdo->prepare("
        SELECT 
            q.questionID, 
            q.question_text, 
            q.question_type, 
            q.difficulty, 
            q.time_limit, 
            q.explanation
        FROM question q
        JOIN quizquestion qq ON q.questionID = qq.questionID
        WHERE qq.quizID = :qid
    ");

    $stmt->execute([':qid' => $quizID]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['questions' => $questions]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Fehler', 'details' => $e->getMessage()]);
}