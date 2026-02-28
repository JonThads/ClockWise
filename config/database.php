<?php

session_start();

$host = "localhost";
$database = "clockwise";
$db_user = "root";
$db_password = "";
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$database;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE                   => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE        => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES          => false,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_password, $options);
} catch (PDOException $e) {
    die('Database Connection Failed: ' . $e->getMessage());
}