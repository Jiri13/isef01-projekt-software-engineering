<?php
// api/submitMultiplayerAnswer.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require __DIR__ . '/dbConnection.php';

$input = json_decode(file_get_contents('php://input'), true);

$roomID     = (int)($input['roomID'] ?? 0);
$userID     = (int)($input['userID'] ?? 0);
$questionID = (int)($input['questionID'] ?? 0);
$isCorrect  = !empty($input['isCorrect']) ? 1 : 0;

// FIX: optionID muss NULL sein, wenn sie 0 oder leer ist
$rawOptionID = isset($input['optionID']) ? (int)$input['optionID'] : 0;
$optionID    = ($rawOptionID > 0) ? $rawOptionID : null;

if ($roomID <= 0 || $userID <= 0 || $questionID <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Raum-Historie
    $stmtInsert = $pdo->prepare("
        INSERT INTO roomanswer (is_correct, roomID, questionID, userID, optionID)
        VALUES (:correct, :rid, :qid, :uid, :oid)
    ");
    // Wichtig: Wir binden die Parameter explizit, damit NULL korrekt als SQL-NULL ankommt
    $stmtInsert->bindValue(':correct', $isCorrect, PDO::PARAM_INT);
    $stmtInsert->bindValue(':rid',     $roomID,    PDO::PARAM_INT);
    $stmtInsert->bindValue(':qid',     $questionID,PDO::PARAM_INT);
    $stmtInsert->bindValue(':uid',     $userID,    PDO::PARAM_INT);
    $stmtInsert->bindValue(':oid',     $optionID,  $optionID === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmtInsert->execute();

    // 2. Raum-Punkte
    if ($isCorrect === 1) {
        $stmtUpdate = $pdo->prepare("
            UPDATE roomparticipant 
            SET points = points + 1 
            WHERE roomID = :rid AND userID = :uid
        ");
        $stmtUpdate->execute([':rid' => $roomID, ':uid' => $userID]);
    }

    // 3. Globale Statistik
    $stmtGlobal = $pdo->prepare("
        INSERT INTO statistics (userID, questionID, optionID, is_correct, answered_at)
        VALUES (:uid, :qid, :oid, :correct, NOW())
    ");
    $stmtGlobal->bindValue(':uid',     $userID,    PDO::PARAM_INT);
    $stmtGlobal->bindValue(':qid',     $questionID,PDO::PARAM_INT);
    $stmtGlobal->bindValue(':oid',     $optionID,  $optionID === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmtGlobal->bindValue(':correct', $isCorrect, PDO::PARAM_INT);
    $stmtGlobal->execute();

    $pdo->commit();
    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'DB Error', 'details' => $e->getMessage()]);
}