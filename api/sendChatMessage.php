<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require __DIR__ . '/dbConnection.php';
require __DIR__ . '/encryptionKey.php'; // <--- NEU

$input = json_decode(file_get_contents('php://input'), true);

$roomID = (int)($input['roomID'] ?? 0);
$userID = (int)($input['userID'] ?? 0);
$message = trim((string)($input['message'] ?? ''));

if ($roomID <= 0 || $userID <= 0 || empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing data']);
    exit;
}

try {
    // Nachricht verschlüsseln vor dem Speichern
    $encryptedMessage = encryptMessage($message); // <--- NEU

    $stmt = $pdo->prepare("
        INSERT INTO chatmessage (message, created_at, roomID, userID)
        VALUES (:msg, NOW(), :rid, :uid)
    ");

    $stmt->execute([
        ':msg' => $encryptedMessage, // Wir speichern den Salat
        ':rid' => $roomID,
        ':uid' => $userID
    ]);

    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB Error', 'details' => $e->getMessage()]);
}
?>