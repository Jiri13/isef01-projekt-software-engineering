<?php
// api/updateRoom.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require __DIR__ . '/dbConnection.php';

$input = json_decode(file_get_contents('php://input'), true);

$roomID          = (int)($input['roomID'] ?? 0);
$userID          = (int)($input['userID'] ?? 0); // Zum Prüfen der Berechtigung (Owner)
$name            = trim($input['name'] ?? '');
$playMode        = $input['playMode'] ?? 'cooperative';
$difficulty      = $input['difficulty'] ?? 'medium';
$maxParticipants = (int)($input['maxParticipants'] ?? 8);
$quizID          = isset($input['quizID']) ? (int)$input['quizID'] : null;

if ($roomID <= 0 || $userID <= 0 || empty($name)) {
    http_response_code(400);
    echo json_encode(['error' => 'roomID, userID and name required']);
    exit;
}

try {
    // 1. Prüfen, ob der User wirklich der Host ist
    $check = $pdo->prepare("SELECT userID FROM room WHERE roomID = :rid");
    $check->execute([':rid' => $roomID]);
    $room = $check->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        http_response_code(404);
        echo json_encode(['error' => 'Room not found']);
        exit;
    }

    if ((int)$room['userID'] !== $userID) {
        http_response_code(403);
        echo json_encode(['error' => 'Nur der Host darf den Raum bearbeiten']);
        exit;
    }

    // 2. Update durchführen
    // Hinweis: quizID kann NULL sein (wenn man "Kein Quiz" wählt)
    $sql = "UPDATE room 
            SET room_name = :name, 
                play_mode = :pm, 
                difficulty = :diff, 
                max_participants = :maxP, 
                quizID = :qid 
            WHERE roomID = :rid";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name' => $name,
        ':pm'   => $playMode,
        ':diff' => $difficulty,
        ':maxP' => $maxParticipants,
        ':qid'  => ($quizID > 0 ? $quizID : null),
        ':rid'  => $roomID
    ]);

    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB Error', 'details' => $e->getMessage()]);
}