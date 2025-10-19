<?php
//verst.
// api/addQuestion.php
// [WHY] Endpoint zum Anlegen einer Frage. Unterstützt direktes Mitliefern von Antwortoptionen (Array)
// oder das reine Anlegen einer Frage (kollaboratives Workflow).
// Liefert questionID zurück.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
// Abfangen von Preflight OPTIONS Anfragen
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

// DB-Verbindung herstellen
require __DIR__ . '/dbConnection.php'; // erwartet $pdo (PDO)

//Eingabedaten parsen
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { http_response_code(400); echo json_encode(['error'=>'Invalid or missing JSON body']); exit; }

/* Expected JSON:
{
  "moduleID": 123,           // required - welcher Fragenkatalog / Modul / Quizz
  "questionText": "...",    // required
  "questionType": "mc|text",        // optional, default 'mc'
  "difficulty": "easy|medium|hard", // optional, default 'medium'
  "creatorID": 5,            // required
  "options": [               // optional - array of {"text":"...","isCorrect":true|false,"explanation":"..."}
      {"text":"A","isCorrect":false},
      ...
  ]
}
*/

//Werte aus dem JSON herausziehen und Standardwerte setzen
$moduleID = isset($input['moduleID']) ? (int)$input['moduleID'] : 0;
$questionText = trim((string)($input['questionText'] ?? ''));
$typeIn = strtolower(trim((string)($input['questionType'] ?? 'mc')));
$validTypes = ['mc','text'];
$type = in_array($typeIn, $validTypes, true) ? $typeIn : 'mc';
$diffIn = strtolower(trim((string)($input['difficulty'] ?? 'medium')));
$validDiffs = ['easy','medium','hard'];
$difficulty = in_array($diffIn, $validDiffs, true) ? $diffIn : 'medium';
$creatorID = isset($input['creatorID']) ? (int)$input['creatorID'] : 0;
$options = isset($input['options']) && is_array($input['options']) ? $input['options'] : [];

// Mindestangaben prüfen
if ($moduleID <= 0 || $questionText === '' || $creatorID <= 0) {
    http_response_code(400);
    echo json_encode(['error'=>'moduleID, questionText and creatorID are required']);
    exit;
}

try {
    // Begin TX: Frage + Optionen atomar anlegen
    $pdo->beginTransaction();

    // Optional: prüfen, ob Modul existiert
    $stM = $pdo->prepare("SELECT 1 FROM Module WHERE moduleID = :m LIMIT 1");
    $stM->execute([':m'=>$moduleID]);
    if (!$stM->fetch()) {  // Wenn kein Modul gefunden wurde
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error'=>'moduleID not found']);
        exit;
    }

    // Creator prüfen
    $stU = $pdo->prepare("SELECT 1 FROM Users WHERE userID = :u LIMIT 1");
    $stU->execute([':u'=>$creatorID]);
    if (!$stU->fetch()) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error'=>'creatorID not found']);
        exit;
    }

    // Frage anlegen
    $insQ = $pdo->prepare("INSERT INTO Question (moduleID, question_text, questionType, difficulty, creatorID, created_at) VALUES (:m, :q, :t, :d, :c, :ca)");
    $insQ->execute([
        ':m'=>$moduleID,
        ':q'=>$questionText,
        ':t'=>ucfirst($type), // DB-Konvention
        ':d'=>ucfirst($difficulty),
        ':c'=>$creatorID,
        ':ca'=>date('Y-m-d H:i:s')
    ]);

    //liefert die automatisch erzeugte ID des gerade eingefügten Datensatzes
    $questionID = (int)$pdo->lastInsertId();

    // Optionen anlegen falls übergeben (nur bei Multiple-Choice)
    if ($type === 'mc' && !empty($options)) {
        $insOpt = $pdo->prepare("INSERT INTO AnswerOption (questionID, option_text, is_correct, explanation) VALUES (:q, :ot, :ic, :ex)");
        foreach ($options as $opt) {
            $optText = trim((string)($opt['text'] ?? ''));
            if ($optText === '') { continue; } // skip leere Optionen
            $isCorrect = !empty($opt['isCorrect']) ? 1 : 0;
            $ex = isset($opt['explanation']) ? trim((string)$opt['explanation']) : null;
            $insOpt->execute([':q'=>$questionID, ':ot'=>$optText, ':ic'=>$isCorrect, ':ex'=>$ex]);
        }
    }

    //Schließt die Transaktion ab.
    $pdo->commit();
    //Gibt die Antwort an den Client zurück (als JSON).
    echo json_encode(['ok'=>true, 'questionID'=>$questionID]);
  
//Fehlerbehandlung
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error'=>'Insert failed', 'details'=>$e->getMessage()]);
}
