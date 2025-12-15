<?php
// api/getQuizQuestions.php
// Zweck:
// Liefert alle Fragen inkl. Antwortoptionen zu einem bestimmten Quiz.
// Wird u. a. im Einzelspielermodus und in der Quiz-Bearbeitung verwendet.

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require __DIR__ . '/dbConnection.php';

// 1) Eingabe lesen und validieren:
// quizID auslesen
$quizID = isset($_GET['quizID']) ? (int)$_GET['quizID'] : 0;

// Wenn keine gültige ID übergeben wurde: leere Liste zurückgeben
if ($quizID <= 0) {
    echo json_encode(['questions' => []]);
    exit;
}

try {
    //  2) Fragen zu diesem Quiz laden
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

    // Wenn keine Fragen zugeordnet sind: leere Liste zurückgeben
    if (!$questions) {
        echo json_encode(['questions' => []]);
        exit;
    }

    // 3) Alle questionIDs sammeln, um Optionen in einem Batch zu laden
    $ids = array_column($questions, 'questionId');

     // Für PDO-Prepared-Statements mit IN (...) braucht man passende Platzhalter
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    // 4) Optionen zu allen Fragen in einer Abfrage holen
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
    // Platzhalter mit den questionIDs befüllen (1-basiert)
    foreach ($ids as $i => $id) {
        $optStmt->bindValue($i + 1, (int)$id, PDO::PARAM_INT);
    }
    $optStmt->execute();

    $options = $optStmt->fetchAll(PDO::FETCH_ASSOC);

    // 5) Optionen nach questionId gruppieren (für schnelleres Zusammenbauen)
    $byQuestion = [];
    foreach ($options as $opt) {
        $qid = (int)$opt['questionId'];
        if (!isset($byQuestion[$qid])) {
            $byQuestion[$qid] = [];
        }
        // Typen sauber casten (int/bool), damit das JSON konsistent ist
        $byQuestion[$qid][] = [
            'optionId'   => (int)$opt['optionId'],
            'optionText' => $opt['optionText'],
            'isCorrect'  => (bool)$opt['isCorrect'],
        ];
    }

    // 6) Endgültige Response-Struktur bauen (Fragen + Optionen)
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
