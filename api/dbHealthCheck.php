<?php
// [WHY] Health-Check: prüft, ob die DB erreichbar ist und grundlegende PHP-Komponenten verfügbar sind.
// [HOW] Versucht einfachen Query (`SELECT 1`) und gibt technische Basisinfos zurück.

header('Content-Type: application/json');

try {
    require __DIR__ . '/dbConnection.php'; // [IO] Stellt PDO-Verbindung her; Exception bei Verbindungsfehlern (ERRMODE_EXCEPTION aktiv)

    $ok = $pdo->query('SELECT 1')->fetchColumn(); // [WHY] Minimaler Test-Query zur Verifizierung der DB-Konnektivität

    echo json_encode([
        'ok' => (bool)$ok,                          // [ASSUME] true = DB antwortet; false = kein Ergebnis
        'php' => PHP_VERSION,                       // [WARN] Leakt PHP-Versionsinfo → nur in internen Systemen nutzen
        'pdo_mysql' => extension_loaded('pdo_mysql')// [HOW] Prüft, ob MySQL-PDO-Erweiterung aktiv ist
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()                 // [WARN] Gibt interne Fehlertexte aus; in Prod durch generische Msg ersetzen
    ]);
}
