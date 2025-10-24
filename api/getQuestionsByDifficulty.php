<?php
// api/getQuestionsByDifficulty.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require __DIR__ . '/dbConnection.php';

$difficulty = isset($_GET['difficulty']) ? strtolower(trim($_GET['difficulty'])) : 'easy';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$validDiffs = ['easy', 'medium', 'hard'];
if (!in_array($difficulty, $validDiffs)) {
    $difficulty = 'easy';
}

try {
    // 1. Fragen nach Schwierigkeit holen
    $stmt = $pdo->prepare("
        SELECT
            questionID,
            quizID,
            question_text AS text,
            question_type AS type,
            difficulty,
            explanation,
            time_limit AS timeLimit
        FROM Question
        WHERE difficulty = :diff
        ORDER BY RAND()
        LIMIT :lim
    ");
    $stmt->bindValue(':diff', $difficulty);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$questions) {
        echo json_encode(['questions' => []]);
        exit;
    }

    // 2. Optionen laden
    $ids = array_column($questions, 'questionID');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $optStmt = $pdo->prepare("
        SELECT questionID, option_text AS text, is_correct AS isCorrect
        FROM Question_Option
        WHERE questionID IN ($in)
    ");
    $optStmt->execute($ids);
    $options = $optStmt->fetchAll(PDO::FETCH_ASSOC);

    $byQ = [];
    foreach ($options as $o) {
        $byQ[$o['questionID']][] = [
            'text' => $o['text'],
            'isCorrect' => (bool)$o['isCorrect']
        ];
    }

    // 3. Zusammenbauen
    $res = [];
    foreach ($questions as $q) {
        $res[] = [
            'questionId' => (int)$q['questionID'],
            'quizID' => (int)$q['quizID'],
            'text' => $q['text'],
            'type' => $q['type'],
            'difficulty' => $q['difficulty'],
            'explanation' => $q['explanation'],
            'timeLimit' => (int)$q['timeLimit'],
            'options' => $byQ[$q['questionID']] ?? []
        ];
    }

    echo json_encode(['questions' => $res], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Fehler beim Laden der Katalogfragen', 'details' => $e->getMessage()]);
}
