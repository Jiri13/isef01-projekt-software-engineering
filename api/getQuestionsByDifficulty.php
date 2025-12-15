<?php
// api/getQuestionsByDifficulty.php
// [WHY] Liefert Fragen + Antwortoptionen nach Schwierigkeitsgrad für den Einzelspielermodus (Katalog)

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require __DIR__ . '/dbConnection.php';

/**
 * Parameter auslesen und validieren
 * - difficulty: erwartete Werte: easy|medium|hard (Default: easy)
 * - limit: Anzahl der Fragen (Default: 20)
 */
$difficultyParam = isset($_GET['difficulty']) ? strtolower(trim($_GET['difficulty'])) : 'easy';
$limitParam      = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

// Sicherheits-Validierung: nur erlaubte Werte akzeptieren
if (!in_array($difficultyParam, ['easy', 'medium', 'hard'], true)) {
    $difficultyParam = 'easy';
}
if ($limitParam <= 0) {
    $limitParam = 20;
}

// DB-Enum: 'Easy','Medium','Hard'
$difficultyDb = ucfirst($difficultyParam);

try {
    /**
     * 1) Fragen mit gewünschter Difficulty laden
     * - ORDER BY RAND() sorgt für zufällige Reihenfolge (für kleine Datenmengen ok).
     * - LIMIT begrenzt die Anzahl der Fragen.
     */
    $stmt = $pdo->prepare("
        SELECT
            q.questionID    AS questionId,
            q.question_text AS questionText,
            q.question_type AS questionType,
            q.difficulty    AS difficulty,
            q.explanation   AS explanation,
            q.time_limit    AS timeLimit
        FROM question q
        WHERE q.difficulty = :difficulty
        ORDER BY RAND()
        LIMIT :limit
    ");

    $stmt->bindValue(':difficulty', $difficultyDb, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limitParam, PDO::PARAM_INT);
    $stmt->execute();

    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Keine passenden Fragen gefunden -> leeres Ergebnis zurückgeben
    if (!$questions) {
        echo json_encode(['questions' => []]);
        exit;
    }

    /**
     * 2) IDs der geladenen Fragen sammeln
     * - Damit wir im nächsten Schritt alle Optionen in EINER Abfrage laden können.
     */
    $ids = array_column($questions, 'questionId');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    /**
     * 3) Optionen zu allen geladenen Fragen in einer Abfrage holen
     * - WHERE IN (...) mit dynamischen Placeholders.
     */
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

    /**
     * 4) Optionen nach questionId gruppieren
     * - Ziel: Schnelle Zuordnung der Optionen zur jeweiligen Frage im nächsten Schritt.
     */
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

    /**
     * 5) Finales Response-Objekt bauen
     * - timeLimit wird auf int normalisiert
     * - options werden ergänzt
     */
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
