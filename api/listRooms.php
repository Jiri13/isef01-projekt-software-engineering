<?php
// [WHY] Endpoint: Liefert alle Räume, in denen userID Host oder Teilnehmer ist, inkl. Metriken.

header('Content-Type: application/json');

require __DIR__ . '/dbConnection.php';

/**
 * Expect: GET /api/listRooms.php?userID=123
 */

$userID = isset($_GET['userID']) ? (int)$_GET['userID'] : 0;
if ($userID <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'userID required']);
    exit;
}
// [WARN] userID kommt aus Query-Param ohne Auth-Überprüfung (Session/JWT fehlt).
// [ASSUME] Request ist bereits authentisiert oder nur für interne Nutzung bestimmt.

$sql = "
SELECT
  r.roomID                            AS id,
  r.room_name                         AS name,
  r.play_mode                         AS play_mode,
  r.started                           AS started,
  r.quizID                            AS quizID,
  r.userID                            AS hostID,
  r.code                              AS code,
  r.max_participants	              AS max_participants,
  COALESCE(rp.cnt, 0)                 AS participants_count,
  COALESCE(qq.cnt, 0)                 AS questions_count,
  COALESCE(qq.avg_diff, 0)            AS avg_diff
FROM Room r
LEFT JOIN (
  SELECT roomID, COUNT(*) AS cnt
  FROM RoomParticipant
  GROUP BY roomID
) rp ON rp.roomID = r.roomID
LEFT JOIN (
  SELECT
    q.quizID,
    COUNT(*) AS cnt,
    AVG(CASE q.difficulty
          WHEN 'Easy'   THEN 1
          WHEN 'Medium' THEN 2
          WHEN 'Hard'   THEN 3
        END) AS avg_diff
  FROM Question q
  GROUP BY q.quizID
) qq ON qq.quizID = r.quizID
WHERE
  r.userID = :uid
  OR r.roomID IN (SELECT roomID FROM RoomParticipant WHERE userID = :uid)
ORDER BY r.started DESC
";
// [HOW] Aggregiert Teilnehmer/Fragen via vorgelagerten Gruppierungen und joint diese pro Raum.
// [ASSUME] Question.difficulty ist exakt 'Easy|Medium|Hard' (Großschreibung wie im CASE).
// [PERF] COUNT/AVG über Subselects vermeidet N+1-Queries; Indexe auf RoomParticipant(roomID), Question(quizID) empfohlen.
// [WARN] participants_count zählt nur RoomParticipant, nicht zwingend den Host (falls Host nicht als Participant erfasst).

$stmt = $pdo->prepare($sql);
$stmt->execute([':uid' => $userID]);
$rows = $stmt->fetchAll();

// [HOW] Teilnehmer je Raum (als Liste von userIDs) nachladen
$roomIds = array_column($rows, 'id');
$participants = [];
if (!empty($roomIds)) {
    $placeholders = implode(',', array_fill(0, count($roomIds), '?'));
    $sqlP = "SELECT roomID, userID FROM RoomParticipant WHERE roomID IN ($placeholders)";
    $stP = $pdo->prepare($sqlP);
    $stP->execute($roomIds);
    foreach ($stP->fetchAll() as $p) {
        $rid = (int)$p['roomID'];
        $uid = (int)$p['userID'];
        if (!isset($participants[$rid])) $participants[$rid] = [];
        $participants[$rid][] = $uid;
    }
    // [HOW] Dynamische IN-Klausel sicher über Platzhalter; schützt vor SQL-Injection.
    // [PERF] Sehr große IN-Listen können langsam sein; Alternative: JOIN mit IN (...) oder batched Laden.
}

/* Difficulty-Heuristik: 1..3 -> easy/medium/hard */
function mapDifficulty($avg)
{
    if ($avg <= 1.5) return 'easy';
    if ($avg <= 2.5) return 'medium';
    return 'hard';
}
// [ASSUME] Schwellen 1.5/2.5 linear aus Mapping 1..3; bei fehlenden Fragen ist avg_diff = 0 → später 'medium'.

$result = array_map(function($r) use ($participants) {
    $id = (int)$r['id'];
    $avg = (float)$r['avg_diff'];

    return [
        'id'               => $id,
        'name'             => $r['name'],
        'gameMode'         => (strtolower($r['play_mode']) === 'cooperative') ? 'cooperative' : 'competitive', // [HOW] API normalisiert auf lowercase
        'difficulty'       => $avg > 0 ? mapDifficulty($avg) : 'medium', // [ASSUME] Kein avg ⇒ neutrale Default-Schwierigkeit
        'code'             => $r['code'],
        'hostID'           => (int)$r['hostID'],
        'started'          => $r['started'],
        'quizID'           => (int)$r['quizID'],              // [WARN] NULL wird zu 0 coercet; ggf. besser null durchreichen.
        'participants'     => $participants[$id] ?? [],       // [ASSUME] Reihenfolge der IDs ist unerheblich
        'participantsCount'=> (int)$r['participants_count'],
        'maxParticipants'  => $r['max_participants'],          // [ASSUME] Typ vom Treiber (String/Int) ist für Client tolerierbar
        'questions'        => array_fill(0, (int)$r['questions_count'], null), // [WHY] Nur Anzahl signalisieren, Inhalte separat laden
        'questionsCount'   => (int)$r['questions_count'],
    ];
}, $rows);

echo json_encode($result);
// [IO] Liefert Array ohne Wrapper/ok-Flag; Client muss leeres Array korrekt interpretieren.
// [PERF] Für viele Räume kann die participants-Nachladung groß werden; Pagination/Limit im Hauptquery erwägen. [TODO]
