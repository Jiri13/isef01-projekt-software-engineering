<?php
// api/addQuiz.php
// Legt ein neues Quiz an und ordnet (optional) vorhandene Fragen über die
// Beziehungstabelle `quizquestion` zu.

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Preflight für CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require __DIR__ . '/dbConnection.php';

// JSON-Body lesen
$rawBody = file_get_contents('php://input');
$input   = json_decode($rawBody, true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode([
        'ok'    => false,
        'error' => 'Invalid or missing JSON body',
        'body'  => $rawBody
    ]);
    exit;
}

/*
Erwartet vom Frontend (CreateQuizModal.vue -> createQuiz()):

{
  "userID": 1,
  "title": "Name des Quiz",
  "category": "Modul/Fach",
  "description": "Beschreibung",
  "timeLimit": 30,          // optional (Integer oder null)

  // ENTWEDER so:
  "questionIDs": [3, 8, 11]

  // ODER (aktuelles Frontend):
  "questions": [3, 8, 11]
}
*/

$title       = trim((string)($input['title'] ?? ''));
$category    = trim((string)($input['category'] ?? ''));
$description = trim((string)($input['description'] ?? ''));
$userID      = isset($input['userID']) ? (int)$input['userID'] : 0;

$timeLimit   = (isset($input['timeLimit']) && $input['timeLimit'] !== '')
    ? (int)$input['timeLimit']
    : null;

// Frage-IDs aus dem Frontend holen:
// bevorzugt "questionIDs", Fallback auf "questions"
$questionIDsRaw = $input['questionIDs'] ?? $input['questions'] ?? [];

// Sicherstellen, dass es wirklich ein Array ist
if (!is_array($questionIDsRaw)) {
    $questionIDsRaw = [];
}

// Nur gültige Integer-IDs > 0 übernehmen
$questionIDs = array_values(
    array_filter(
        array_map('intval', $questionIDsRaw),
        fn($v) => $v > 0
    )
);

// Pflichtfelder prüfen
if ($title === '' || $category === '' || $description === '' || $userID <= 0) {
    http_response_code(400);
    echo json_encode([
        'ok'    => false,
        'error' => 'title, category, description and userID are required',
        'data'  => [
            'title'       => $title,
            'category'    => $category,
            'description' => $description,
            'userID'      => $userID
        ]
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    // Quiz anlegen (Tabelle: quiz, Spalten klein)
    $sql = "
        INSERT INTO quiz (title, quiz_description, time_limit, category, userID, created_at)
        VALUES (:title, :descr, :tl, :cat, :userID, :created)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':title'   => $title,
        ':descr'   => $description,
        ':tl'      => $timeLimit,              // darf NULL sein
        ':cat'     => $category,
        ':userID'  => $userID,
        ':created' => date('Y-m-d H:i:s')
    ]);

    // Auto-Increment-ID des neuen Quiz
    $quizID = (int)$pdo->lastInsertId();

    // Ausgewählte Fragen diesem Quiz zuordnen (wenn angegeben)
    if (!empty($questionIDs)) {
        $ins = $pdo->prepare("
            INSERT INTO quizquestion (quizID, questionID, sort_order)
            VALUES (:quizID, :questionID, :sort_order)
        ");

        $pos = 1;
        foreach ($questionIDs as $qid) {
            $ins->execute([
                ':quizID'     => $quizID,
                ':questionID' => $qid,
                ':sort_order' => $pos++
            ]);
        }
    }

    $pdo->commit();

    echo json_encode([
        'ok'     => true,
        'quizID' => $quizID
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'ok'      => false,
        'error'   => 'Insert failed',
        'details' => $e->getMessage()
    ]);
}
