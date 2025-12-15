<?php
// api/logout.php
// Zweck:
// Beendet die aktuelle Benutzersitzung vollständig.
// Dabei werden:
//  - alle Session-Daten entfernt
//  - das Session-Cookie ungültig gemacht
//  - die Session serverseitig zerstört
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

// Session starten bzw. vorhandene Session übernehmen
session_start();

// Session löschen
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

echo json_encode(['ok' => true]);

