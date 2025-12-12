<?php
// api/getUsers.php
//für Benutzerverwaltung im admin bereich
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

session_start();
require __DIR__ . '/dbConnection.php';

// Optional: nur Admin darf Benutzer sehen
if (!isset($_SESSION['userID']) || ($_SESSION['user_role'] ?? '') !== 'Admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Not allowed']);
    exit;
}

try {
    $stmt = $pdo->query("
        SELECT userID, first_name, last_name, email, user_role
        FROM users
        ORDER BY userID ASC
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($users);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Could not fetch users',
        'details' => $e->getMessage()
    ]);
}
