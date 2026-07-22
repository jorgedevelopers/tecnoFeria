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

/* Estadísticas */
$totalUsuarios = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalFerias = $conn->query("SELECT COUNT(*) FROM ferias")->fetchColumn();
$totalPublicaciones = $conn->query("SELECT COUNT(*) FROM publicaciones")->fetchColumn();
$totalComentarios = $conn->query("SELECT COUNT(*) FROM comentarios")->fetchColumn();

/* Usuarios */
$sql = "SELECT id, nombre, email, rol, created_at
        FROM users
        ORDER BY id ASC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Ferias */
$sql = "SELECT ferias.*, users.nombre AS organizador
        FROM ferias
        LEFT JOIN users ON ferias.organizador_id = users.id
        ORDER BY ferias.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$ferias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include("../templates/header.php"); ?>

<main class="container py-5">

    <div class="row">

        <?php require_once("../templates/sidebar.php"); ?>

        <section class="col-lg-9">

            <?php
            $breadcrumb = [
                "Inicio" => "/index.php",
                "Administración" => null
            ];

            require_once("../templates/breadcrumb.php");
            ?>

            <h1 class="mb-4">
                ⚙️ Panel de Administración
            </h1>

    <?php if (isset($_GET["eliminada"]) && $_GET["eliminada"] == "1") : ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            ✅ La feria fue eliminada correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET["usuario_eliminado"]) && $_GET["usuario_eliminado"] == "1") : ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            ✅ Usuario eliminado correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET["error"]) && $_GET["error"] === "autoeliminar") : ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            ⚠️ No podés eliminar tu propio usuario administrador.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

<div id="resumen" class="row g-4 mb-5">

    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-body text-center">
                <h2><?= $totalUsuarios ?></h2>
                <p class="mb-0">Usuarios</p>
            </div>
        </div>
    </div>

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h2><?= $totalFerias ?></h2>
                    <p class="mb-0">Ferias</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h2><?= $totalPublicaciones ?></h2>
                    <p class="mb-0">Publicaciones</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h2><?= $totalComentarios ?></h2>
                    <p class="mb-0">Comentarios</p>
                </div>
            </div>
        </div>

    </div>

    <div id="usuarios" class="card shadow-sm">
        <div class="card-body">

            <h3 class="mb-4">
                👥 Usuarios registrados
            </h3>

            <div class="table-responsive">
                <table class="table table-hover align-middle">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Fecha alta</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($usuarios as $usuario) : ?>
                            <tr>
                                <td><?= $usuario["id"] ?></td>

                                <td><?= htmlspecialchars($usuario["nombre"]) ?></td>

                                <td><?= htmlspecialchars($usuario["email"]) ?></td>

                                <td>
                                    <?php if ($usuario["rol"] === "admin") : ?>
                                        <span class="badge bg-warning text-dark">
                                            admin
                                        </span>
                                    <?php else : ?>
                                        <span class="badge bg-primary">
                                            <?= htmlspecialchars($usuario["rol"]) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($usuario["created_at"] ?? "-") ?>
                                </td>

                                <td>
                                    <div class="d-flex gap-2">

                                        <a
                                            href="/perfil.php?id=<?= $usuario["id"] ?>"
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            Ver perfil
                                        </a>

                                        <a
                                            href="/admin_editar_usuario.php?id=<?= $usuario["id"] ?>"
                                            class="btn btn-sm btn-outline-warning"
                                        >
                                            Editar
                                        </a>

                                        <?php if ($_SESSION["user_id"] != $usuario["id"]) : ?>
                                            <a
                                                href="/admin_eliminar_usuario.php?id=<?= $usuario["id"] ?>"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('¿Seguro que querés eliminar este usuario? Esta acción no se puede deshacer.')"
                                            >
                                                Eliminar
                                            </a>
                                        <?php endif; ?>

                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>

        </div>
    </div>

    <div id="ferias" class="card shadow-sm mt-5">
        <div class="card-body">

            <h3 class="mb-4">
                🎪 Ferias registradas
            </h3>

            <div class="table-responsive">
                <table class="table table-hover align-middle">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Organizador</th>
                            <th>Categoría</th>
                            <th>Fecha</th>
                            <th>Ubicación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($ferias as $feria) : ?>
                            <tr>
                                <td><?= $feria["id"] ?></td>

                                <td><?= htmlspecialchars($feria["titulo"]) ?></td>

                                <td><?= htmlspecialchars($feria["organizador"] ?? "Sin organizador") ?></td>

                                <td>
                                    <?php if (!empty($feria["categoria"])) : ?>
                                        <span class="badge bg-primary">
                                            <?= htmlspecialchars($feria["categoria"]) ?>
                                        </span>
                                    <?php else : ?>
                                        <span class="text-muted">
                                            Sin categoría
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td><?= htmlspecialchars($feria["fecha"]) ?></td>

                                <td><?= htmlspecialchars($feria["ubicacion"]) ?></td>

                                <td>
                                    <div class="d-flex gap-2">

                                        <a 
                                            href="/feria.php?id=<?= $feria["id"] ?>"
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            Ver
                                        </a>

                                        <a 
                                            href="/editar_feria.php?id=<?= $feria["id"] ?>"
                                            class="btn btn-sm btn-outline-warning"
                                        >
                                            Editar
                                        </a>

                                        <a 
                                            href="/eliminar_feria.php?id=<?= $feria["id"] ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('¿Seguro que querés eliminar esta feria?')"
                                        >
                                            Eliminar
                                        </a>

                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>

        </div>
    </div>

        </section>

    </div>

</main>

<?php include("../templates/footer.php"); ?>