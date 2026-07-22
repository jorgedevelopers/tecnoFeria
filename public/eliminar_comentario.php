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

$comentario_id = $_GET["id"];
$feria_id = $_GET["feria_id"];
$user_id = $_SESSION["user_id"];

$sql = "SELECT * FROM comentarios
        WHERE id = :id
        AND user_id = :user_id
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bindParam(":id", $comentario_id);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();

$comentario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$comentario) {
    header("Location: /feria.php?id=" . $feria_id);
    exit;
}

$sql = "DELETE FROM comentarios
        WHERE id = :id
        AND user_id = :user_id";

$stmt = $conn->prepare($sql);
$stmt->bindParam(":id", $comentario_id);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();

header("Location: /feria.php?id=" . $feria_id);
exit;