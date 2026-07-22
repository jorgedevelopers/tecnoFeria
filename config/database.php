<?php

require_once __DIR__ . "/env.php";

try {
    /*
     * PRODUCCIÓN: Railway entrega DATABASE_URL.
     * DESARROLLO LOCAL: se mantienen las variables DB_* del archivo .env.
     */
    $databaseUrl = $_ENV["DATABASE_URL"]
        ?? getenv("DATABASE_URL")
        ?: null;

    if ($databaseUrl) {
        $datos = parse_url($databaseUrl);

        if ($datos === false) {
            throw new RuntimeException(
                "La variable DATABASE_URL no tiene un formato válido."
            );
        }

        $host = $datos["host"] ?? "";
        $port = $datos["port"] ?? 5432;
        $dbname = isset($datos["path"])
            ? ltrim($datos["path"], "/")
            : "";

        $user = isset($datos["user"])
            ? urldecode($datos["user"])
            : "";

        $password = isset($datos["pass"])
            ? urldecode($datos["pass"])
            : "";
    } else {
        $host = $_ENV["DB_HOST"]
            ?? getenv("DB_HOST")
            ?: "";

        $port = $_ENV["DB_PORT"]
            ?? getenv("DB_PORT")
            ?: 5432;

        $dbname = $_ENV["DB_NAME"]
            ?? getenv("DB_NAME")
            ?: "";

        $user = $_ENV["DB_USER"]
            ?? getenv("DB_USER")
            ?: "";

        $password = $_ENV["DB_PASS"]
            ?? getenv("DB_PASS")
            ?: "";
    }

    if (
        $host === "" ||
        $dbname === "" ||
        $user === ""
    ) {
        throw new RuntimeException(
            "Faltan datos para conectar con PostgreSQL."
        );
    }

    $dsn = sprintf(
        "pgsql:host=%s;port=%s;dbname=%s",
        $host,
        $port,
        $dbname
    );

    $conn = new PDO(
        $dsn,
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

} catch (Throwable $e) {
    error_log("Error de conexión a PostgreSQL: " . $e->getMessage());

    if (($_ENV["APP_ENV"] ?? getenv("APP_ENV")) === "production") {
        die("No se pudo conectar con la base de datos.");
    }

    die("Error de conexión: " . $e->getMessage());
}