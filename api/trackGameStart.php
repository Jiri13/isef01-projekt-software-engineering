<?php
// api/trackGameStart.php
// Zählt "Games Played" hoch, aber maximal einmal alle 60 Sekunden pro Quiz/User (Spam-Schutz)

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require __DIR__ . '/dbConnection.php';

$input = json_decode(file_get_contents('php://input'), true);
$roomID = isset($input['roomID']) ? (int)$input['roomID'] : 0;
$userID = isset($input['userID']) ? (int)$input['userID'] : 0;

if ($roomID <= 0 || $userID <= 0) {
    echo json_encode(['error' => 'Missing IDs']);
    exit;
}

try {
    // 1. QuizID aus dem Raum holen
    $stRoom = $pdo->prepare("SELECT quizID FROM room WHERE roomID = :rid");
    $stRoom->execute([':rid' => $roomID]);
    $quizID = $stRoom->fetchColumn();

    if (!$quizID) {
        echo json_encode(['ok' => true, 'info' => 'No quiz in room']);
        exit;
    }

    // 2. Prüfen, wann zuletzt gespielt wurde (Spam-Schutz / Reload-Schutz)
    $stCheck = $pdo->prepare("
        SELECT last_played 
        FROM userquizstats 
        WHERE userID = :uid AND quizID = :qid
    ");
    $stCheck->execute([':uid' => $userID, ':qid' => $quizID]);
    $lastPlayed = $stCheck->fetchColumn();

    $shouldUpdate = true;
    if ($lastPlayed) {
        $secondsSince = time() - strtotime($lastPlayed);
        // Wenn vor weniger als 60 Sekunden aktualisiert wurde, zählen wir nicht nochmal
        if ($secondsSince < 60) {
            $shouldUpdate = false;
        }
    }

    // 3. Update durchführen
    if ($shouldUpdate) {
        $stmtStats = $pdo->prepare("
            INSERT INTO userquizstats (userID, quizID, games_played, last_played)
            VALUES (:uid, :qid, 1, NOW())
            ON DUPLICATE KEY UPDATE 
                games_played = games_played + 1,
                last_played = NOW()
        ");
        $stmtStats->execute([':uid' => $userID, ':qid' => $quizID]);
        echo json_encode(['ok' => true, 'counted' => true]);
    } else {
        echo json_encode(['ok' => true, 'counted' => false, 'reason' => 'too_soon']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB Error']);
}