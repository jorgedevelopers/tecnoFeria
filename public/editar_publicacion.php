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
$mensaje = null;

/* Buscar publicación y verificar dueño */
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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $contenido = trim($_POST["contenido"]);

    if (!empty($contenido)) {
        try {
            $sql = "UPDATE publicaciones
                    SET contenido = :contenido
                    WHERE id = :id
                    AND user_id = :user_id";

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":contenido", $contenido);
            $stmt->bindParam(":id", $publicacion_id);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();

            header("Location: /feria.php?id=" . $feria_id);
            exit;

        } catch (PDOException $e) {
            $mensaje = "❌ Error: " . $e->getMessage();
        }
    } else {
        $mensaje = "⚠️ La publicación no puede estar vacía";
    }
}
?>

<?php include("../templates/header.php"); ?>

<div class="container py-5">

    <a href="/feria.php?id=<?= $feria_id ?>" class="btn btn-outline-secondary mb-4">
        ← Volver a la feria
    </a>

    <h2>Editar publicación</h2>

    <?php if ($mensaje) : ?>
        <div class="alert alert-info">
            <?= $mensaje ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <div class="mb-3">
            <label>Contenido</label>

            <textarea 
                name="contenido" 
                class="form-control" 
                rows="5"
                required
            ><?= htmlspecialchars($publicacion["contenido"]) ?></textarea>
        </div>

        <button class="btn btn-primary">
            Guardar cambios
        </button>

    </form>

</div>

<?php include("../templates/footer.php"); ?>