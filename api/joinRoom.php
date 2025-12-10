<?php
// api/joinRoom.php
// Tritt einem Raum bei und erhöht den "Games Played" Zähler

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require __DIR__ . '/dbConnection.php';

$input  = json_decode(file_get_contents('php://input'), true);
$code   = isset($input['code']) ? trim((string)$input['code']) : '';
$userID = isset($input['userID']) ? (int)$input['userID'] : 0;

if ($code === '' || $userID <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'code and userID required']);
    exit;
}

// 1. Raum suchen & quizID mitladen
$st = $pdo->prepare("SELECT roomID, max_participants, quizID FROM room WHERE code = :code LIMIT 1");
$st->execute([':code' => $code]);
$room = $st->fetch();

if (!$room) {
    http_response_code(404);
    echo json_encode(['error' => 'room not found']);
    exit;
}

$roomID = (int)$room['roomID'];
$quizID = (int)$room['quizID'];

// 2. Prüfen ob schon Teilnehmer
$st2 = $pdo->prepare("SELECT 1 FROM roomparticipant WHERE roomID = :r AND userID = :u");
$st2->execute([':r' => $roomID, ':u' => $userID]);
if ($st2->fetch()) {
    // Schon drin -> Erfolg melden, aber Zähler NICHT erhöhen (Refresh-Schutz)
    echo json_encode(['ok' => true, 'roomID' => $roomID, 'alreadyParticipant' => true]);
    exit;
}

// 3. Kapazität prüfen
$maxP = isset($room['max_participants']) ? (int)$room['max_participants'] : null;
if ($maxP && $maxP > 0) {
    $st3 = $pdo->prepare("SELECT COUNT(*) AS c FROM roomparticipant WHERE roomID = :r");
    $st3->execute([':r' => $roomID]);
    $current = (int)$st3->fetch()['c'];
    if ($current >= $maxP) {
        http_response_code(409);
        echo json_encode(['error' => 'room is full']);
        exit;
    }
}

try {
    $pdo->beginTransaction();

    // 4. Teilnehmer eintragen
    $ins = $pdo->prepare("INSERT INTO roomparticipant (points, roomID, userID) VALUES (0, :r, :u)");
    $ins->execute([':r' => $roomID, ':u' => $userID]);

    $pdo->commit();
    echo json_encode(['ok' => true, 'roomID' => $roomID]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'DB Error', 'details' => $e->getMessage()]);
}