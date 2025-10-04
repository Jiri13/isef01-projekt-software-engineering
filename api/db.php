<?php

// api/db.php
$dsn = 'mysql:host=sql110.infinityfree.com;dbname=if0_39994504_defaultdb;charset=utf8mb4';
$user = 'if0_39994504';
$pass = 'eWMOjOFiJu';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
$pdo = new PDO($dsn, $user, $pass, $options);
