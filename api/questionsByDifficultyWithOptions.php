<?php
// api/questionsByDifficultyWithOptions.php
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

// Input: difficulty=easy|medium|hard, limit (default 10), optional category
$diffParam = isset($_GET['difficulty']) ? strtolower(trim($_GET['difficulty'])) : 'easy';
$map = ['easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard'];
$difficulty = $map[$diffParam] ?? 'Easy';

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
if ($limit <= 0) $limit = 10;
if ($limit > 50) $limit = 50;

$category = isset($_GET['category']) ? trim((string)$_GET['category']) : null;

try {
    // 1) Fragen passend zur Difficulty (und optional Kategorie) holen, zufällig
    $where = "q.difficulty = :difficulty";
    $params = [':difficulty' => $difficulty];
    if ($category !== null && $category !== '') {
        $where .= " AND qz.category = :category";
        $params[':category'] = $category;
    }

    $sqlQuestions = "
        SELECT 
            q.questionID            AS questionId,
            q.quizID                AS quizId,
            q.question_text         AS questionText,
            q.difficulty            AS difficulty,
            q.explanation           AS explanation, 
            IFNULL(qz.time_limit,NULL) AS timeLimit
        FROM Question q
        LEFT JOIN Quiz qz ON qz.quizID = q.quizID
        WHERE $where
        ORDER BY RAND()
        LIMIT $limit
    ";
    $stmtQ = $pdo->prepare($sqlQuestions);
    $stmtQ->execute($params);
    $questions = $stmtQ->fetchAll();

    if (!$questions) {
        echo json_encode(['questions' => []]);
        exit;
    }

    // 2) Optionen zu allen gefundenen Fragen holen
    $ids = array_column($questions, 'questionId');
    $in  = implode(',', array_fill(0, count($ids), '?'));

    $sqlOptions = "
        SELECT 
            o.optionID     AS optionId,
            o.option_text  AS optionText,
            o.is_correct   AS isCorrect,
            o.questionID   AS questionId
        FROM Question_Option o
        WHERE o.questionID IN ($in)
        ORDER BY o.optionID
    ";
    $stmtO = $pdo->prepare($sqlOptions);
    $stmtO->execute($ids);
    $options = $stmtO->fetchAll();

    // 3) Normalisieren & mappen
    $byId = [];
    foreach ($questions as $q) {
        $q['difficulty'] = $q['difficulty'] !== null ? strtolower($q['difficulty']) : null;
        $q['options'] = [];
        $byId[$q['questionId']] = $q;
    }
    foreach ($options as $o) {
        $o['isCorrect'] = (bool)$o['isCorrect'];
        if (isset($byId[$o['questionId']])) {
            $byId[$o['questionId']]['options'][] = $o;
        }
    }

    echo json_encode(['questions' => array_values($byId)], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $e) {
    error_log('[questionsByDifficultyWithOptions] '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
}
