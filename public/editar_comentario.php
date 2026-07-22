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
$mensaje = null;

$sql = "SELECT *
        FROM comentarios
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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $contenido = trim($_POST["contenido"]);

    if (!empty($contenido)) {
        try {
            $sql = "UPDATE comentarios
                    SET contenido = :contenido
                    WHERE id = :id
                    AND user_id = :user_id";

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":contenido", $contenido);
            $stmt->bindParam(":id", $comentario_id);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();

            header("Location: /feria.php?id=" . $feria_id);
            exit;

        } catch (PDOException $e) {
            $mensaje = "❌ Error: " . $e->getMessage();
        }
    } else {
        $mensaje = "⚠️ El comentario no puede estar vacío";
    }
}
?>

<?php include("../templates/header.php"); ?>

<div class="container py-5">

    <a href="/feria.php?id=<?= $feria_id ?>" class="btn btn-outline-secondary mb-4">
        ← Volver a la feria
    </a>

    <h2>Editar comentario</h2>

    <?php if ($mensaje) : ?>
        <div class="alert alert-info">
            <?= $mensaje ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <div class="mb-3">
            <label>Comentario</label>

            <textarea 
                name="contenido" 
                class="form-control" 
                rows="4"
                required
            ><?= htmlspecialchars($comentario["contenido"]) ?></textarea>
        </div>

        <button class="btn btn-primary">
            Guardar cambios
        </button>

    </form>

</div>

<?php include("../templates/footer.php"); ?>