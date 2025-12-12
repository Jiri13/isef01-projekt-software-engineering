<?php
// api/createUser.php
//für Benutzerverwaltung im admin bereich
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

session_start();
require __DIR__ . '/dbConnection.php';

// Nur Admin darf Benutzer anlegen
if (!isset($_SESSION['userID']) || ($_SESSION['user_role'] ?? '') !== 'Admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$firstName = trim($input['first_name'] ?? '');
$lastName  = trim($input['last_name'] ?? '');
$email     = trim($input['email'] ?? '');
$role      = trim($input['user_role'] ?? '');
$password  = (string)($input['password'] ?? '');

if ($firstName === '' || $email === '' || $password === '' || $role === '') {
    http_response_code(400);
    echo json_encode(['error' => 'first_name, email, password and user_role are required']);
    exit;
}

// nur erlaubte Rollen
$allowedRoles = ['Creator', 'Admin'];
if (!in_array($role, $allowedRoles, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid role']);
    exit;
}

try {
    // prüfen, ob E-Mail schon existiert
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    if ($stmt->fetchColumn() > 0) {
        http_response_code(409);
        echo json_encode(['error' => 'Email already exists']);
        exit;
    }

    // Passwort hashen
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

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

    $newId = (int)$pdo->lastInsertId();

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
