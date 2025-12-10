<?php
// api/getRoomDetailsStats.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
require __DIR__ . '/dbConnection.php';

$roomID = isset($_GET['roomID']) ? (int)$_GET['roomID'] : 0;
$userID = isset($_GET['userID']) ? (int)$_GET['userID'] : 0;

if ($roomID <= 0) {
    echo json_encode(['error' => 'roomID required']);
    exit;
}

try {
    // 1. Hole alle Teilnehmer des Raumes, sortiert nach Punkten
    $sql = "
        SELECT 
            rp.userID, 
            rp.points,
            u.first_name, 
            u.last_name
        FROM roomparticipant rp
        JOIN users u ON rp.userID = u.userID
        WHERE rp.roomID = :rid
        ORDER BY rp.points DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':rid' => $roomID]);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$participants) {
        $participants = []; // Leeres Array falls keine Teilnehmer (sollte nicht passieren)
    }

    // 2. Besten Spieler ermitteln (für Kompetitiv)
    $bestPlayer = !empty($participants) ? $participants[0] : null;

    // 3. Eigenen Rang und Punkte finden
    $myRank = 0;
    $myStats = null;
    $teamTotalPoints = 0; // NEU: Summe für Kooperativ

    foreach ($participants as $index => $p) {
        // Summe berechnen
        $teamTotalPoints += (int)$p['points'];

        if ((int)$p['userID'] === $userID) {
            $myRank = $index + 1;
            $myStats = $p;
        }
    }

    echo json_encode([
        'bestPlayerName' => $bestPlayer ? ($bestPlayer['first_name'] . ' ' . $bestPlayer['last_name']) : '-',
        'bestPlayerPoints' => $bestPlayer ? (int)$bestPlayer['points'] : 0,
        'myRank' => $myRank,
        'myPoints' => $myStats ? (int)$myStats['points'] : 0,
        'totalParticipants' => count($participants),
        'teamTotalPoints' => $teamTotalPoints, // NEU: Das Feld geben wir zurück
        'leaderboard' => array_slice($participants, 0, 10) // Top 10
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB Error', 'details' => $e->getMessage()]);
}