<?php
// api/deleteQuestion.php
// [WHY] Löscht eine Frage. Nur Creator oder Moderator darf dauerhaft löschen. Fragen, die automatisch entfernt werden
// (zu schlechte Bewertung) könnten hierüber ebenfalls gelöscht werden (z. B. Cron/autoRemove ruft endpunkt intern oder führt Query direkt aus).


header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {exit;}
require __DIR__ . '/dbConnection.php';

$input = json_decode(file_get_contents('php://input'), true);
$questionID = isset($input['questionID']) ? (int)$input['questionID'] : 0;
$userID = isset($input['userID']) ? (int)$input['userID'] : 0;

if ($questionID <= 0 || $userID <= 0) { http_response_code(400); echo json_encode(['error'=>'questionID and userID required']); exit; }

// Rechte check
$st = $pdo->prepare("SELECT creatorID FROM Question WHERE questionID = :q LIMIT 1");
$st->execute([':q'=>$questionID]);
$q = $st->fetch();
if (!$q) { http_response_code(404); echo json_encode(['error'=>'question not found']); exit; }

$allowed = false;
if ((int)$q['creatorID'] === $userID) {$allowed = true;}
else {
    $st2 = $pdo->prepare("SELECT isModerator FROM Users WHERE userID = :u LIMIT 1");
    $st2->execute([':u'=>$userID]);
    $row = $st2->fetch();
    if ($row && !empty($row['isModerator'])) {$allowed = true;}
}
if (!$allowed) { http_response_code(403); echo json_encode(['error'=>'Forbidden: not allowed to delete']); exit; }

try {
    $pdo->beginTransaction();
    // Lösche Optionen, Bewertungen, sonstige Abhängigkeiten
    $pdo->prepare("DELETE FROM AnswerOption WHERE questionID = :q")->execute([':q'=>$questionID]);
    $pdo->prepare("DELETE FROM QuestionRating WHERE questionID = :q")->execute([':q'=>$questionID]);
    $pdo->prepare("DELETE FROM Question WHERE questionID = :q")->execute([':q'=>$questionID]);
    $pdo->commit();
    echo json_encode(['ok'=>true,'deletedQuestionID'=>$questionID]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error'=>'Delete failed','details'=>$e->getMessage()]);
}
