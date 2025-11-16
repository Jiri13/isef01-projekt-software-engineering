<?php
// listRooms.php – überarbeitet für Many-to-Many Quiz <-> Question

header('Content-Type: application/json');
require __DIR__ . '/dbConnection.php';

$userID = isset($_GET['userID']) ? (int)$_GET['userID'] : 0;
if ($userID <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'userID required']);
    exit;
}

/*
    ERKLÄRUNG:
    - questions_count: wird über quizquestion bestimmt
    - avg_diff: Difficulty-Durchschnitt der Fragen im Quiz
*/

$sql = "
SELECT
  r.roomID                             AS id,
  r.room_name                          AS name,
  r.play_mode                          AS play_mode,
  r.started                            AS started,
  r.quizID                             AS quizID,
  r.userID                             AS hostID,
  r.code                               AS code,
  r.max_participants	               AS max_participants,
  r.difficulty                         AS room_difficulty,

  -- Teilnehmer pro Raum
  COALESCE(rp.cnt, 0)                  AS participants_count,

  -- Fragen pro Quiz (über quizquestion)
  COALESCE(qstats.questions_count, 0)  AS questions_count,
  qstats.avg_diff                      AS avg_diff

FROM Room r

LEFT JOIN (
    SELECT
        roomID,
        COUNT(*) AS cnt
    FROM RoomParticipant
    GROUP BY roomID
) rp ON rp.roomID = r.roomID

LEFT JOIN (
    SELECT
        qq.quizID,
        COUNT(*) AS questions_count,
        AVG(
            CASE LOWER(q.difficulty)
                WHEN 'easy'   THEN 1
                WHEN 'medium' THEN 2
                WHEN 'hard'   THEN 3
            END
        ) AS avg_diff
    FROM quizquestion qq
    JOIN question q ON q.questionID = qq.questionID
    GROUP BY qq.quizID
) qstats ON qstats.quizID = r.quizID

WHERE
    r.userID = :uid
    OR r.roomID IN (SELECT roomID FROM RoomParticipant WHERE userID = :uid)

ORDER BY r.started DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':uid' => $userID]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Difficulty-Mapping */
function mapDifficulty($avg) {
    if ($avg <= 1.5) return 'easy';
    if ($avg <= 2.5) return 'medium';
    return 'hard';
}

/* Teilnehmer pro Raum laden */
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
        $participants[$rid][] = $uid;
    }
}

$result = array_map(function($r) use ($participants) {

    $avg            = $r['avg_diff'] !== null ? (float)$r['avg_diff'] : null;
    $questionsCount = (int)$r['questions_count'];
    $roomDiff       = strtolower($r['room_difficulty'] ?? 'medium');

    // Beide Welten:
    // Falls es Fragen gibt -> Difficulty nach Fragen bestimmen
    // Falls keine -> Difficulty direkt aus Room-Settings
    $difficulty = ($questionsCount > 0 && $avg !== null)
        ? mapDifficulty($avg)
        : $roomDiff;

    return [
        'id'               => (int)$r['id'],
        'name'             => $r['name'],
        'gameMode'         => strtolower($r['play_mode']) === 'cooperative' ? 'cooperative' : 'competitive',
        'difficulty'       => $difficulty,
        'code'             => $r['code'],
        'hostID'           => (int)$r['hostID'],
        'started'          => $r['started'],
        'quizID'           => (int)$r['quizID'],
        'participants'     => $participants[(int)$r['id']] ?? [],
        'participantsCount'=> (int)$r['participants_count'],
        'maxParticipants'  => (int)$r['max_participants'],
        'questions'        => array_fill(0, $questionsCount, null),
        'questionsCount'   => $questionsCount
    ];
}, $rows);

echo json_encode($result);
