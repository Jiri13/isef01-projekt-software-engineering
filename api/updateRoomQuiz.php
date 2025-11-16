<?php
// api/updateRoomQuiz.php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/dbConnection.php';

$data = json_decode(file_get_contents('php://input'), true);

$roomID = (int)($data['roomID'] ?? 0);
$quizID = isset($data['quizID']) ? (int)$data['quizID'] : 0; // 0 = entfernen
$userID = (int)($data['userID'] ?? 0);

if ($roomID <= 0 || $userID <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'roomID and userID required']);
    exit;
}

// 1) prüfen, ob der User Host des Raums ist
$sql = "SELECT userID FROM Room WHERE roomID = :rid";
$st = $pdo->prepare($sql);
$st->execute([':rid' => $roomID]);
$owner = $st->fetchColumn();

if (!$owner) {
    http_response_code(404);
    echo json_encode(['error' => 'room not found']);
    exit;
}
if ((int)$owner !== $userID) {
    http_response_code(403);
    echo json_encode(['error' => 'only host can change quiz']);
    exit;
}

// 2) falls quizID > 0: prüfen, ob Quiz existiert
if ($quizID > 0) {
    $stQ = $pdo->prepare("SELECT 1 FROM Quiz WHERE quizID = :qid");
    $stQ->execute([':qid' => $quizID]);
    if (!$stQ->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'quizID not found']);
        exit;
    }
}

// 3) Update
$stU = $pdo->prepare("UPDATE Room SET quizID = :qid WHERE roomID = :rid");
$stU->execute([
    ':qid' => $quizID > 0 ? $quizID : null,
    ':rid' => $roomID
]);

echo json_encode(['ok' => true, 'roomID' => $roomID, 'quizID' => $quizID > 0 ? $quizID : null]);
