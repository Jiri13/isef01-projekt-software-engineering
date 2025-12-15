<?php
/**
 * api/addQuestion.php
 *
 * Zweck:
 * - Legt eine neue Frage inkl. optionaler Antwortoptionen an.
 * - Der Ersteller (userID) wird **ausschließlich** aus der Session übernommen (nicht aus dem Request).
 *
 * Sicherheit:
 * - Zugriff nur für eingeloggte Benutzer.
 * - Erlaubte Rollen: Creator, Admin.
 *
 * Request (JSON, POST):
 * {
 *   "quizID": 1,
 *   "text": "Fragetext",
 *   "type": "multiple_choice" | "text_input" | "true_false",
 *   "options": [ {"text":"A","isCorrect":false}, {"text":"B","isCorrect":true} ],
 *   "difficulty": "easy" | "medium" | "hard",
 *   "explanation": "optionale Erklärung",
 *   "timeLimit": 30
 * }
 *
 * Response (JSON):
 * - Erfolgreich: { "ok": true, "questionID": 123 }
 * - Fehler:      { "error": "..." }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

session_start();
require __DIR__ . '/dbConnection.php'; // erwartet $pdo (PDO)

// 1) Authentifizierung: Prüfen, ob eine gültige Session besteht
if (!isset($_SESSION['userID'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$currentUserID  = (int)$_SESSION['userID'];
$currentUserRole = $_SESSION['user_role'] ?? 'Creator';

// 2) Autorisierung: Rollenprüfung
if (!in_array($currentUserRole, ['Creator', 'Admin'], true)) {
    http_response_code(403);
    echo json_encode(['error' => 'Not allowed to create questions']);
    exit;
}

// 3) Request einlesen und validieren
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['error'=>'Invalid or missing JSON body']);
    exit;
}

//Pflicht-/Defaultwerte aus dem Request übernehmen
$quizID      = isset($input['quizID']) ? (int)$input['quizID'] : 0;

// userID kommt nicht aus dem Request (Manipulationsschutz),
// sondern immer aus der Session:
$userID      = $currentUserID;

$questionTxt = trim((string)($input['text'] ?? ''));
$type        = strtolower(trim((string)($input['type'] ?? 'multiple_choice')));
$difficulty  = strtolower(trim((string)($input['difficulty'] ?? 'medium')));
$explanation = trim((string)($input['explanation'] ?? ''));
$timeLimit   = isset($input['timeLimit']) ? (int)$input['timeLimit'] : 30;
// Options ist optional, muss aber ein Array sein, falls vorhanden
$options     = isset($input['options']) && is_array($input['options']) ? $input['options'] : [];

// Pflichtfelder prüfen
if ($quizID <= 0 || $questionTxt === '') {
    http_response_code(400);
    echo json_encode(['error' => 'quizID and text are required']);
    exit;
}

// 4) Persistenz: Frage + Optionen in einer Transaktion speichern
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

    $questionID = (int)$pdo->lastInsertId(); // Neu erzeugte ID der Frage

    // Antwortoptionen speichern (falls vorhanden):
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

    // 5) Erfolgsresponse
    echo json_encode([
        'ok' => true,
        'questionID' => $questionID
    ]);

} catch (Exception $e) {
    // Bei Fehlern Transaktion zurückrollen, um Teilzustände zu vermeiden
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Insert failed', 'details' => $e->getMessage()]);
}
