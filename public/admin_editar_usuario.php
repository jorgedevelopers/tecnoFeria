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
$mensaje = null;

/* Buscar usuario */
$sql = "SELECT id, nombre, email, rol
        FROM users
        WHERE id = :id
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bindParam(":id", $usuario_id);
$stmt->execute();

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    include("../templates/header.php");
    echo "<div class='container py-5'><div class='alert alert-danger'>Usuario no encontrado.</div></div>";
    include("../templates/footer.php");
    exit;
}

/* Actualizar usuario */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["nombre"]);
    $email = trim($_POST["email"]);
    $rol = $_POST["rol"];

    if (!in_array($rol, ["admin", "feriante"])) {
        $rol = "feriante";
    }

    try {

        $sql = "UPDATE users
                SET nombre = :nombre,
                    email = :email,
                    rol = :rol
                WHERE id = :id";

        $stmt = $conn->prepare($sql);

        $stmt->bindParam(":nombre", $nombre);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":rol", $rol);
        $stmt->bindParam(":id", $usuario_id);

        $stmt->execute();

        $mensaje = "✅ Usuario actualizado correctamente";

        $usuario["nombre"] = $nombre;
        $usuario["email"] = $email;
        $usuario["rol"] = $rol;

        if ($_SESSION["user_id"] == $usuario_id) {
            $_SESSION["user_nombre"] = $nombre;
            $_SESSION["user_rol"] = $rol;
        }

    } catch (PDOException $e) {
        $mensaje = "❌ Error al actualizar usuario";
    }
}
?>

<?php include("../templates/header.php"); ?>

<div class="container py-5">

    <a href="/admin.php" class="btn btn-outline-secondary mb-4">
        ← Volver al panel admin
    </a>

    <h2>Editar usuario</h2>

    <?php if ($mensaje) : ?>
        <div class="alert alert-info">
            <?= $mensaje ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">

        <div class="card-body">

            <form method="POST">

                <div class="mb-3">
                    <label class="form-label">Nombre</label>

                    <input 
                        type="text" 
                        name="nombre" 
                        class="form-control"
                        value="<?= htmlspecialchars($usuario["nombre"]) ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>

                    <input 
                        type="email" 
                        name="email" 
                        class="form-control"
                        value="<?= htmlspecialchars($usuario["email"]) ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Rol</label>

                    <select name="rol" class="form-select" required>

                        <option value="feriante" <?= $usuario["rol"] === "feriante" ? "selected" : "" ?>>
                            feriante
                        </option>

                        <option value="admin" <?= $usuario["rol"] === "admin" ? "selected" : "" ?>>
                            admin
                        </option>

                    </select>
                </div>

                <button class="btn btn-primary">
                    Guardar cambios
                </button>

            </form>

        </div>

    </div>

</div>

<?php include("../templates/footer.php"); ?>