<?php
// api/updateUser.php
// Zweck:
// Aktualisiert einen bestehenden Benutzer (Name, E-Mail, Rolle) in der Benutzerverwaltung.
// Optional kann auch das Passwort geändert werden.
// Hinweis: Diese Aktion ist ausschließlich Admins erlaubt.

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

session_start();
require __DIR__ . '/dbConnection.php';

// Berechtigungsprüfung: Nur Admin darf Benutzer bearbeiten
if (!isset($_SESSION['userID']) || ($_SESSION['user_role'] ?? '') !== 'Admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Not allowed']);
    exit;
}

// Request-Body einlesen und Eingaben vorbereiten
$input = json_decode(file_get_contents('php://input'), true);

$userID    = (int)($input['userID'] ?? 0);
$firstName = trim($input['first_name'] ?? '');
$lastName  = trim($input['last_name'] ?? '');
$email     = trim($input['email'] ?? '');
$role      = trim($input['user_role'] ?? '');
$password  = (string)($input['password'] ?? ''); // optional

// Pflichtfelder validieren
if ($userID <= 0 || $firstName === '' || $email === '' || $role === '') {
    http_response_code(400);
    echo json_encode(['error' => 'userID, first_name, email and user_role are required']);
    exit;
}

// Rollen-Whitelist: Nur diese Rollen sind zulässig
$allowedRoles = ['Creator', 'Admin'];
if (!in_array($role, $allowedRoles, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid role']);
    exit;
}

try {
    // E-Mail-Duplikat prüfen (andere Nutzer)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email AND userID <> :id");
    $stmt->execute([':email' => $email, ':id' => $userID]);
    if ($stmt->fetchColumn() > 0) {
        http_response_code(409);
        echo json_encode(['error' => 'Email already in use by another user']);
        exit;
    }

    // 4) Dynamisches UPDATE bauen:
    //    - Standardfelder immer aktualisieren
    //    - Passwort nur aktualisieren, wenn übergeben (nicht leer)
    $sql = "
        UPDATE users
        SET first_name = :fn,
            last_name  = :ln,
            email      = :email,
            user_role  = :role
    ";

    $params = [
        ':fn'    => $firstName,
        ':ln'    => $lastName,
        ':email' => $email,
        ':role'  => $role,
        ':id'    => $userID,
    ];

    // Wenn Passwort mitgegeben wurde: Passwort-Hash aktualisieren
    if ($password !== '') {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $sql .= ", password_hash = :ph";
        $params[':ph'] = $passwordHash;
    }

    // WHERE-Klausel immer am Ende hinzufügen
    $sql .= " WHERE userID = :id";

    // 5) Update ausführen
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Erfolgsmeldung zurückgeben
    echo json_encode(['ok' => true]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Update user failed',
        'details' => $e->getMessage()
    ]);
}
