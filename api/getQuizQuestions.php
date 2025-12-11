<?php
// api/getQuizQuestions.php
// [WHY] Liefert alle Fragen + Antwortoptionen für ein bestimmtes Quiz (Einzelspielermodus)

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require __DIR__ . '/dbConnection.php';

// quizID auslesen
$quizID = isset($_GET['quizID']) ? (int)$_GET['quizID'] : 0;

if ($quizID <= 0) {
    echo json_encode(['questions' => []]);
    exit;
}

try {
    // 1. Fragen zu diesem Quiz holen
    $stmt = $pdo->prepare("
        SELECT
            q.questionID    AS questionId,
            q.question_text AS questionText,
            q.question_type AS questionType,
            q.difficulty    AS difficulty,
            q.explanation   AS explanation,
            q.time_limit    AS timeLimit
        FROM question q
        INNER JOIN quizquestion qq ON q.questionID = qq.questionID
        WHERE qq.quizID = :quizID
        ORDER BY q.questionID ASC
    ");
    $stmt->execute([':quizID' => $quizID]);

    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$questions) {
        echo json_encode(['questions' => []]);
        exit;
    }

    // 2. IDs der Fragen sammeln
    $ids = array_column($questions, 'questionId');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    // 3. Optionen zu diesen Fragen holen
    $optStmt = $pdo->prepare("
        SELECT
            qo.questionID      AS questionId,
            qo.optionID        AS optionId,
            qo.option_text     AS optionText,
            qo.is_correct      AS isCorrect
        FROM question_option qo
        WHERE qo.questionID IN ($placeholders)
        ORDER BY qo.optionID ASC
    ");
    foreach ($ids as $i => $id) {
        $optStmt->bindValue($i + 1, (int)$id, PDO::PARAM_INT);
    }
    $optStmt->execute();

    $options = $optStmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Optionen nach questionId gruppieren
    $byQuestion = [];
    foreach ($options as $opt) {
        $qid = (int)$opt['questionId'];
        if (!isset($byQuestion[$qid])) {
            $byQuestion[$qid] = [];
        }
        $byQuestion[$qid][] = [
            'optionId'   => (int)$opt['optionId'],
            'optionText' => $opt['optionText'],
            'isCorrect'  => (bool)$opt['isCorrect'],
        ];
    }

    // 5. Endgültige Struktur bauen
    $result = [];
    foreach ($questions as $q) {
        $qid = (int)$q['questionId'];
        $result[] = [
            'questionId'   => $qid,
            'questionText' => $q['questionText'],
            'questionType' => $q['questionType'],
            'difficulty'   => $q['difficulty'],
            'explanation'  => $q['explanation'],
            'timeLimit'    => (int)$q['timeLimit'],
            'options'      => $byQuestion[$qid] ?? []
        ];
    }

    echo json_encode(['questions' => $result]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error'   => 'Database error',
        'details' => $e->getMessage()
    ]);
}
