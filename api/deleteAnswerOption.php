<?php
// api/deleteAnswerOption.php
// Entfernt eine spezifische Antwortoption. Prüft auf Moderationsrechte / Ersteller optional.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }
require __DIR__ . '/dbConnection.php';

$input = json_decode(file_get_contents('php://input'), true);
$optionID = isset($input['optionID']) ? (int)$input['optionID'] : 0;
$userID = isset($input['userID']) ? (int)$input['userID'] : 0; // der, der die Löschung anfordert

if ($optionID <= 0 || $userID <= 0) { http_response_code(400); echo json_encode(['error'=>'optionID and userID required']); exit; }

// Prüfe Existenz und ermittele Frage und Creator
$st = $pdo->prepare("SELECT ao.questionID, q.creatorID FROM AnswerOption ao JOIN Question q ON q.questionID = ao.questionID WHERE ao.optionID = :oid LIMIT 1");
$st->execute([':oid'=>$optionID]);
$row = $st->fetch();
if (!$row) { http_response_code(404); echo json_encode(['error'=>'option not found']); exit; }

$allowed = false;
if ((int)$row['creatorID'] === $userID) {
    $allowed = true; // Frage-Ersteller darf Optionen entfernen
} else {
    $st2 = $pdo->prepare("SELECT isModerator FROM Users WHERE userID = :u LIMIT 1");
    $st2->execute([':u'=>$userID]);
    $r = $st2->fetch();
    if ($r && !empty($r['isModerator'])) {
        $allowed = true;
    }
}
if (!$allowed) { http_response_code(403); echo json_encode(['error'=>'Forbidden: not allowed to delete option']); exit; }

try {
    $pdo->prepare("DELETE FROM AnswerOption WHERE optionID = :oid")->execute([':oid'=>$optionID]);
    echo json_encode(['ok'=>true,'deletedOptionID'=>$optionID]);
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['error'=>'Delete failed','details'=>$e->getMessage()]);
}

