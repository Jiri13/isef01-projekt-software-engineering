<?php
// api/saveSingleAnswer.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

session_start();
require __DIR__ . '/dbConnection.php';

if (empty($_SESSION['userID'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$userID     = (int)$_SESSION['userID']; //  nur aus Session!
$questionID = (int)($input['questionID'] ?? 0);
$isCorrect  = !empty($input['isCorrect']) ? 1 : 0;

$rawOptionID = isset($input['optionID']) ? (int)$input['optionID'] : 0;
$optionID    = ($rawOptionID > 0) ? $rawOptionID : null;

if ($questionID <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'questionID required']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO statistics (userID, questionID, optionID, is_correct, answered_at)
        VALUES (:uid, :qid, :oid, :correct, NOW())
    ");

    $stmt->bindValue(':uid',     $userID,      PDO::PARAM_INT);
    $stmt->bindValue(':qid',     $questionID,  PDO::PARAM_INT);
    $stmt->bindValue(':oid',     $optionID,    $optionID === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue(':correct', $isCorrect,   PDO::PARAM_INT);

    $stmt->execute();

    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB Error', 'details' => $e->getMessage()]);
}
