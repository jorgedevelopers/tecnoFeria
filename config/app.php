<?php

require_once __DIR__ . "/env.php";

if (!defined("MAPBOX_PUBLIC_TOKEN")) {
    $mapboxToken = $_ENV["MAPBOX_PUBLIC_TOKEN"]
        ?? getenv("MAPBOX_PUBLIC_TOKEN")
        ?: "";

    define("MAPBOX_PUBLIC_TOKEN", $mapboxToken);
}

$appEnv = $_ENV["APP_ENV"] ?? "development";

if ($appEnv === "production") {
    ini_set("display_errors", 0);
    error_reporting(0);
} else {
    ini_set("display_errors", 1);
    error_reporting(E_ALL);
}