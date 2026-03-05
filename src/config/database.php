<?php

session_start();

require_once __DIR__ . '/env.php';

$host        = $_ENV['DB_HOST'];
$database    = $_ENV['DB_DATABASE'];
$db_user     = $_ENV['DB_USERNAME'];
$db_password = $_ENV['DB_PASSWORD'];
$charset     = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$database;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_password, $options);
} catch (PDOException $e) {
    die('Database Connection Failed: ' . $e->getMessage());
}