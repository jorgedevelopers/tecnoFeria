<?php

require_once __DIR__ . "/../vendor/autoload.php";

$envPath = dirname(__DIR__);

/*
 * En desarrollo local carga .env.
 * En Railway usa las variables configuradas en el servicio.
 */
$dotenv = Dotenv\Dotenv::createImmutable($envPath);
$dotenv->safeLoad();