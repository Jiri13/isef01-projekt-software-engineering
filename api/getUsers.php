<?php
// api/getUsers.php
// Zweck:
// Liefert eine Liste aller Benutzer für die Benutzerverwaltung.
// Dieser Endpoint wird ausschließlich im Admin-Bereich verwendet.
//
// Sicherheitskonzept:
// - Zugriff ist nur für eingeloggte Benutzer mit der Rolle "Admin" erlaubt
// - Normale Benutzer (Creator) erhalten keinen Zugriff auf diese Daten
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

session_start();
require __DIR__ . '/dbConnection.php';

// 1) Zugriffskontrolle: Nur Admins dürfen Benutzer abrufen
if (!isset($_SESSION['userID']) || ($_SESSION['user_role'] ?? '') !== 'Admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Not allowed']);
    exit;
}

try {
    // 2) Benutzer aus der Datenbank laden:
    // Es werden nur die für die Verwaltung relevanten Felder geladen:
    // - Keine Passwörter
    $stmt = $pdo->query("
        SELECT userID, first_name, last_name, email, user_role
        FROM users
        ORDER BY userID ASC
    ");
    // Alle Benutzer als assoziatives Array abrufen
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3) Ergebnis als JSON zurückgeben
    echo json_encode($users);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Could not fetch users',
        'details' => $e->getMessage()
    ]);
}
