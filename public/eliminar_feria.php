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

if (!isset($_GET["id"])) {
    header("Location: /dashboard.php");
    exit;
}

$feria_id = $_GET["id"];
$user_id = $_SESSION["user_id"];
$user_rol = $_SESSION["user_rol"] ?? "feriante";
$esAdmin = $user_rol === "admin";

$volverUrl = $esAdmin ? "/admin.php" : "/dashboard.php";

/* Verificar permisos */
if ($esAdmin) {
    $sql = "SELECT * FROM ferias
            WHERE id = :id
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":id", $feria_id);
} else {
    $sql = "SELECT * FROM ferias
            WHERE id = :id
            AND organizador_id = :user_id
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":id", $feria_id);
    $stmt->bindParam(":user_id", $user_id);
}

$stmt->execute();
$feria = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$feria) {
    die("No tenés permisos para eliminar esta feria.");
}

try {

    $sql = "DELETE FROM participaciones
            WHERE feria_id = :feria_id";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":feria_id", $feria_id);
    $stmt->execute();

    $sql = "DELETE FROM favoritos
            WHERE feria_id = :feria_id";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":feria_id", $feria_id);
    $stmt->execute();

    $sql = "DELETE FROM likes_publicaciones
            WHERE publicacion_id IN (
                SELECT id FROM publicaciones WHERE feria_id = :feria_id
            )";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":feria_id", $feria_id);
    $stmt->execute();

    $sql = "DELETE FROM comentarios
            WHERE publicacion_id IN (
                SELECT id FROM publicaciones WHERE feria_id = :feria_id
            )";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":feria_id", $feria_id);
    $stmt->execute();

    $sql = "DELETE FROM publicaciones
            WHERE feria_id = :feria_id";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":feria_id", $feria_id);
    $stmt->execute();

    $sql = "DELETE FROM ferias
            WHERE id = :id";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":id", $feria_id);
    $stmt->execute();

header("Location: " . $volverUrl . "?eliminada=1");
exit;

} catch (PDOException $e) {
    die("Error al eliminar feria.");
}