<?php
// api/getQuestionsByDifficulty.php
// [WHY] Endpoint zum Abrufen von Fragen nach Schwierigkeit mit deren Antwortoptionen
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
    // 1: Fragen nach Schwierigkeit holen (mit passenden Aliasen)
    $stmt = $pdo->prepare("
        SELECT
            q.questionID AS questionId,
            q.quizID,
            q.question_text AS questionText,
            q.question_type AS questionType,
            q.difficulty,
            q.explanation,
            q.time_limit AS timeLimit
        FROM Question q
        WHERE q.difficulty = :diff
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

    // 2: Alle Frage-IDs sammeln und Optionen abrufen
    $ids = array_column($questions, 'questionId');
    $in = implode(',', array_fill(0, count($ids), '?'));

    $optStmt = $pdo->prepare("
        SELECT
            qo.questionID,
            qo.optionID AS optionId,
            qo.option_text AS optionText,
            qo.is_correct AS isCorrect
        FROM Question_Option qo
        WHERE qo.questionID IN ($in)
    ");
    $optStmt->execute($ids);
    $options = $optStmt->fetchAll(PDO::FETCH_ASSOC);

    // 3: Optionen nach Frage gruppieren
    $byQ = [];
    foreach ($options as $o) {
        $byQ[$o['questionID']][] = [
            'optionId' => (int)$o['optionId'],
            'optionText' => $o['optionText'],
            'isCorrect' => (bool)$o['isCorrect']
        ];
    }

    // 4: Endgültiges JSON aufbauen
    $res = [];
    foreach ($questions as $q) {
        $res[] = [
            'questionId'   => (int)$q['questionId'],
            'quizID'       => (int)$q['quizID'],
            'questionText' => $q['questionText'],
            'questionType' => $q['questionType'],
            'difficulty'   => $q['difficulty'],
            'explanation'  => $q['explanation'],
            'timeLimit'    => (int)$q['timeLimit'],
            'options'      => $byQ[$q['questionId']] ?? []
        ];
    }

    echo json_encode(['questions' => $res], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Fehler beim Laden der Katalogfragen',
        'details' => $e->getMessage()
    ]);
}
