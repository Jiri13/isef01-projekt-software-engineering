<?php
// api/listQuizQuestions.php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/dbConnection.php';

$quizID = isset($_GET['quizID']) ? (int)$_GET['quizID'] : 0;
if ($quizID <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'quizID required']);
    exit;
}

$sql = "
  SELECT
    qq.quizID,
    qq.questionID      AS id,
    qq.sort_order,
    q.question_text    AS text,
    q.question_type    AS type,
    q.difficulty       AS difficulty
  FROM quizquestion qq
  JOIN question q ON q.questionID = qq.questionID
  WHERE qq.quizID = :qid
  ORDER BY COALESCE(qq.sort_order, 9999), q.questionID
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':qid' => $quizID]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as &$r) {
    $r['difficulty'] = strtolower($r['difficulty'] ?? 'Easy');
}

echo json_encode($rows);
