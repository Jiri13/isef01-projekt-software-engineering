<?php
// api/deleteQuiz.php
// Löscht ein Quiz und alle zugehörigen Zuordnungen in `quizquestion`.
// Berechtigung:
// - Admin darf jedes Quiz löschen
// - Creator darf nur selbst erstellte Quizze löschen
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

session_start();
require __DIR__ . '/dbConnection.php';

// Login erforderlich
if (empty($_SESSION['userID'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

// Aktuellen Benutzer aus der Session bestimmen
$me = (int)$_SESSION['userID'];
$role = (string)($_SESSION['user_role'] ?? '');

$input = json_decode(file_get_contents('php://input'), true);
$quizID = (int)($input['quizID'] ?? 0);

// Eingabevalidierung
if ($quizID <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'quizID required']);
    exit;
}

try {
    /**
     * 1) Eigentümer (Ersteller) des Quiz aus der DB laden
     *    → wird für die Berechtigungsprüfung benötigt
     */
    $ownStmt = $pdo->prepare("SELECT userID FROM quiz WHERE quizID = :id LIMIT 1");
    $ownStmt->execute([':id' => $quizID]);
    $row = $ownStmt->fetch(PDO::FETCH_ASSOC);

    // Quiz existiert nicht
    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'Quiz not found']);
        exit;
    }

    $ownerID = (int)$row['userID'];

    /**
     * 2) Berechtigung prüfen
     *    - Admin darf löschen
     *    - Nicht-Admin nur, wenn er Owner (Ersteller) ist
     */
    if ($role !== 'Admin' && $ownerID !== $me) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden: not owner']);
        exit;
    }

    /**
     * 3) Löschvorgang in einer Transaktion ausführen
     *    → stellt sicher: entweder alles wird gelöscht oder nichts
     */
    $pdo->beginTransaction();

    // Erst Zuordnungen entfernen (Beziehungstabelle)
    $pdo->prepare("DELETE FROM quizquestion WHERE quizID = :q")
        ->execute([':q' => $quizID]);

    // Dann das Quiz selbst löschen
    $pdo->prepare("DELETE FROM quiz WHERE quizID = :q")
        ->execute([':q' => $quizID]);

    $pdo->commit();

    // Erfolgsantwort
    echo json_encode(['ok' => true]);

} catch (Throwable $e) {
    // Bei Fehlern Transaktion sauber zurückrollen
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Delete failed', 'details' => $e->getMessage()]);
}
