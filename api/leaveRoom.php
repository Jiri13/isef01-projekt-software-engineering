<?php
// api/leaveRoom.php
// [WHY] Endpoint: Nutzer verlässt einen Raum. Verhindert, dass der Host seinen eigenen Raum verlässt.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // [WARN] Offen; in Prod auf vertrauenswürdige Origins begrenzen
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit; // [IO] CORS-Preflight kurzschließen

require __DIR__ . '/dbConnection.php'; // [ASSUME] PDO mit ERRMODE_EXCEPTION aktiv

// optional fürs Debugging
// ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);

$input  = json_decode(file_get_contents('php://input'), true);
$roomID = isset($input['roomID']) ? (int)$input['roomID'] : 0;
$userID = isset($input['userID']) ? (int)$input['userID'] : 0;

if ($roomID <= 0 || $userID <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'roomID and userID required']);
    exit; // [ERR] Pflichtwerte/Typen unzulässig
}

// [HOW] Raum lookup, um Existenz und Host später zu prüfen
$st = $pdo->prepare("SELECT userID FROM room WHERE roomID = :r LIMIT 1");
$st->execute([':r' => $roomID]);
$room = $st->fetch();

if (!$room) {
    http_response_code(404);
    echo json_encode(['error' => 'room not found']);
    exit; // [ERR] Zielraum existiert nicht
}

// [WHY] Host darf nicht "leaven", um verwaiste Räume zu verhindern (separater Delete-Flow erforderlich)
if ((int)$room['userID'] === $userID) {
    http_response_code(400);
    echo json_encode(['error' => 'host cannot leave their own room']);
    exit;
}

// [HOW] Idempotenz-Check: nur löschen, wenn User Teilnehmer ist
$st2 = $pdo->prepare("SELECT 1 FROM roomparticipant WHERE roomID = :r AND userID = :u");
$st2->execute([':r' => $roomID, ':u' => $userID]);
if (!$st2->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'user not in room']);
    exit; // [ERR] Kein Eintrag => nichts zu tun
}

// [IO] Austragen aus Teilnehmerliste; FK/UNIQUE sollten Konsistenz sichern
$del = $pdo->prepare("DELETE FROM roomparticipant WHERE roomID = :r AND userID = :u");
$del->execute([':r' => $roomID, ':u' => $userID]);

echo json_encode(['ok' => true, 'roomID' => $roomID, 'userID' => $userID]);

// [WARN] Authentisierung fehlt (userID aus Body). In Prod via Session/JWT prüfen. [TODO]
// [ASSUME] Kein zusätzlicher Cleanup notwendig (z. B. Scores/Locks). Falls doch, hier erweitern.
// [PERF] Kein TX nötig, da eine einzelne, atomare DELETE-Operation.
