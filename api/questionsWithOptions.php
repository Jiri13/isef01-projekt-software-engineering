<?php
// api/questionsWithOptions.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require __DIR__ . '/dbConnection.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection not initialized ($pdo).']);
    exit;
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$quizID = isset($_GET['quizID']) ? (int)$_GET['quizID'] : 0;
if ($quizID <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'quizID required']);
    exit;
}

try {
    // Fragen laden (Question.question_text + difficulty), timeLimit aus Quiz.time_limit mappen
    $sqlQuestions = "
        SELECT 
            q.questionID                    AS questionId,
            q.quizID                        AS quizId,
            q.question_text                 AS questionText,
            q.difficulty                    AS difficulty,
            q.explanation                   AS explanation,
            IFNULL(qz.time_limit, NULL)     AS timeLimit
        FROM Question q
        LEFT JOIN Quiz qz ON qz.quizID = q.quizID
        WHERE q.quizID = :quizID
        ORDER BY q.questionID ASC
    ";
    $stmtQ = $pdo->prepare($sqlQuestions);
    $stmtQ->execute([':quizID' => $quizID]);
    $questions = $stmtQ->fetchAll();

    if (!$questions) {
        echo json_encode(['quizId' => $quizID, 'questions' => []]);
        exit;
    }

    // IDs für Options-Abfrage
    $ids = array_column($questions, 'questionId');
    $in  = implode(',', array_fill(0, count($ids), '?'));

    // Optionen laden (Question_Option)
    $sqlOptions = "
        SELECT 
            o.optionID     AS optionId,
            o.option_text  AS optionText,
            o.is_correct   AS isCorrect,
            o.questionID   AS questionId
        FROM Question_Option o
        WHERE o.questionID IN ($in)
        ORDER BY o.optionID ASC
    ";
    $stmtO = $pdo->prepare($sqlOptions);
    $stmtO->execute($ids);
    $options = $stmtO->fetchAll();

    // Fragen nach ID mappen + Normalisierung
    $byId = [];
    foreach ($questions as $q) {
        // difficulty: 'Easy'|'Medium'|'Hard' -> 'easy'|'medium'|'hard'
        $q['difficulty'] = $q['difficulty'] !== null ? strtolower($q['difficulty']) : null;
        $q['options'] = [];
        $byId[$q['questionId']] = $q;
    }

    foreach ($options as $o) {
        $qid = $o['questionId'];
        if (isset($byId[$qid])) {
            $o['isCorrect'] = (bool)$o['isCorrect'];
            $byId[$qid]['options'][] = $o;
        }
    }

    $result = [
        'quizId'    => $quizID,
        'questions' => array_values($byId),
    ];

    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

} catch (Throwable $e) {
    error_log('[questionsWithOptions] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
}
