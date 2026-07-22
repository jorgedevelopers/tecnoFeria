<?php

require_once __DIR__ . "/env.php";

use Cloudinary\Cloudinary;

function subirImagen($archivo, $carpeta = "tecnoferia")
{
    if (empty($archivo["tmp_name"])) {
        return null;
    }

    $tiposPermitidos = [
        "image/jpeg",
        "image/png",
        "image/webp"
    ];

    $tamañoMaximo = 2 * 1024 * 1024; // 2 MB

    if (!in_array($archivo["type"], $tiposPermitidos)) {
        throw new Exception("Solo se permiten imágenes JPG, PNG o WEBP.");
    }

    if ($archivo["size"] > $tamañoMaximo) {
        throw new Exception("La imagen no puede superar los 2 MB.");
    }

    $cloudinary = new Cloudinary([
        "cloud" => [
            "cloud_name" => $_ENV["CLOUDINARY_CLOUD_NAME"],
            "api_key"    => $_ENV["CLOUDINARY_API_KEY"],
            "api_secret" => $_ENV["CLOUDINARY_API_SECRET"]
        ],
        "url" => [
            "secure" => true
        ]
    ]);

    $upload = $cloudinary->uploadApi()->upload(
        $archivo["tmp_name"],
        [
            "folder" => $carpeta
        ]
    );

    return $upload["secure_url"];
}