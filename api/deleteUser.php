<?php
// api/deleteUser.php
//für Benutzerverwaltung im admin bereich
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

session_start();
require __DIR__ . '/dbConnection.php';

if (!isset($_SESSION['userID']) || ($_SESSION['user_role'] ?? '') !== 'Admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userID = (int)($input['userID'] ?? 0);

if ($userID <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'userID required']);
    exit;
}

// Optional: verhindern, dass Admin sich selbst löscht
if ($userID === (int)$_SESSION['userID']) {
    http_response_code(400);
    echo json_encode(['error' => 'Cannot delete yourself']);
    exit;
}

try {
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
