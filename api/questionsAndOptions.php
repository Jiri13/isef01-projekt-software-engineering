<?php
// api/questionsAndOptions.php
// Gibt alle Fragen und deren Antwortoptionen zurück.
// Optional: Filter nach quizID (?quizID=123)

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require __DIR__ . '/dbConnection.php';

try {
    // Optional: Filter nach quizID (wenn Frontend das irgendwann ergänzt)
    $quizID = isset($_GET['quizID']) ? (int)$_GET['quizID'] : 0;

    // Basis-SQL
    $sql = "SELECT q.questionID, q.quizID, q.question_text, q.questionType, 
                   q.difficulty, q.explanation, q.created_at
            FROM Question q";
    $params = [];

    if ($quizID > 0) {
        $sql .= " WHERE q.quizID = :quizID";
        $params[':quizID'] = $quizID;
    }

    $sql .= " ORDER BY q.questionID ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Wenn keine Fragen gefunden
    if (!$questions) {
        echo json_encode([]);
        exit;
    }

    // Alle Frage-IDs für das Nachladen der Antwortoptionen
    $questionIDs = array_column($questions, 'questionID');

    // Optionen abrufen
    $in = implode(',', array_fill(0, count($questionIDs), '?'));
    $optStmt = $pdo->prepare("
        SELECT optionID, questionID, option_text, is_correct, explanation
        FROM Question_Option
        WHERE questionID IN ($in)
        ORDER BY optionID ASC
    ");
    $optStmt->execute($questionIDs);
    $options = $optStmt->fetchAll(PDO::FETCH_ASSOC);

    // Optionen nach questionID gruppieren
    $optionsByQ = [];
    foreach ($options as $opt) {
        $qid = $opt['questionID'];
        $optionsByQ[$qid][] = [
            'optionID'   => (int)$opt['optionID'],
            'text'       => $opt['option_text'],
            'isCorrect'  => (bool)$opt['is_correct'],
            'explanation'=> $opt['explanation']
        ];
    }

    // Fragen + Optionen zusammenbauen
    $result = [];
    foreach ($questions as $q) {
        $qid = $q['questionID'];
        $result[] = [
            'questionID'  => (int)$q['questionID'],
            'quizID'      => (int)$q['quizID'],
            'question_text' => $q['question_text'],
            'questionType'  => $q['questionType'],
            'difficulty'    => $q['difficulty'],
            'explanation'   => $q['explanation'],
            'created_at'    => $q['created_at'],
            'options'       => $optionsByQ[$qid] ?? []
        ];
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch questions', 'details' => $e->getMessage()]);
}

