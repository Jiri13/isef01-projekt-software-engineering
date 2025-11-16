<?php
// api/listQuestions.php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/dbConnection.php';

$sql = "
  SELECT
    q.questionID    AS id,
    q.question_text AS text,
    q.question_type AS type,
    q.difficulty    AS difficulty,
    q.quizID        AS legacy_quizID,   -- nur noch historisch
    q.userID        AS userID,
    q.created_at    AS createdAt
  FROM question q
  ORDER BY q.questionID
";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Difficulty auf lowercase normalisieren:
foreach ($rows as &$r) {
    $r['difficulty'] = strtolower($r['difficulty'] ?? 'Easy'); // 'easy','medium','hard'
}

echo json_encode($rows);
