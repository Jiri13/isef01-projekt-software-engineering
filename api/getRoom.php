<?php
// api/getRoom.php
// Bereinigt: Nutzt nur noch die moderne 'quizquestion' Tabelle zur Fragenermittlung

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
require __DIR__ . '/dbConnection.php';

$code = isset($_GET['code']) ? trim($_GET['code']) : '';
$roomID = isset($_GET['roomID']) ? (int)$_GET['roomID'] : 0;

if (!$code && !$roomID) {
    echo json_encode(['error' => 'code or roomID required']);
    exit;
}

try {
    // 1. Raumdaten laden
    if ($code) {
        $stmt = $pdo->prepare("SELECT * FROM room WHERE code = :c LIMIT 1");
        $stmt->execute([':c' => $code]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM room WHERE roomID = :r LIMIT 1");
        $stmt->execute([':r' => $roomID]);
    }
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        http_response_code(404);
        echo json_encode(['error' => 'Room not found']);
        exit;
    }

    $rid = (int)$room['roomID'];
    $quizID = (int)$room['quizID'];

    // Teilnehmer laden
    $stP = $pdo->prepare("SELECT userID FROM roomparticipant WHERE roomID = :id");
    $stP->execute([':id' => $rid]);
    $participants = array_map('intval', $stP->fetchAll(PDO::FETCH_COLUMN));

    // 2. Fragen laden (NUR über quizquestion Verknüpfung)
    $questionsRaw = [];
    if ($quizID > 0) {
        $stmtQ = $pdo->prepare("
            SELECT 
                q.questionID, 
                q.question_text, 
                q.question_type, 
                q.time_limit, 
                q.difficulty,
                q.explanation
            FROM question q
            JOIN quizquestion qq ON q.questionID = qq.questionID
            WHERE qq.quizID = :qid
            ORDER BY COALESCE(qq.sort_order, 9999), q.questionID
        ");
        $stmtQ->execute([':qid' => $quizID]);
        $questionsRaw = $stmtQ->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3. Optionen laden und sauber zuordnen
    $finalQuestions = [];
    $questionsCount = count($questionsRaw);

    if ($questionsCount > 0) {
        $qIds = array_column($questionsRaw, 'questionID');

        // Optionen laden (Batch-Loading)
        $inQuery = implode(',', array_map('intval', $qIds));
        $stmtOpt = $pdo->query("
            SELECT optionID, questionID, option_text, is_correct 
            FROM question_option 
            WHERE questionID IN ($inQuery)
            ORDER BY optionID ASC
        ");
        $allOptions = $stmtOpt->fetchAll(PDO::FETCH_ASSOC);

        // Gruppieren
        $optionsByQuestionID = [];
        foreach ($allOptions as $opt) {
            $qidKey = (int)$opt['questionID'];
            if (!isset($optionsByQuestionID[$qidKey])) {
                $optionsByQuestionID[$qidKey] = [];
            }
            $optionsByQuestionID[$qidKey][] = [
                'id' => (int)$opt['optionID'],
                'text' => $opt['option_text'],
                'isCorrect' => (int)$opt['is_correct']
            ];
        }

        // 4. Fragen zusammenbauen
        foreach ($questionsRaw as $q) {
            $qid = (int)$q['questionID'];

            // Optionen zuordnen
            $opts = $optionsByQuestionID[$qid] ?? [];

            // Index der richtigen Antwort finden
            $correctIndex = -1;
            foreach ($opts as $idx => $o) {
                if ($o['isCorrect'] === 1) {
                    $correctIndex = $idx;
                }
            }

            $finalQuestions[] = [
                'id'            => $qid,
                'text'          => $q['question_text'],
                'type'          => $q['question_type'] ?: 'multiple_choice',
                'timeLimit'     => (int)$q['time_limit'],
                'difficulty'    => $q['difficulty'],
                'explanation'   => $q['explanation'],
                'options'       => $opts,
                'correctAnswer' => $correctIndex
            ];
        }
    }

    // 5. Antwort senden
    echo json_encode([
        'room' => [
            'id' => $rid,
            'name' => $room['room_name'] ?? $room['name'],
            'code' => $room['code'],
            'quizID' => $quizID,
            'started' => (int)$room['started'],
            'difficulty' => $room['difficulty'],
            'gameMode' => $room['play_mode'],
            'maxParticipants' => (int)$room['max_participants'],
            'participants' => $participants,
            'participantsCount' => count($participants),
            'questions' => $finalQuestions,
            'questionsCount' => $questionsCount
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB Error', 'details' => $e->getMessage()]);
}
?>