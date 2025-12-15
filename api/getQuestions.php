<?php
// api/getQuestions.php
// - Liefert alle Fragen inkl. Antwortoptionen in nur 2 DB-Abfragen (Batch Loading).
// - Enthält zusätzlich die Ersteller-Informationen (Vorname/Nachname) per LEFT JOIN auf `users`.
// Einsatz:
// - Fragenverwaltung im Frontend (Übersicht, Bearbeiten-Modal etc.)

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require __DIR__ . '/dbConnection.php';

try {
    /**
     * 1) Fragen laden (inkl. Ersteller)
     * - LEFT JOIN, damit Fragen auch dann geliefert werden, wenn der User nicht mehr existiert (NULL möglich).
     * - Sortierung: neueste zuerst.
     */
    $stmt = $pdo->query("
        SELECT
            q.questionID      AS id,
            q.question_text   AS text,
            q.question_type   AS type,
            q.difficulty,
            q.time_limit      AS timeLimit,
            q.explanation,
            q.quizID,
            q.userID,
            q.created_at      AS createdAt,
            u.first_name      AS creatorFirstName,
            u.last_name       AS creatorLastName
        FROM question q
        LEFT JOIN users u ON u.userID = q.userID
        ORDER BY q.created_at DESC, q.questionID DESC
    ");
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Wenn keine Fragen existieren, geben wir ein leeres Array zurück (kein Fehlerfall)
    if (empty($questions)) {
        echo json_encode([]);
        exit;
    }

    /**
     * 2) Alle questionIDs sammeln
     * - Wird für den Batch-Query auf `question_option` verwendet.
     */
    $questionIDs = array_column($questions, 'id');

    /**
     * 3) Alle Optionen zu allen geladenen Fragen in einer Abfrage holen
     * Hinweis:
     * - $idsForQuery wird aus Integern gebaut (array_map('intval')), um nur gültige IDs zu verwenden.
     * - Dadurch sind die Werte sauber numerisch, bevor sie in den IN()-Teil eingefügt werden.
     */
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

    /**
     * 4) Optionen im PHP-Speicher nach questionID gruppieren
     * - Ziel: Schneller Zugriff beim Zusammenbauen der finalen JSON-Struktur.
     */
    $optionsByQuestion = [];
    foreach ($allOptions as $opt) {
        $qID = (int)$opt['questionID'];
        unset($opt['questionID']); // redundant im JSON

        // Einheitlicher Datentyp für das Frontend (int statt string/bool)
        $opt['isCorrect'] = (int)$opt['isCorrect'];

        $optionsByQuestion[$qID][] = $opt;
    }

    // 5. Optionen + correctAnswer in das Fragen-Array einfügen
    foreach ($questions as &$q) {
        $q['id']        = (int)$q['id'];
        $q['timeLimit'] = (int)$q['timeLimit'];
        $q['quizID']    = $q['quizID'] ? (int)$q['quizID'] : null;
        $q['userID']    = (int)$q['userID'];

        // Ersteller Strings normalisieren (falls NULL)
        $q['creatorFirstName'] = isset($q['creatorFirstName']) ? (string)$q['creatorFirstName'] : '';
        $q['creatorLastName']  = isset($q['creatorLastName'])  ? (string)$q['creatorLastName']  : '';

        // Optionen zur Frage hinzufügen
        $qOptions = $optionsByQuestion[$q['id']] ?? [];
        $q['options'] = $qOptions;

         // Richtig-Information ableiten
        $correctIndex = -1;
        $correctText  = '';

        foreach ($qOptions as $idx => $opt) {
            if ((int)$opt['isCorrect'] === 1) {
                $correctIndex = $idx;
                $correctText  = (string)$opt['text'];
                break; // erste richtige Antwort reicht
            }
        }

        $type = strtolower((string)$q['type']);

        if ($type === 'text_input') {
            // Texteingabe: korrekter Antworttext wird als correctAnswer zurückgegeben
            $q['correctAnswer'] = $correctText;
        } else {
            // Multiple Choice / True-False: Index der richtigen Option (oder -1 falls keine markiert)
            $q['correctAnswer'] = $correctIndex;
        }
    }
    unset($q);
    // Antwort: vollständige Fragenliste als JSON
    echo json_encode($questions);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
}
?>
