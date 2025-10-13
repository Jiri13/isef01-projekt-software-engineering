<?php
// [WHY] Endpoint: Nutzer einem Raum per Code beitreten lassen; prüft Existenz & Kapazität.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // [WARN] Offen; in Prod auf vertrauenswürdige Origins begrenzen
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit; // [IO] CORS-Preflight abbrechen

require __DIR__ . '/dbConnection.php';

$input  = json_decode(file_get_contents('php://input'), true);
$code   = isset($input['code']) ? trim((string)$input['code']) : '';
$userID = isset($input['userID']) ? (int)$input['userID'] : 0;

if ($code === '' || $userID <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'code and userID required']);
    exit; // [ERR] Pflichtfelder fehlen/ungültig
}
// [WARN] Keine Zeichen-/Längenvalidierung für code; falls UI case-insensitive ist, serverseitig normalisieren/prüfen. [TODO]

// [ASSUME] Room.code ist eindeutig (Unique-Index)
$st = $pdo->prepare("SELECT roomID, max_participants FROM Room WHERE code = :code LIMIT 1"); // [HOW] Parametrisiert gegen SQL-Injection
$st->execute([':code' => $code]);
$room = $st->fetch();

if (!$room) {
    http_response_code(404);
    echo json_encode(['error' => 'room not found']);
    exit; // [ERR] Raum existiert nicht
}

$roomID = (int)$room['roomID'];

// [HOW] Früh exit, falls Nutzer bereits Teilnehmer ist (idempotent)
$st2 = $pdo->prepare("SELECT 1 FROM RoomParticipant WHERE roomID = :r AND userID = :u");
$st2->execute([':r' => $roomID, ':u' => $userID]);
if ($st2->fetch()) {
    echo json_encode(['ok' => true, 'roomID' => $roomID, 'alreadyParticipant' => true]); // [WHY] Idempotentes Verhalten für wiederholte Requests
    exit;
}

// [ASSUME] max_participants 0/null bedeutet unbegrenzt
$maxP = isset($room['max_participants']) ? (int)$room['max_participants'] : null;
if ($maxP && $maxP > 0) {
    $st3 = $pdo->prepare("SELECT COUNT(*) AS c FROM RoomParticipant WHERE roomID = :r"); // [PERF] Index auf (roomID) empfohlen
    $st3->execute([':r' => $roomID]);
    $current = (int)$st3->fetch()['c'];
    if ($current >= $maxP) {
        http_response_code(409);
        echo json_encode(['error' => 'room is full']);
        exit; // [ERR] Kapazität erreicht
    }
}

// [WARN] Race-Condition: Zwischen COUNT und INSERT können weitere Joiner eintreten.
//        Gegenmaßnahme: UNIQUE(roomID,userID) + Duplicate-Key als 200 idempotent/409 behandeln,
//        sowie Kapazität per TX/Lock (z. B. SELECT ... FOR UPDATE) prüfen. [TODO]

// [ASSUME] userID existiert in Users; aktuell keine Validierung dagegen → FK/Constraint sollte Fehler verhindern
// [ERR] INSERT kann bei fehlendem UNIQUE zu Duplikaten führen; aktuell nicht abgefangen
$ins = $pdo->prepare("INSERT INTO RoomParticipant (points, roomID, userID) VALUES (0, :r, :u)");
$ins->execute([':r' => $roomID, ':u' => $userID]); // [HOW] Defaultpunkte=0; weitere Felder werden von DB-Defaults getragen

echo json_encode(['ok' => true, 'roomID' => $roomID]); // [WHY] Liefert Ziel-Raum zur Weiter-Navigation im Client

// [WARN] Authentisierung fehlt: userID kommt aus Body. In Prod via Session/JWT verifizieren.
