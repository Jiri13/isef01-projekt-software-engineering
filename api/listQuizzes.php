<?php
// api/listQuizzes.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require __DIR__ . '/dbConnection.php';

try {
    // Lädt eine Liste aller Quizze (für Dropdowns bei der Raumerstellung)
    $stmt = $pdo->query("
        SELECT
            quizID,
            title,
            category,
            time_limit
        FROM quiz
        ORDER BY created_at DESC
    ");

    $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Falls keine Quizze da sind, leeres Array zurückgeben
    echo json_encode($quizzes ?: []);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Fehler beim Laden der Quiz-Liste', 'details' => $e->getMessage()]);
}