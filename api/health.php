<?php
// [WHY] Health-Check: prüft DB-Konnektivität und liefert Basisdiagnose (PHP-Version, pdo_mysql)

header('Content-Type: application/json');
try {
    require __DIR__ . '/db.php'; // [IO] Verbindet zur DB; Exception bei Fehlern (ERRMODE_EXCEPTION)
    $ok = $pdo->query('SELECT 1')->fetchColumn();
    echo json_encode([
        'ok' => (bool)$ok,
        'php' => PHP_VERSION,                          // [WARN] Leakt Versionsinfos; Prod ggf. entfernen
        'pdo_mysql' => extension_loaded('pdo_mysql')
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);  // [WARN] Fehlermeldung kann interne Details leaken
}
