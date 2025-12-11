<?php
// api/getQuestions.php
// [OPTIMIZED] Lädt alle Fragen und Optionen in nur 2 Datenbank-Abfragen (Batch-Loading)

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require __DIR__ . '/dbConnection.php';

try {
    // 1. Alle Fragen laden
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

    if (empty($questions)) {
        echo json_encode([]);
        exit;
    }

    // 2. IDs aller geladenen Fragen sammeln
    $questionIDs = array_column($questions, 'id');

    // 3. Alle Optionen zu diesen Fragen in EINER Abfrage laden
    $idsForQuery = implode(',', array_map('intval', $questionIDs));
    $optStmt = $pdo->query("
        SELECT
            optionID      AS id,
            questionID,
            option_text   AS text,
            is_correct    AS isCorrect
        FROM question_option
        WHERE questionID IN ($idsForQuery)
        ORDER BY optionID ASC
    ");
    $allOptions = $optStmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Optionen den Fragen zuordnen (im PHP-Speicher)
    $optionsByQuestion = [];
    foreach ($allOptions as $opt) {
        $qID = $opt['questionID'];
        unset($opt['questionID']); // redundant im JSON

        // isCorrect als int
        $opt['isCorrect'] = (int)$opt['isCorrect'];

        $optionsByQuestion[$qID][] = $opt;
    }

    // 5. Optionen + correctAnswer in das Fragen-Array einfügen
    foreach ($questions as &$q) {
        $q['id']        = (int)$q['id'];
        $q['timeLimit'] = (int)$q['timeLimit'];
        $q['quizID']    = $q['quizID'] ? (int)$q['quizID'] : null;
        $q['userID']    = (int)$q['userID'];

        $qOptions = $optionsByQuestion[$q['id']] ?? [];
        $q['options'] = $qOptions;

        $correctIndex = -1;
        $correctText  = '';

        foreach ($qOptions as $idx => $opt) {
            if ($opt['isCorrect'] === 1) {
                $correctIndex = $idx;
                $correctText  = $opt['text'];
                break; // erste richtige Antwort reicht
            }
        }

        $type = strtolower((string)$q['type']);

        if ($type === 'text_input') {
            // Für Texteingabe geben wir den TEXT der richtigen Antwort zurück
            $q['correctAnswer'] = $correctText;
        } else {
            // Für Multiple Choice / True-False geben wir wie bisher den Index zurück
            $q['correctAnswer'] = $correctIndex;
        }
    }
    unset($q); // Referenz aufheben

    echo json_encode($questions);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
}
?>
