<?php
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
if (!$input) {
    http_response_code(400);
    echo json_encode(['error'=>'Invalid or missing JSON body']); 
    exit;
}

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
$quizID = isset($input['quizID']) ? (int)$input['quizID'] : 0;
$question_text = trim((string)($input['question_text'] ?? ''));
$typeIn = strtolower(trim((string)($input['questionType'] ?? 'mc')));
$validTypes = ['mc','text'];
$type = in_array($typeIn, $validTypes, true) ? $typeIn : 'mc';
$diffIn = strtolower(trim((string)($input['difficulty'] ?? 'medium')));
$validDiffs = ['easy','medium','hard'];
$difficulty = in_array($diffIn, $validDiffs, true) ? $diffIn : 'medium';
$userID = isset($input['userID']) ? (int)$input['userID'] : 0;
$options = isset($input['options']) && is_array($input['options']) ? $input['options'] : [];

// Mindestangaben prüfen
if ($quizID <= 0 || $question_text === '' || $userID <= 0) {
    http_response_code(400);
    echo json_encode(['error'=>'moduleID, questionText and userID are required']);
    exit;
}

try {
    // Begin TX: Frage + Optionen atomar anlegen
    $pdo->beginTransaction();

    // Optional: prüfen, ob Quiz existiert
    $stM = $pdo->prepare("SELECT 1 FROM Quiz WHERE quizID = :m LIMIT 1");
    $stM->execute([':m'=>$quizID]);
    if (!$stM->fetch()) {  // Wenn kein Quiz gefunden wurde
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error'=>'quizID not found']);
        exit;
    }

    // User prüfen
    $stU = $pdo->prepare("SELECT 1 FROM Users WHERE userID = :u LIMIT 1");
    $stU->execute([':u'=>$userID]);
    if (!$stU->fetch()) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error'=>'userID not found']);
        exit;
    }

    // Frage anlegen
    $insQ = $pdo->prepare("INSERT INTO Question (quizID, question_text, questionType, difficulty, userID, created_at) VALUES (:m, :q, :t, :d, :c, :ca)");
    $insQ->execute([
        ':m'=>$moduleID,
        ':q'=>$question_text,
        ':t'=>ucfirst($type), // DB-Konvention
        ':d'=>ucfirst($difficulty),
        ':c'=>$userID,
        ':ca'=>date('Y-m-d H:i:s')
    ]);

    //liefert die automatisch erzeugte ID des gerade eingefügten Datensatzes
    $questionID = (int)$pdo->lastInsertId();

    // Optionen anlegen falls übergeben (nur bei Multiple-Choice)
    if ($type === 'mc' && !empty($options)) {
        $insOpt = $pdo->prepare("INSERT INTO Question_Option (questionID, option_text, is_correct, explanation) VALUES (:q, :ot, :ic, :ex)");
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
