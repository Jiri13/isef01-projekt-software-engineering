<?php
// api/deleteUser.php
// Löscht einen Benutzer im Admin-Bereich.
// Sicherheitsregeln:
// - Nur Admin darf Benutzer löschen
// - Admin darf sich nicht selbst löschen

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

session_start();
require __DIR__ . '/dbConnection.php';

// Berechtigung: Nur Admin
if (!isset($_SESSION['userID']) || ($_SESSION['user_role'] ?? '') !== 'Admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userID = (int)($input['userID'] ?? 0);

// Eingabevalidierung
if ($userID <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'userID required']);
    exit;
}

// Schutz: verhindern, dass Admin sich selbst löscht
if ($userID === (int)$_SESSION['userID']) {
    http_response_code(400);
    echo json_encode(['error' => 'Cannot delete yourself']);
    exit;
}

try {
    /**
     * Benutzer löschen
     * Hinweis: Falls in anderen Tabellen FK-Constraints existieren (z.B. statistics),
     * muss man entweder vorher abhängige Datensätze löschen oder FK-Regeln (ON DELETE ...)
     * passend definieren.
     */
    $stmt = $pdo->prepare("DELETE FROM users WHERE userID = :id");
    $stmt->execute([':id' => $userID]);

    echo json_encode(['ok' => true]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Delete user failed',
        'details' => $e->getMessage()
    ]);
}
