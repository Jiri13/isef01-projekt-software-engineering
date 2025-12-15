<?php
// api/updateQuestion.php
// Aktualisiert eine bestehende Frage inklusive aller Antwortoptionen.
// Die Bearbeitung ist ausschließlich dem Ersteller der Frage oder einem Admin erlaubt.

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

session_start();
require __DIR__ . '/dbConnection.php';

// JSON-Daten vom Frontend lesen
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing JSON body']);
    exit;
}

// Eingabedaten extrahieren und normalisieren
$questionID  = isset($input['questionID']) ? (int)$input['questionID'] : 0;
$text        = trim((string)($input['text'] ?? ''));
$type        = strtolower(trim((string)($input['type'] ?? 'multiple_choice')));
$difficulty  = strtolower(trim((string)($input['difficulty'] ?? 'medium')));
$explanation = trim((string)($input['explanation'] ?? ''));
$timeLimit   = isset($input['timeLimit']) ? (int)$input['timeLimit'] : 30;
$options     = isset($input['options']) && is_array($input['options']) ? $input['options'] : [];

if ($questionID <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'questionID required']);
    exit;
}

// Session-User prüfen
if (!isset($_SESSION['userID'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$currentUserID   = (int)$_SESSION['userID'];
$currentUserRole = $_SESSION['user_role'] ?? 'Creator';

try {
    // Eigentümer der Frage ermitteln
    $stmtOwner = $pdo->prepare("SELECT userID FROM question WHERE questionID = :qid");
    $stmtOwner->execute([':qid' => $questionID]);
    $owner = $stmtOwner->fetch(PDO::FETCH_ASSOC);

    if (!$owner) {
        http_response_code(404);
        echo json_encode(['error' => 'Question not found']);
        exit;
    }

    $ownerID = (int)$owner['userID'];

    // Berechtigung prüfen: Ersteller oder Admin
    if ($ownerID !== $currentUserID && $currentUserRole !== 'Admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Not allowed to update this question']);
        exit;
    }

    //Transaktion starten (Frage + Optionen konsistent aktualisieren)
    $pdo->beginTransaction();

    // Frage aktualisieren
    $stmt = $pdo->prepare("
        UPDATE question
        SET question_text = :text,
            question_type = :type,
            difficulty = :difficulty,
            explanation = :explanation,
            time_limit = :timeLimit
        WHERE questionID = :questionID
    ");
    $stmt->execute([
        ':text'        => $text,
        ':type'        => $type,
        ':difficulty'  => $difficulty,
        ':explanation' => $explanation,
        ':timeLimit'   => $timeLimit,
        ':questionID'  => $questionID
    ]);

    // Alte Optionen löschen
    $pdo->prepare("DELETE FROM question_option WHERE questionID = :qid")
        ->execute([':qid' => $questionID]);

    // Neue Optionen einfügen
    if (!empty($options)) {
        $optStmt = $pdo->prepare("
            INSERT INTO question_option (questionID, option_text, is_correct)
            VALUES (:questionID, :text, :isCorrect)
        ");
        foreach ($options as $opt) {
            $optText = trim((string)($opt['text'] ?? ''));
            if ($optText === '') continue; // leere Optionen überspringen

            $isCorrect = !empty($opt['isCorrect']) ? 1 : 0;
            $optStmt->execute([
                ':questionID' => $questionID,
                ':text'       => $optText,
                ':isCorrect'  => $isCorrect
            ]);
        }
    }

    // Transaktion abschließen
    $pdo->commit();

    echo json_encode(['ok' => true, 'questionID' => $questionID]);

} catch (Exception $e) {
    // Fehlerbehandlung + Rollback
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'error' => 'Update failed',
        'details' => $e->getMessage()
    ]);
}
