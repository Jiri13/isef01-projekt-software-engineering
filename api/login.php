<?php
// api/login.php
// Authentifiziert einen Benutzer anhand von E-Mail und Passwort.
// Bei erfolgreichem Login wird eine PHP-Session aufgebaut
// und die wichtigsten Benutzerdaten werden im Session-Speicher abgelegt.
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

// Session starten (oder fortsetzen)
session_start();
require __DIR__ . '/dbConnection.php';

// 1) JSON-Request auslesen
$input = json_decode(file_get_contents('php://input'), true);

// E-Mail und Passwort aus dem Request extrahieren
$email    = trim($input['email'] ?? '');
$password = (string)($input['password'] ?? '');

// Pflichtfelder prüfen
if ($email === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['error' => 'email and password required']);
    exit;
}

try {
    // 2) Benutzer anhand der E-Mail aus der Datenbank laden
    $stmt = $pdo->prepare("
        SELECT userID, first_name, last_name, email, password_hash, user_role
        FROM users
        WHERE email = :email
        LIMIT 1
    ");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Falls kein Benutzer gefunden wurde → Login abbrechen
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials (no user)']);
        exit;
    }

    // 3) Passwort prüfen
    $hashInDb = $user['password_hash'];

    // Standardfall:
    // Passwort ist gehasht gespeichert → password_verify verwenden
    $isValid = password_verify($password, $hashInDb);

    // Fallback für Demo-/Übergangsphase:
    // Falls das Passwort noch im Klartext in der DB steht
    if (!$isValid && $password === $hashInDb) {
        $isValid = true;
    }

    // Passwort ist falsch
    if (!$isValid) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials (wrong password)']);
        exit;
    }

    // 4. Login erfolgreich → Session setzen
    $_SESSION['userID']     = (int)$user['userID'];
    $_SESSION['user_role']  = $user['user_role'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name']  = $user['last_name'];

    // 5) Erfolgreiche Antwort an das Frontend senden
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
