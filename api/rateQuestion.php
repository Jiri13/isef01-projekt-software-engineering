<?php
// api/rateQuestion.php
// Speichert eine 1..5 Bewertung für eine Frage; aktualisiert Durchschnitt (aber AVG kann bei Abfrage auch berechnet werden).


header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
require __DIR__ . '/dbConnection.php';

$input = json_decode(file_get_contents('php://input'), true);
$questionID = isset($input['questionID']) ? (int)$input['questionID'] : 0;
$userID = isset($input['userID']) ? (int)$input['userID'] : 0;
$rating = isset($input['rating']) ? (int)$input['rating'] : 0;

// Mindestangaben prüfen und Wertebereich bei Sternebewertung
if ($questionID <= 0 || $userID <= 0 || $rating < 1 || $rating > 5) { http_response_code(400); echo json_encode(['error'=>'questionID, userID and rating(1-5) required']); exit; }

try {
    // Upsert: existing rating update sonst insert
    $pdo->beginTransaction();
    $st = $pdo->prepare("SELECT ratingID FROM QuestionRating WHERE questionID = :q AND userID = :u LIMIT 1");
    $st->execute([':q'=>$questionID, ':u'=>$userID]);
    $row = $st->fetch();
    if ($row) {
        $upd = $pdo->prepare("UPDATE QuestionRating SET rating = :r, rated_at = :ra WHERE ratingID = :rid");
        $upd->execute([':r'=>$rating, ':ra'=>date('Y-m-d H:i:s'), ':rid'=>$row['ratingID']]);
    } else {
        $ins = $pdo->prepare("INSERT INTO QuestionRating (questionID, userID, rating, rated_at) VALUES (:q, :u, :r, :ra)");
        $ins->execute([':q'=>$questionID, ':u'=>$userID, ':r'=>$rating, ':ra'=>date('Y-m-d H:i:s')]);
    }
    $pdo->commit();
    echo json_encode(['ok'=>true]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500); echo json_encode(['error'=>'Rating failed','details'=>$e->getMessage()]);
}
