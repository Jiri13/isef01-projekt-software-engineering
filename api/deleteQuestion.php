<?php
// api/deleteQuestion.php
// Löscht eine Frage inklusive aller zugehörigen Antwortoptionen.
// Berechtigung: Nur der Ersteller der Frage ODER ein Admin darf löschen.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

session_start();
require __DIR__ . '/dbConnection.php';

$input = json_decode(file_get_contents('php://input'), true);
$questionID = isset($input['questionID']) ? (int)$input['questionID'] : 0;

// Eingabevalidierung
if ($questionID <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'questionID required']);
    exit;
}
// Login erforderlich
if (!isset($_SESSION['userID'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}
// Aktuellen Benutzer aus der Session bestimmen
$currentUserID   = (int)$_SESSION['userID'];
$currentUserRole = $_SESSION['user_role'] ?? 'Creator';

try {
    /**
     * 1) Ersteller der Frage aus der DB laden
     *    => wird für die Berechtigungsprüfung benötigt
     */
    $stmtOwner = $pdo->prepare("SELECT userID FROM question WHERE questionID = :qid");
    $stmtOwner->execute([':qid' => $questionID]);
    $owner = $stmtOwner->fetch(PDO::FETCH_ASSOC);

    // Frage existiert nicht
    if (!$owner) {
        http_response_code(404);
        echo json_encode(['error' => 'Question not found']);
        exit;
    }

    $ownerID = (int)$owner['userID'];

    /**
     * 2) Berechtigung prüfen
     *    - Admin darf alles löschen
     *    - Creator darf nur eigene Fragen löschen
     */
    if ($ownerID !== $currentUserID && $currentUserRole !== 'Admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Not allowed to delete this question']);
        exit;
    }

    /**
     * 3) Löschvorgang in einer Transaktion ausführen
     *    => stellt sicher: entweder alles wird gelöscht oder nichts (Konsistenz)
     */
    $pdo->beginTransaction();

    // Zuerst abhängige Datensätze entfernen (Antwortoptionen)
    $pdo->prepare("DELETE FROM question_option WHERE questionID = :qid")
        ->execute([':qid' => $questionID]);

    // Danach die Frage selbst entfernen
    $pdo->prepare("DELETE FROM question WHERE questionID = :qid")
        ->execute([':qid' => $questionID]);

    $pdo->commit();

    // Erfolgsantwort
    echo json_encode(['ok' => true, 'deletedID' => $questionID]);

} catch (Throwable $e) {
    // Bei Fehlern Transaktion sauber zurückrollen
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'error' => 'Delete failed',
        'details' => $e->getMessage()
    ]);
}
