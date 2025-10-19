<?php
// api/updateQuestion.php
// [WHY] Endpoint zum Bearbeiten einer bestehenden Frage (Text, Schwierigkeit, Typ, Zuordnung zu Modul)
// Nur der Ersteller oder ein Moderator darf editieren.


header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {exit;}
require __DIR__ . '/dbConnection.php';

$input = json_decode(file_get_contents('php://input'), true);
$questionID = isset($input['questionID']) ? (int)$input['questionID'] : 0;
$editorID = isset($input['editorID']) ? (int)$input['editorID'] : 0;
$questionText = array_key_exists('questionText', $input) ? trim((string)$input['questionText']) : null;
$diffIn = array_key_exists('difficulty', $input) ? strtolower(trim((string)$input['difficulty'])) : null;
$typeIn = array_key_exists('type', $input) ? strtolower(trim((string)$input['type'])) : null;
$moduleID = array_key_exists('moduleID', $input) ? (int)$input['moduleID'] : null;

if ($questionID <= 0 || $editorID <= 0) { http_response_code(400); echo json_encode(['error'=>'questionID and editorID required']); exit; }

// Fetch question + creator
$st = $pdo->prepare("SELECT creatorID FROM Question WHERE questionID = :q LIMIT 1");
$st->execute([':q'=>$questionID]);
$q = $st->fetch();
if (!$q) { http_response_code(404); echo json_encode(['error'=>'question not found']); exit; }

// Rechte: editor == creator OR moderator flag in Users (angenommen 'isModerator')
$allowed = false;
if ((int)$q['creatorID'] === $editorID) { $allowed = true; }
else {
    $st2 = $pdo->prepare("SELECT isModerator FROM Users WHERE userID = :u LIMIT 1");
    $st2->execute([':u'=>$editorID]);
    $row = $st2->fetch();
    if ($row && !empty($row['isModerator'])) { $allowed = true; }
}
if (!$allowed) { http_response_code(403); echo json_encode(['error'=>'Forbidden: not allowed to edit']); exit; }

$fields = [];
$params = [':q'=>$questionID];
if ($questionText !== null) { $fields[] = 'question_text = :qt'; $params[':qt'] = $questionText; }
if ($diffIn !== null) { $validDiffs = ['easy','medium','hard']; $d = in_array($diffIn,$validDiffs,true) ? ucfirst($diffIn) : null; if ($d) { $fields[] = 'difficulty = :d'; $params[':d'] = $d; } }
if ($typeIn !== null) { $validTypes=['mc','text']; $t = in_array($typeIn,$validTypes,true) ? ucfirst($typeIn) : null; if ($t) { $fields[] = 'type = :t'; $params[':t'] = $t; } }
if ($moduleID !== null) { $fields[] = 'moduleID = :m'; $params[':m'] = $moduleID; }

if (empty($fields)) { echo json_encode(['ok'=>true, 'note'=>'nothing to update']); exit; }

$sql = 'UPDATE Question SET ' . implode(', ', $fields) . ' WHERE questionID = :q';
try {
    $pdo->beginTransaction();
    // optional: module existence check if moduleID updated
    if (isset($params[':m'])) {
        $stM = $pdo->prepare("SELECT 1 FROM Module WHERE moduleID = :m LIMIT 1");
        $stM->execute([':m'=>$params[':m']]);
        if (!$stM->fetch()) { $pdo->rollBack(); http_response_code(400); echo json_encode(['error'=>'moduleID not found']); exit; }
    }
    $upd = $pdo->prepare($sql);
    $upd->execute($params);
    $pdo->commit();
    echo json_encode(['ok'=>true, 'questionID'=>$questionID]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error'=>'Update failed','details'=>$e->getMessage()]);
}
