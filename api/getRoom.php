<?php
// api/getRoom.php
// Liefert einen einzelnen Raum inkl. Teilnehmern und Fragen (über Quiz → QuizQuestion)

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require __DIR__ . '/dbConnection.php';

$roomID = isset($_GET['roomID']) ? (int)$_GET['roomID'] : 0;
if ($roomID <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'roomID required']);
    exit;
}

/**
 * 1) Raum laden
 */
$sqlRoom = "
    SELECT
        r.roomID           AS id,
        r.room_name        AS name,
        r.play_mode        AS play_mode,
        r.difficulty       AS room_difficulty,
        r.started          AS started,
        r.code             AS code,
        r.quizID           AS quizID,
        r.userID           AS hostID,
        r.max_participants AS max_participants
    FROM room r
    WHERE r.roomID = :id
    LIMIT 1
";
$stRoom = $pdo->prepare($sqlRoom);
$stRoom->execute([':id' => $roomID]);
$room = $stRoom->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    http_response_code(404);
    echo json_encode(['error' => 'room not found']);
    exit;
}

/**
 * 2) Teilnehmer laden (userIDs)
 */
$participants = [];
$stP = $pdo->prepare("SELECT userID FROM roomparticipant WHERE roomID = :id");
$stP->execute([':id' => $roomID]);
$participants = array_map('intval', $stP->fetchAll(PDO::FETCH_COLUMN));

/**
 * 3) Fragen laden (über quizquestion → question)
 */
$quizID = (int)($room['quizID'] ?? 0);
$questions = [];

if ($quizID > 0) {

    // 3a) Fragen-IDs & Grunddaten über quizquestion + question
    $sqlQ = "
        SELECT
            q.questionID,
            q.question_text,
            q.question_type,
            q.explanation,
            q.time_limit,
            q.difficulty
        FROM quizquestion qq
        JOIN question q ON q.questionID = qq.questionID
        WHERE qq.quizID = :qid
        ORDER BY COALESCE(qq.sort_order, 9999), q.questionID
    ";
    $stQ = $pdo->prepare($sqlQ);
    $stQ->execute([':qid' => $quizID]);
    $rowsQ = $stQ->fetchAll(PDO::FETCH_ASSOC);

    // Fallback: falls (aus irgendeinem Grund) noch keine Einträge in quizquestion sind
    if (!$rowsQ) {
        $sqlQ2 = "
            SELECT
                q.questionID,
                q.question_text,
                q.question_type,
                q.explanation,
                q.time_limit,
                q.difficulty
            FROM question q
            WHERE q.quizID = :qid
            ORDER BY q.questionID
        ";
        $stQ2 = $pdo->prepare($sqlQ2);
        $stQ2->execute([':qid' => $quizID]);
        $rowsQ = $stQ2->fetchAll(PDO::FETCH_ASSOC);
    }

    if ($rowsQ) {
        $questionIds = array_map(fn($r) => (int)$r['questionID'], $rowsQ);

        // 3b) Antwortoptionen zu allen Fragen auf einmal holen
        $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
        $sqlOpt = "
            SELECT
                optionID,
                option_text,
                is_correct,
                questionID
            FROM question_option
            WHERE questionID IN ($placeholders)
            ORDER BY questionID, optionID
        ";
        $stOpt = $pdo->prepare($sqlOpt);
        $stOpt->execute($questionIds);
        $rowsOpt = $stOpt->fetchAll(PDO::FETCH_ASSOC);

        // Optionen nach questionID gruppieren
        $optionsByQuestion = [];
        foreach ($rowsOpt as $o) {
            $qid = (int)$o['questionID'];
            if (!isset($optionsByQuestion[$qid])) {
                $optionsByQuestion[$qid] = [];
            }
            $optionsByQuestion[$qid][] = $o;
        }

        // 3c) Fragen in das Format bringen, das das Frontend erwartet
        foreach ($rowsQ as $q) {
            $qid       = (int)$q['questionID'];
            $opts      = $optionsByQuestion[$qid] ?? [];
            $optTexts  = [];
            $correctIx = null;

            foreach ($opts as $index => $optRow) {
                $optTexts[] = $optRow['option_text'];
                if ((int)$optRow['is_correct'] === 1) {
                    $correctIx = $index; // 0-basiert
                }
            }

            $questions[] = [
                'id'                => $qid,
                'text'              => $q['question_text'],
                'type'              => $q['question_type'] ?: 'multiple_choice',
                'options'           => $optTexts,
                'correctAnswer'     => $correctIx,      // Index 0..n
                'correctAnswerText' => null,            // optional für Textfragen
                'explanation'       => $q['explanation'],
                'timeLimit'         => (int)$q['time_limit'],
                'difficulty'        => strtolower($q['difficulty'] ?? 'Easy') // 'easy' | 'medium' | 'hard'
            ];
        }
    }
}

// Anzahl Fragen
$questionsCount = count($questions);

/**
 * 4) Finale Struktur für Frontend (Dashboard & MultiplayerPage)
 */
$response = [
    'id'               => (int)$room['id'],
    'name'             => $room['name'],
    'gameMode'         => strtolower($room['play_mode']) === 'cooperative' ? 'cooperative' : 'competitive',
    'difficulty'       => strtolower($room['room_difficulty'] ?? 'medium'),
    'code'             => $room['code'],
    'hostID'           => (int)$room['hostID'],
    'started'          => $room['started'],
    'quizID'           => $quizID,
    'participants'     => $participants,               // [3, 5, 7, ...]
    'participantsCount'=> count($participants),
    'maxParticipants'  => (int)$room['max_participants'],
    'questions'        => $questions,                  // volle Frageobjekte
    'questionsCount'   => $questionsCount
];

echo json_encode($response);
