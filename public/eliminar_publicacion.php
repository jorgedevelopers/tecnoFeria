<?php
require_once("../config/database.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    header("Location: /login.php");
    exit;
}

if (!isset($_GET["id"]) || !isset($_GET["feria_id"])) {
    header("Location: /ferias.php");
    exit;
}

$publicacion_id = $_GET["id"];
$feria_id = $_GET["feria_id"];
$user_id = $_SESSION["user_id"];

/* Verificar que la publicación sea del usuario */
$sql = "SELECT * FROM publicaciones
        WHERE id = :id
        AND user_id = :user_id
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bindParam(":id", $publicacion_id);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();

$publicacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$publicacion) {
    header("Location: /feria.php?id=" . $feria_id);
    exit;
}

try {
    $sql = "DELETE FROM publicaciones
            WHERE id = :id
            AND user_id = :user_id";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":id", $publicacion_id);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();

    header("Location: /feria.php?id=" . $feria_id);
    exit;

} catch (PDOException $e) {
    die("Error al eliminar publicación: " . $e->getMessage());
}