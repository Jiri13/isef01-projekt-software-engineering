<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
require __DIR__ . '/dbConnection.php';
require __DIR__ . '/encryptionKey.php'; // <--- NEU

$roomID = isset($_GET['roomID']) ? (int)$_GET['roomID'] : 0;

if ($roomID <= 0) {
    echo json_encode([]);
    exit;
}

try {
    $sql = "
        SELECT 
            cm.messageID,
            cm.message,
            cm.created_at,
            cm.userID,
            u.first_name,
            u.last_name
        FROM chatmessage cm
        JOIN users u ON cm.userID = u.userID
        WHERE cm.roomID = :rid
        ORDER BY cm.created_at ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':rid' => $roomID]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Array durchlaufen und Nachrichten entschlüsseln
    foreach ($messages as &$msg) {
        $msg['message'] = decryptMessage($msg['message']);
    }
    unset($msg); // Referenz lösen

    echo json_encode($messages);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Fehler beim Laden', 'details' => $e->getMessage()]);
}
?>