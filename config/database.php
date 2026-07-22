<?php

require_once __DIR__ . "/env.php";

try {
    $host = $_ENV["DB_HOST"];
    $port = $_ENV["DB_PORT"];
    $dbname = $_ENV["DB_NAME"];
    $user = $_ENV["DB_USER"];
    $password = $_ENV["DB_PASS"];

    $conn = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname",
        $user,
        $password
    );

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}