<?php
// api/saveSingleAnswer.php
// Zweck:
// Speichert die Antwort eines eingeloggten Benutzers auf eine einzelne Frage
// im Einzelspielermodus (Statistik / Auswertung).
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

// Session starten, um den eingeloggten Benutzer zu identifizieren
session_start();
require __DIR__ . '/dbConnection.php';

// 1) Authentifizierung prüfen
if (empty($_SESSION['userID'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}
// 2) JSON-Body auslesen
$input = json_decode(file_get_contents('php://input'), true);

// Benutzer-ID kommt ausschließlich aus der Session (Manipulationsschutz)
$userID     = (int)$_SESSION['userID']; 
// Pflichtfeld: ID der beantworteten Frage
$questionID = (int)($input['questionID'] ?? 0);
// Gibt an, ob die Antwort korrekt war (0 oder 1)
$isCorrect  = !empty($input['isCorrect']) ? 1 : 0;

$rawOptionID = isset($input['optionID']) ? (int)$input['optionID'] : 0;
$optionID    = ($rawOptionID > 0) ? $rawOptionID : null;

// 3) Eingabevalidierung
if ($questionID <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'questionID required']);
    exit;
}

try {
    // 4) Antwort in der Statistik-Tabelle speichern
    $stmt = $pdo->prepare("
        INSERT INTO statistics (userID, questionID, optionID, is_correct, answered_at)
        VALUES (:uid, :qid, :oid, :correct, NOW())
    ");

    $stmt->bindValue(':uid',     $userID,      PDO::PARAM_INT);
    $stmt->bindValue(':qid',     $questionID,  PDO::PARAM_INT);
    $stmt->bindValue(':oid',     $optionID,    $optionID === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue(':correct', $isCorrect,   PDO::PARAM_INT);

    $stmt->execute();

    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB Error', 'details' => $e->getMessage()]);
}
