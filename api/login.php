<?php
// api/login.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

session_start();
require __DIR__ . '/dbConnection.php';

$input = json_decode(file_get_contents('php://input'), true);

$email    = trim($input['email'] ?? '');
$password = (string)($input['password'] ?? '');

if ($email === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['error' => 'email and password required']);
    exit;
}

try {
    // ACHTUNG: deine Tabelle hat genau diese Spaltennamen
    $stmt = $pdo->prepare("
        SELECT userID, first_name, last_name, email, password_hash, user_role
        FROM users
        WHERE email = :email
        LIMIT 1
    ");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials (no user)']);
        exit;
    }

    $hashInDb = $user['password_hash'];

    // 1) „echter“ Weg: Passwort ist gehasht gespeichert
    $isValid = password_verify($password, $hashInDb);

    // 2) Fallback für deine Demo-Phase: Klartext-Passwort in password_hash
    if (!$isValid && $password === $hashInDb) {
        $isValid = true;
    }

    if (!$isValid) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials (wrong password)']);
        exit;
    }

    // Login erfolgreich → Session setzen
    $_SESSION['userID']     = (int)$user['userID'];
    $_SESSION['user_role']  = $user['user_role'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name']  = $user['last_name'];

    echo json_encode([
        'ok'   => true,
        'user' => [
            'userID'     => (int)$user['userID'],
            'first_name' => $user['first_name'],
            'last_name'  => $user['last_name'],
            'user_role'  => $user['user_role'],
            'email'      => $user['email'],
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error'   => 'Login failed',
        'details' => $e->getMessage()
    ]);
}
