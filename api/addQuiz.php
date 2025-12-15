<?php
/**
 * api/addQuiz.php
 *
 * Zweck:
 * - Legt ein neues Quiz in der Tabelle `quiz` an.
 * - Ordnet optional vorhandene Fragen über die Beziehungstabelle `quizquestion` zu.
 *
 * Sicherheit:
 * - Zugriff nur für eingeloggte Benutzer (Session erforderlich).
 * - Der Besitzer/Ersteller (userID) wird **immer** aus der Session übernommen.
 *
 * Request (JSON, POST):
 * {
 *   "title": "Name des Quiz",
 *   "category": "Modul/Fach",
 *   "description": "Beschreibung",
 *   "timeLimit": 30,           // optional (Integer oder null)
 *   "questionIDs": [3, 8, 11]  // optional
 *   // oder alternativ:
 *   "questions": [3, 8, 11]
 * }
 *
 * Response (JSON):
 * - Erfolgreich: { "ok": true, "quizID": 123 }
 * - Fehler:      { "ok": false, "error": "..." }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Preflight für CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

session_start();

require __DIR__ . '/dbConnection.php';

// 1) Authentifizierung: Login/Sitzung erforderlich
if (empty($_SESSION['userID'])) {
    http_response_code(401);
    echo json_encode([
        'ok' => false,
        'error' => 'Not logged in'
    ]);
    exit;
}

// 2) Request einlesen und validieren
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

// Pflicht-/Defaultwerte aus dem Request übernehmen
$title       = trim((string)($input['title'] ?? ''));
$category    = trim((string)($input['category'] ?? ''));
$description = trim((string)($input['description'] ?? ''));

// userID kommt nicht aus dem Request (Manipulationsschutz),
// sondern immer aus der Session:
$userID = (int)$_SESSION['userID'];

// Optional: Zeitlimit (NULL erlaubt)
$timeLimit   = (isset($input['timeLimit']) && $input['timeLimit'] !== '' && $input['timeLimit'] !== null)
    ? (int)$input['timeLimit']
    : null;

// Frage-IDs aus dem Frontend holen (beide Varianten unterstützen)
$questionIDsRaw = $input['questionIDs'] ?? $input['questions'] ?? [];

// Absichern: muss ein Array sein
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
if ($title === '' || $category === '' || $description === '') {
    http_response_code(400);
    echo json_encode([
        'ok'    => false,
        'error' => 'title, category and description are required',
        'data'  => [
            'title'       => $title,
            'category'    => $category,
            'description' => $description
        ]
    ]);
    exit;
}

// 3) Persistenz: Quiz + optionale Zuordnungen in Transaktion
try {
    $pdo->beginTransaction();

    // Quiz anlegen
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

     // Neue Quiz-ID
    $quizID = (int)$pdo->lastInsertId();

    // Fragen zuordnen (wenn angegeben)
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

    // 4) Erfolgsresponse
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
