<?php
// api/addRoom.php
// [WHY] Endpoint zum Anlegen eines Spiel-Raums mit Validierung, Code-Generierung und DB-TXN.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // [WARN] CORS für alle Ursprünge offen; in Prod ggf. einschränken
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; } // [IO] CORS-Preflight schnell beenden

require __DIR__ . '/dbConnection.php'; // [ASSUME] PDO ist mit ERRMODE_EXCEPTION + UTF-8 konfiguriert

/**
 * JSON:
 * {
 *   "name": "Roomtitel",                     // required
 *   "playMode": "cooperative|competitive",   // optional, default 'cooperative'
 *   "difficulty": "easy|medium|hard",        // optional, default 'medium'
 *   "maxParticipants": 8,                    // optional, default 10
 *   "started": "YYYY-MM-DD HH:MM:SS",        // optional, default NOW
 *   "quizID": null | 123,                    // optional; null = kein Quiz
 *   "userID": 5,                             // required
 *   "code": "ABC123",                        // optional; wird generiert
 *   "addHostAsParticipant": true             // optional
 * }
 * [WARN] Keine Längen-/Zeichen-Validierung für name/code; DB-Constraints übernehmen das Risiko
 */

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { http_response_code(400); echo json_encode(['error'=>'Invalid or missing JSON body']); exit; } // [ERR] Ungültiges/fehlendes JSON

$name    = trim((string)($input['name'] ?? ''));
$modeIn  = strtolower(trim((string)($input['playMode'] ?? 'cooperative')));
$diffIn  = strtolower(trim((string)($input['difficulty'] ?? 'medium'))); // << lower-case
$maxPart = isset($input['maxParticipants']) ? (int)$input['maxParticipants'] : 10;
$started = trim((string)($input['started'] ?? ''));
$quizID  = array_key_exists('quizID', $input) ? $input['quizID'] : null; // [HOW] unterscheidet fehlend vs. explizit null
$userID  = (int)($input['userID'] ?? 0);
$code    = isset($input['code']) ? trim((string)$input['code']) : ''; // [WARN] Client-Code wird nicht auf erlaubte Zeichen geprüft
$addHost = !empty($input['addHostAsParticipant']);

if ($name === '' || $userID <= 0) { http_response_code(400); echo json_encode(['error'=>'name and userID are required']); exit; } // [ERR] Mindestangaben prüfen

$playMode = $modeIn === 'competitive' ? 'Competitive' : 'Cooperative'; // [ASSUME] DB speichert kapitalisiert
$validDiffs = ['easy','medium','hard'];
$difficultyDb = in_array($diffIn, $validDiffs, true) ? $diffIn : 'medium'; // [ERR] Whitelist statt freier String

if ($maxPart < 2) $maxPart = 2;               // [ASSUME] Mindestens 2 Spieler
if ($maxPart > 99) $maxPart = 99;             // [ASSUME] Obergrenze zur DB/UI-Entlastung

if ($started === '') { $started = date('Y-m-d H:i:s'); } // [ASSUME] Serverzeit/Timezone
else {
    $t = strtotime($started);
    if ($t === false) { http_response_code(400); echo json_encode(['error'=>'Invalid datetime for "started"']); exit; } // [ERR]
    $started = date('Y-m-d H:i:s', $t); // [WARN] Zeitzone des Inputs geht verloren; Normalisierung auf Server-TZ
}

/**
 * [WHY] Generiert kollisionsarme Raum-Codes und prüft Einzigartigkeit gegen DB.
 * [HOW] random_bytes → hex → base36 → uppercase; danach DB-Existenzcheck in Schleife.
 * [PERF] Erwartete O(1)-Versuche bei kleinem Code-Space; Loop bricht bei Treffer.
 * [ERR] Vertraut auf DB-Verbindung; ohne Unique-Index kann Race Condition verbleiben.
 * [WARN] random_bytes kann Exception werfen (z. B. fehlende Entropie); hier nicht separat abgefangen
 */
function generateUniqueCode(PDO $pdo, $length = 6) {
    while (true) {
        $rand = strtoupper(substr(base_convert(bin2hex(random_bytes(5)),16,36),0,$length));
        $st = $pdo->prepare("SELECT 1 FROM room WHERE code = :c LIMIT 1");
        $st->execute([':c'=>$rand]);
        if (!$st->fetch()) return $rand;
    }
}
if ($code === '') {
    $code = generateUniqueCode($pdo, 6); // [WHY] Server-seitige Code-Erzeugung
} else {
    $st = $pdo->prepare("SELECT 1 FROM room WHERE code = :c LIMIT 1");
    $st->execute([':c'=>$code]);
    if ($st->fetch()) { http_response_code(409); echo json_encode(['error'=>'Room code already exists']); exit; } // [ERR] Konflikt melden
}

try {
    $pdo->beginTransaction(); // [WHY] Konsistenz über mehrere Tabellen

    $chkU = $pdo->prepare("SELECT 1 FROM users WHERE userID = :u LIMIT 1");
    $chkU->execute([':u'=>$userID]);
    if (!$chkU->fetch()) { $pdo->rollBack(); http_response_code(400); echo json_encode(['error'=>'userID not found']); exit; } // [ERR]

    $quizIdToStore = null;
    if ($quizID !== null && $quizID !== '' && (int)$quizID > 0) {
        $qId = (int)$quizID; // [ASSUME] numerische quizID; "0" gilt als ungültig
        $chkQ = $pdo->prepare("SELECT 1 FROM quiz WHERE quizID = :q LIMIT 1");
        $chkQ->execute([':q'=>$qId]);
        if (!$chkQ->fetch()) { $pdo->rollBack(); http_response_code(400); echo json_encode(['error'=>'quizID not found']); exit; } // [ERR]
        $quizIdToStore = $qId;
    }

    $ins = $pdo->prepare("
    INSERT INTO room (room_name, play_mode, difficulty, max_participants, started, quizID, userID, code)
    VALUES (:name, :mode, :difficulty, :maxp, :started, :quizID, :userID, :code)
  ");
    if ($quizIdToStore === null) { $ins->bindValue(':quizID', null, PDO::PARAM_NULL); } // [HOW] Explizit NULL binden
    else { $ins->bindValue(':quizID', $quizIdToStore, PDO::PARAM_INT); }

    $ins->bindValue(':name', $name);
    $ins->bindValue(':mode', $playMode);
    $ins->bindValue(':difficulty', $difficultyDb);
    $ins->bindValue(':maxp', $maxPart, PDO::PARAM_INT);
    $ins->bindValue(':started', $started);
    $ins->bindValue(':userID', $userID, PDO::PARAM_INT);
    $ins->bindValue(':code', $code);
    $ins->execute();

    $roomID = (int)$pdo->lastInsertId(); // [ASSUME] auto_increment und kein Trigger ändert ID

    if ($addHost) {
        $insP = $pdo->prepare("INSERT INTO roomparticipant (points, roomID, userID) VALUES (0, :r, :u)");
        $insP->execute([':r'=>$roomID, ':u'=>$userID]); // [ASSUME] (roomID,userID) ist entweder unikal oder mehrfach erlaubt
        // [WARN] Kein Check auf bestehende Teilnahme; Duplicate wird auf DB-Constraint abgewiesen (falls vorhanden)
    }

    $pdo->commit();

    echo json_encode([
        'ok' => true,
        'room' => [
            'id'              => $roomID,
            'name'            => $name,
            'playMode'        => strtolower($playMode), // [HOW] API liefert lower-case, DB speichert kapitalisiert
            'difficulty'      => $difficultyDb,
            'maxParticipants' => $maxPart,
            'started'         => $started,
            'quizID'          => $quizIdToStore,
            'hostID'          => $userID,
            'code'            => $code
        ]
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack(); // [ERR] TX sauber zurückrollen
    http_response_code(500);
    echo json_encode(['error'=>'Insert failed','details'=>$e->getMessage()]); // [WARN] Leakt DB-Fehlerinhalte in Produktion
}

// [WARN] Race-Condition möglich, wenn zwei Prozesse denselben Code gleichzeitig einfügen.
//        Unique-Index auf Room(code) erzwingen und 409 bei Duplicate Key abbilden. [TODO]
