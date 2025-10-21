<?php
// api/deleteQuestion.php
// [WHY] Löscht eine Frage.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {exit;}
require __DIR__ . '/dbConnection.php';

$input = json_decode(file_get_contents('php://input'), true);
$questionID = isset($input['questionID']) ? (int)$input['questionID'] : 0;

if ($questionID <= 0 ) { http_response_code(400); echo json_encode(['error'=>'questionID required']); exit; }


try {
    $pdo->beginTransaction();
    $pdo->prepare("DELETE FROM Question_Option WHERE questionID = :qid")->execute([':qid' => $questionID]);
    $pdo->prepare("DELETE FROM Question WHERE questionID = :qid")->execute([':qid' => $questionID]);
    $pdo->commit();
    echo json_encode(['status' => 'success']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Delete failed', 'details' => $e->getMessage()]);
}
