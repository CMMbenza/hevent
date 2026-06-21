<?php

require_once __DIR__ . '/env.php';

$host = env('DB_HOST');
$dbname = env('DB_NAME');
$username = env('DB_USER');
$password = env('DB_PASS');
$charset = env('DB_CHARSET', 'utf8mb4');

try {

    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset={$charset}",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

} catch (PDOException $e) {

    die("Erreur de connexion : " . $e->getMessage());

}