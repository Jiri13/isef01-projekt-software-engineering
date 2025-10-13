<?php
// [WHY] Endpoint: Löscht einen Raum inkl. abhängiger Daten; nur Host darf löschen.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // [WARN] Offen für alle Origins; Prod: whitelisten
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit; // [IO] CORS-Preflight ohne Verarbeitung
}

require __DIR__ . '/dbConnection.php';

// [HOW] Request-Body einlesen und minimale Typ-/Range-Checks
$input = json_decode(file_get_contents('php://input'), true);
$roomID = isset($input['roomID']) ? (int)$input['roomID'] : 0;
$userID = isset($input['userID']) ? (int)$input['userID'] : 0;

if ($roomID <= 0 || $userID <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'roomID and userID required']);
    exit; // [ERR] Fehlende Pflichtwerte
}

// [WHY] Autorisierung: Nur Host (Room.userID) darf löschen
$st = $pdo->prepare("SELECT userID FROM Room WHERE roomID = :roomID"); // [HOW] Prepared Statement verhindert SQL-Injection
$st->execute([':roomID' => $roomID]);
$room = $st->fetch();

if (!$room) {
    http_response_code(404);
    echo json_encode(['error' => 'Room not found']);
    exit; // [ERR] Nicht existenter Raum
}

if ((int)$room['userID'] !== $userID) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden: only host can delete']);
    exit; // [ERR] Verboten: fremder User
}

// [WARN] Authentisierung fehlt: userID kommt aus dem Body; in Prod via Token/Session prüfen.
// [ASSUME] Keine parallelen Änderungen an Room.userID zwischen Check und Delete (TOCTOU-Risiko gering, aber möglich).

// [WHY] Transaktion garantiert atomare Löschung über mehrere Tabellen
$pdo->beginTransaction();
try {
    // [IO] Abhängige Daten zuerst entfernen; alternativ FK ON DELETE CASCADE nutzen
    $pdo->prepare("DELETE FROM RoomParticipant WHERE roomID = :roomID")->execute([':roomID' => $roomID]);
    $pdo->prepare("DELETE FROM RoomAnswer WHERE roomID = :roomID")->execute([':roomID' => $roomID]);
    $pdo->prepare("DELETE FROM ChatMessage WHERE roomID = :roomID")->execute([':roomID' => $roomID]);

    // [HOW] Raum selbst löschen; greift erst nach Entfernen der Kind-Datensätze
    $pdo->prepare("DELETE FROM Room WHERE roomID = :roomID")->execute([':roomID' => $roomID]);
    // [ASSUME] Keine weiteren Tabellen mit roomID-FKs; sonst: erweitern oder CASCADE nutzen.
    // [PERF] Mit FK-Cascade würde ein einzelnes DELETE auf Room genügen (weniger Roundtrips).

    $pdo->commit();
    echo json_encode(['ok' => true, 'deletedRoomID' => $roomID]); // [WHY] Liefert Idempotenz-nahes Ergebnis (bei erneutem Aufruf folgt 404)
} catch (Exception $e) {
    $pdo->rollBack(); // [ERR] Konsistenz bei Fehler wiederherstellen
    http_response_code(500);
    echo json_encode(['error' => 'Delete failed', 'details' => $e->getMessage()]); // [WARN] Fehlermeldung kann interne Details leaken
}

// [PERF] FK-Cascades + Indexe auf roomID reduzieren Roundtrips und vereinfachen Code. [TODO] Prüfen/aktivieren
// [WARN] Potenzielle Deadlocks bei konkurrierenden Lösch-/Schreibvorgängen; konsistente Löschreihenfolge/Indexe helfen.
// [ASSUME] Standard-Transaction-Isolation (REPEATABLE READ/READ COMMITTED) ist ausreichend für diesen Use-Case.
