<?php
// api/getQuizQuestions.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require __DIR__ . '/dbConnection.php';

$quizID = isset($_GET['quizID']) ? (int)$_GET['quizID'] : 0;
if ($quizID <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'quizID required']);
    exit;
}

try {
    // 1. Fragen für dieses Quiz holen
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
        WHERE quizID = :quizID
        ORDER BY questionID ASC
    ");
    $stmt->execute([':quizID' => $quizID]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$questions) {
        echo json_encode(['questions' => []]);
        exit;
    }

    // 2. Alle Optionen zu diesen Fragen holen
    $ids = array_column($questions, 'questionID');
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $optStmt = $pdo->prepare("
        SELECT questionID, option_text AS text, is_correct AS isCorrect
        FROM Question_Option
        WHERE questionID IN ($in)
        ORDER BY optionID ASC
    ");
    $optStmt->execute($ids);
    $opts = $optStmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Optionen nach Frage gruppieren
    $grouped = [];
    foreach ($opts as $o) {
        $grouped[$o['questionID']][] = [
            'text' => $o['text'],
            'isCorrect' => (bool)$o['isCorrect']
        ];
    }

    // 4. Ergebnis zusammenbauen
    $result = [];
    foreach ($questions as $q) {
        $result[] = [
            'questionId' => (int)$q['questionID'],
            'quizID' => (int)$q['quizID'],
            'text' => $q['text'],
            'type' => $q['type'],
            'difficulty' => $q['difficulty'],
            'explanation' => $q['explanation'],
            'timeLimit' => (int)$q['timeLimit'],
            'options' => $grouped[$q['questionID']] ?? []
        ];
    }

    echo json_encode(['questions' => $result], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Fehler beim Laden der Fragen', 'details' => $e->getMessage()]);
}
