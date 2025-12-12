<?php
// api/addQuestion.php
// Endpoint zum Anlegen einer Frage. Unterstützt Antwortoptionen-Array.
// Liefert questionID zurück.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

session_start();
require __DIR__ . '/dbConnection.php'; // erwartet $pdo (PDO)

// Prüfen, ob eingeloggt
if (!isset($_SESSION['userID'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$currentUserID  = (int)$_SESSION['userID'];
$currentUserRole = $_SESSION['user_role'] ?? 'Creator';

// Nur Creator und Admin dürfen Fragen anlegen
if (!in_array($currentUserRole, ['Creator', 'Admin'], true)) {
    http_response_code(403);
    echo json_encode(['error' => 'Not allowed to create questions']);
    exit;
}

// JSON einlesen
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['error'=>'Invalid or missing JSON body']);
    exit;
}

/*
 Erwartetes JSON vom Frontend:
 {
   "quizID": 1,
   "text": "Fragetext",
   "type": "multiple_choice" | "text_input" | "true_false",
   "options": [ {"text":"A","isCorrect":false}, {"text":"B","isCorrect":true} ],
   "difficulty": "easy" | "medium" | "hard",
   "explanation": "optionale Erklärung",
   "timeLimit": 30
 }
*/

//Werte aus dem JSON herausziehen und Standardwerte setzen
$quizID      = isset($input['quizID']) ? (int)$input['quizID'] : 0;
// userID kommt jetzt NICHT mehr aus dem Input, sondern aus Session:
$userID      = $currentUserID;

$questionTxt = trim((string)($input['text'] ?? ''));
$type        = strtolower(trim((string)($input['type'] ?? 'multiple_choice')));
$difficulty  = strtolower(trim((string)($input['difficulty'] ?? 'medium')));
$explanation = trim((string)($input['explanation'] ?? ''));
$timeLimit   = isset($input['timeLimit']) ? (int)$input['timeLimit'] : 30;
$options     = isset($input['options']) && is_array($input['options']) ? $input['options'] : [];

// Pflichtfelder prüfen
if ($quizID <= 0 || $questionTxt === '') {
    http_response_code(400);
    echo json_encode(['error' => 'quizID and text are required']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Frage speichern
    $stmt = $pdo->prepare("
        INSERT INTO question
            (quizID, question_text, question_type, difficulty, explanation, time_limit, userID, created_at)
        VALUES
            (:quizID, :text, :type, :difficulty, :explanation, :timeLimit, :userID, :created_at)
    ");
    $stmt->execute([
        ':quizID'      => $quizID,
        ':text'        => $questionTxt,
        ':type'        => $type,
        ':difficulty'  => $difficulty,
        ':explanation' => $explanation,
        ':timeLimit'   => $timeLimit,
        ':userID'      => $userID,
        ':created_at'  => date('Y-m-d H:i:s')
    ]);

    $questionID = (int)$pdo->lastInsertId();

    //  Optionen jetzt für ALLE Fragetypen speichern, sofern vorhanden
    // multiple_choice  -> mehrere Optionen
    // true_false       -> 2 Optionen (Wahr/Falsch)
    // text_input       -> 1 Option = richtige Antwort
    if (!empty($options)) {
        $optStmt = $pdo->prepare("
            INSERT INTO question_option (questionID, option_text, is_correct)
            VALUES (:questionID, :text, :isCorrect)
        ");
        foreach ($options as $opt) {
            $optText = trim((string)($opt['text'] ?? ''));
            if ($optText === '') continue;

            $isCorrect = !empty($opt['isCorrect']) ? 1 : 0;
            $optStmt->execute([
                ':questionID' => $questionID,
                ':text'       => $optText,
                ':isCorrect'  => $isCorrect
            ]);
        }
    }

    $pdo->commit();

    echo json_encode([
        'ok' => true,
        'questionID' => $questionID
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Insert failed', 'details' => $e->getMessage()]);
}
