<?php
// api/listQuizzes.php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/dbConnection.php';

$sql = "
  SELECT
    quizID   AS id,
    title    AS name,
    quiz_description AS description,
    category,
    time_limit
  FROM quiz
  ORDER BY title
";
$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($rows);
