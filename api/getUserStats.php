<?php
// api/getUserStats.php
// Liefert aggregierte Statistiken + Globalen Rang für einen User

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
require __DIR__ . '/dbConnection.php';

$userID = isset($_GET['userID']) ? (int)$_GET['userID'] : 0;
if ($userID <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'userID required']);
    exit;
}

try {
    // 1. Eigene Stats abrufen
    // -----------------------------------

    // Multiplayer (Tabelle userquizstats)
    $sqlMP = "
        SELECT
            COALESCE(SUM(games_played), 0)      AS gamesPlayed
        FROM userquizstats
        WHERE userID = :uid
    ";
    $stmtMP = $pdo->prepare($sqlMP);
    $stmtMP->execute([':uid' => $userID]);
    $mpStats = $stmtMP->fetch(PDO::FETCH_ASSOC);

    // Singleplayer (Tabelle statistics)
    $sqlSP = "
        SELECT
            COUNT(*)                     AS totalAnswers,
            COALESCE(SUM(is_correct), 0) AS correctAnswers
        FROM statistics
        WHERE userID = :uid
    ";
    $stmtSP = $pdo->prepare($sqlSP);
    $stmtSP->execute([':uid' => $userID]);
    $spStats = $stmtSP->fetch(PDO::FETCH_ASSOC);

    // Zusammenrechnen
    $myTotalCorrect = (int)$spStats['correctAnswers'];
    $myTotalAnswers = (int)$spStats['totalAnswers'];
    $myWrong        = ((int)$spStats['totalAnswers'] - (int)$spStats['correctAnswers']);
    $myGamesPlayed  = (int)$mpStats['gamesPlayed'];


    // 2. Globalen Rang berechnen
    // --------------------------
    // Logik: Zählen, wie viele User mehr richtige Antworten haben als ich.
    // Rang = (Anzahl besserer User) + 1

    $sqlRank = "
        SELECT COUNT(*) + 1 AS ranking
        FROM (
            SELECT u.userID,
                   (
                     COALESCE((SELECT SUM(is_correct) FROM statistics WHERE userID = u.userID), 0)
                   ) as total_score
            FROM users u
        ) as all_scores
        WHERE total_score > :myScore
    ";

    $stmtRank = $pdo->prepare($sqlRank);
    $stmtRank->execute([':myScore' => $myTotalCorrect]);
    $rank = (int)$stmtRank->fetchColumn();


    // 3. Ergebnis senden
    echo json_encode([
        'gamesPlayed'    => $myGamesPlayed,
        'totalAnswers'   => $myTotalAnswers,
        'correctAnswers' => $myTotalCorrect,
        'wrongAnswers'   => $myWrong,
        'rank'           => $rank
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB Error', 'details' => $e->getMessage()]);
}