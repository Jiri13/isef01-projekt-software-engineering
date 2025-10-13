<?php
$dsn = 'mysql:host=localhost;dbname=if0_39994504_defaultdb;charset=utf8mb4';
$user = 'root';
$pass = '';
// [WARN] Zugangsdaten hartkodiert und User 'root' – in Prod via ENV-Variablen oder Secret-Manager laden,
//        und nur minimal nötige Rechte vergeben (Least Privilege).

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // [HOW] Exceptions statt stiller Fehler für robustes Error-Handling
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,   // [WHY] Rückgabe als assoziative Arrays, spart manuelles Index-Mapping
];

// [IO] Baut DB-Verbindung auf. Bei Fehler: PDOException (z. B. falsche Credentials, DB offline).
// [ERR] Kein try/catch hier — Exception propagiert nach oben, um im API-Handler zentral behandelt zu werden.
$pdo = new PDO($dsn, $user, $pass, $options);
