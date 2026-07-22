<?php

require_once __DIR__ . "/env.php";

if (!defined("MAPBOX_PUBLIC_TOKEN")) {
    $mapboxToken = $_ENV["MAPBOX_PUBLIC_TOKEN"]
        ?? getenv("MAPBOX_PUBLIC_TOKEN")
        ?: "";

    define("MAPBOX_PUBLIC_TOKEN", $mapboxToken);
}
