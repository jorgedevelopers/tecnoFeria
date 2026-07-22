<?php

function obtenerCategorias(PDO $conn): array
{
    $stmt = $conn->query("
        SELECT id, nombre
        FROM categorias
        WHERE activo = TRUE
        ORDER BY nombre
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}