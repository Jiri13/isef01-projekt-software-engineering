<?php
// api/rooms.php
header('Content-Type: application/json');

require __DIR__ . '/db.php';

/**
 * Expect: GET /api/rooms.php?userID=123
 */
$userID = isset($_GET['userID']) ? (int)$_GET['userID'] : 0;
if ($userID <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'userID required']);
    exit;
}

/*
  Aggregationen:
  - participants_count: RoomParticipant
  - questions_count: Question (per quizID)
  - avg_difficulty: Heuristik aus Question.difficulty (Easy=1, Medium=2, Hard=3)
*/
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

$stmt = $pdo->prepare($sql);
$stmt->execute([':uid' => $userID]);
$rows = $stmt->fetchAll();

/* Teilnehmer je Room (als Liste von userIDs) */
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
}

/* Difficulty-Heuristik: 1..3 -> easy/medium/hard */
function mapDifficulty($avg)
{
    if ($avg <= 1.5) return 'easy';
    if ($avg <= 2.5) return 'medium';
    return 'hard';
}

/* Ergebnis in Frontend-Form bringen */
$result = array_map(function($r) use ($participants) {
    $id = (int)$r['id'];
    $avg = (float)$r['avg_diff'];

    return [
        'id'               => $id,
        'name'             => $r['name'],
        'gameMode'         => (strtolower($r['play_mode']) === 'cooperative') ? 'cooperative' : 'competitive',
        'difficulty'       => $avg > 0 ? mapDifficulty($avg) : 'medium',
        'code'             => $r['code'],
        'hostID'           => (int)$r['hostID'],
        'started'          => $r['started'],
        'quizID'           => (int)$r['quizID'],
        'participants'     => $participants[$id] ?? [],
        'participantsCount'=> (int)$r['participants_count'],
        'maxParticipants'  => $r['max_participants'],
        'questions'        => array_fill(0, (int)$r['questions_count'], null),
        'questionsCount'   => (int)$r['questions_count'],
    ];
}, $rows);

echo json_encode($result);
