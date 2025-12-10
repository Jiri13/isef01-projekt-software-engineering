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

    // Sicherstellen, dass wir Integer für das IN-Statement haben
    $idsForQuery = implode(',', array_map('intval', $questionIDs));

    // 3. Alle Optionen für diese Fragen in EINER Abfrage laden
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
    // Wir gruppieren die Optionen zuerst nach questionID
    $optionsByQuestion = [];
    foreach ($allOptions as $opt) {
        $qID = $opt['questionID'];
        // Wir entfernen questionID aus dem Option-Objekt selbst, da es redundant ist
        unset($opt['questionID']);

        // Datentypen anpassen (für sauberes JSON)
        $opt['isCorrect'] = (int)$opt['isCorrect'];

        $optionsByQuestion[$qID][] = $opt;
    }

    // Optionen in das Fragen-Array einfügen
    foreach ($questions as &$q) {
        $q['id'] = (int)$q['id'];
        $q['timeLimit'] = (int)$q['timeLimit'];
        $q['quizID'] = $q['quizID'] ? (int)$q['quizID'] : null;
        $q['userID'] = (int)$q['userID'];

        // Optionen zuweisen oder leeres Array
        $q['options'] = $optionsByQuestion[$q['id']] ?? [];

        // CorrectAnswer Index berechnen (für Frontend-Komfort)
        $correctIndex = -1;
        foreach ($q['options'] as $idx => $opt) {
            if ($opt['isCorrect'] === 1) {
                $correctIndex = $idx;
                break; // Erste richtige Antwort reicht für Index
            }
        }
        $q['correctAnswer'] = $correctIndex;
    }
    unset($q); // Referenz aufheben

    echo json_encode($questions);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
}
?>