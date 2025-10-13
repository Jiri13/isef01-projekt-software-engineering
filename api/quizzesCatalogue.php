<?php
// api/quizzesCatalogue.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require __DIR__ . '/dbConnection.php';

$userID = isset($_GET['userID']) ? (int)$_GET['userID'] : 0;
if ($userID <= 0) { http_response_code(400); echo json_encode(['error'=>'userID required']); exit; }

$sql = "
SELECT 
  q.quizID,
  q.title,
  q.quiz_description,
  q.category,
  q.time_limit        AS timeLimit,
  q.userID,
  u.first_name        AS creatorName
FROM Quiz q
LEFT JOIN Users u ON u.userID = q.userID
WHERE q.userID = $userID
ORDER BY q.quizID DESC
";

$st = $pdo->query($sql);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($rows);
