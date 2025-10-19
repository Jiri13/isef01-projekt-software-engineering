
<?php
// api/listTopRatedQuestions.php
// Gibt top N Fragen nach durchschnittlicher Bewertung zurück (für Gamification / Curation)


header('Content-Type: application/json');
require __DIR__ . '/dbConnection.php';

$limit = isset($_GET['limit']) ? max(1,(int)$_GET['limit']) : 20;

$sql = "SELECT q.questionID, q.question_text, COALESCE(qr.avg_rating,0) AS avg_rating, COALESCE(qr.cnt,0) AS rating_count
        FROM Question q
        LEFT JOIN (SELECT questionID, AVG(rating) AS avg_rating, COUNT(*) AS cnt FROM QuestionRating GROUP BY questionID) qr ON qr.questionID = q.questionID
        ORDER BY qr.avg_rating DESC, qr.cnt DESC
        LIMIT :lim";

$st = $pdo->prepare($sql);
$st->bindValue(':lim',(int)$limit,PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll();
$out = array_map(function($r){ return ['questionID'=>(int)$r['questionID'],'text'=>$r['question_text'],'avgRating'=>(float)$r['avg_rating'],'ratingCount'=>(int)$r['rating_count']]; }, $rows);

echo json_encode($out);
