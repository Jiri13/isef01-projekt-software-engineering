<?php
// api/autoRemoveLowRatedQuestions.php
// Skript, das (z.B. per Cron) ausgeführt wird, um Fragen mit zu schlechter Bewertung zu entfernen.
// Kriterien: minReviews, averageRatingThreshold, optional: ageThresholdInDays

// CLI-or-HTTP capable script. No CORS headers here if run via CLI.
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
}
require __DIR__ . '/dbConnection.php';

// Configurable thresholds
$minReviews = 3;
$avgThreshold = 2.0; // Durchschnitt unter 2.0 => entfernen
$ageDays = 7; // Frage muss älter als X Tage sein um gelöscht zu werden

try {
    $cutoff = date('Y-m-d H:i:s', strtotime("-{$ageDays} days"));
    // Select questions that meet criteria
    $sql = "SELECT q.questionID FROM Question q JOIN (SELECT questionID, AVG(rating) AS avg_rating, COUNT(*) AS cnt FROM QuestionRating GROUP BY questionID) qr ON qr.questionID = q.questionID WHERE qr.cnt >= :minr AND qr.avg_rating < :avg AND q.created_at <= :cutoff";
    $st = $pdo->prepare($sql);
    $st->execute([':minr'=>$minReviews, ':avg'=>$avgThreshold, ':cutoff'=>$cutoff]);
    $toDelete = $st->fetchAll();
    $deleted = [];
    if (!empty($toDelete)) {
        $pdo->beginTransaction();
        $delQ = $pdo->prepare("DELETE FROM Question WHERE questionID = :q");
        $delOpt = $pdo->prepare("DELETE FROM AnswerOption WHERE questionID = :q");
        $delRat = $pdo->prepare("DELETE FROM QuestionRating WHERE questionID = :q");
        foreach ($toDelete as $d) {
            $qid = (int)$d['questionID'];
            $delOpt->execute([':q'=>$qid]);
            $delRat->execute([':q'=>$qid]);
            $delQ->execute([':q'=>$qid]);
            $deleted[] = $qid;
        }
        $pdo->commit();
    }
    $out = ['ok'=>true,'deletedQuestions'=>$deleted];
    echo json_encode($out);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error'=>'auto-remove failed','details'=>$e->getMessage()]);
}

