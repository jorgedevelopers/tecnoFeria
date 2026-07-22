<?php
require_once("../config/app.php");
require_once("../config/database.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    header("Location: /login.php");
    exit;
}

if (!isset($_SESSION["user_rol"]) || $_SESSION["user_rol"] !== "admin") {
    header("Location: /index.php");
    exit;
}

if (!isset($_GET["id"])) {
    header("Location: /admin.php");
    exit;
}

$usuario_id = $_GET["id"];

/* Evitar que el admin se elimine a sí mismo */
if ($_SESSION["user_id"] == $usuario_id) {
    header("Location: /admin.php?error=autoeliminar");
    exit;
}

try {
    $sql = "DELETE FROM users
            WHERE id = :id";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":id", $usuario_id);
    $stmt->execute();

    header("Location: /admin.php?usuario_eliminado=1");
    exit;

} catch (PDOException $e) {
    header("Location: /admin.php?error=usuario");
    exit;
}