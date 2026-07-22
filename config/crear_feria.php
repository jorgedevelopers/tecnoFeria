<?php

require_once __DIR__ . "/cloudinary.php";

use Cloudinary\Cloudinary;

function subirImagen($archivo, $carpeta = "tecnoferia")
{
    if (empty($archivo["tmp_name"])) {
        return null;
    }

    $cloudinary = new Cloudinary([
        "cloud" => [
            "cloud_name" => "dcuquvfsm",
            "api_key"    => "753959314656895",
            "api_secret" => "sbqxWW_yxTdvB5zT4LRRpJywX7E"
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