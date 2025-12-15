<?php
// api/createUser.php
// Erstellt einen neuen Benutzer über die Benutzerverwaltung (nur Admin).
// Erwartet Benutzerdaten (Vorname, Nachname, E-Mail, Rolle, Passwort) als JSON
// und speichert das Passwort ausschließlich gehasht in der Datenbank.
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

session_start();
require __DIR__ . '/dbConnection.php';

// Zugriffsschutz: Nur eingeloggte Admins dürfen Benutzer anlegen
if (!isset($_SESSION['userID']) || ($_SESSION['user_role'] ?? '') !== 'Admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Not allowed']);
    exit;
}

// JSON-Body aus dem Request einlesen
$input = json_decode(file_get_contents('php://input'), true);

// Eingabewerte auslesen und bereinigen.
$firstName = trim($input['first_name'] ?? '');
$lastName  = trim($input['last_name'] ?? '');
$email     = trim($input['email'] ?? '');
$role      = trim($input['user_role'] ?? '');
$password  = (string)($input['password'] ?? '');

// Pflichtfelder validieren
if ($firstName === '' || $email === '' || $password === '' || $role === '') {
    http_response_code(400);
    echo json_encode(['error' => 'first_name, email, password and user_role are required']);
    exit;
}

// Rollenvalidierung: nur definierte Rollen zulassen (Whitelisting)
$allowedRoles = ['Creator', 'Admin'];
if (!in_array($role, $allowedRoles, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid role']);
    exit;
}

try {
    /**
     * 1) Prüfen, ob die E-Mail-Adresse bereits existiert
     *    => verhindert doppelte Accounts
     */
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    if ($stmt->fetchColumn() > 0) {
        http_response_code(409);
        echo json_encode(['error' => 'Email already exists']);
        exit;
    }

    /**
     * 2) Passwort sicher hashen
     *    => Klartext-Passwort wird nicht in der DB gespeichert
     */
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    //3) Benutzer in der Datenbank anlegen
    $stmt = $pdo->prepare("
        INSERT INTO users (first_name, last_name, email, password_hash, user_role)
        VALUES (:fn, :ln, :email, :ph, :role)
    ");
    $stmt->execute([
        ':fn'    => $firstName,
        ':ln'    => $lastName,
        ':email' => $email,
        ':ph'    => $passwordHash,
        ':role'  => $role
    ]);

    // Neue userID (Auto-Increment) auslesen
    $newId = (int)$pdo->lastInsertId();

    //4) Erfolg zurückgeben (ohne Passwort/Hash!)
    echo json_encode([
        'ok'   => true,
        'user' => [
            'userID'     => $newId,
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'email'      => $email,
            'user_role'  => $role
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Create user failed',
        'details' => $e->getMessage()
    ]);
}
