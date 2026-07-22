<?php
require_once("../config/database.php");
require_once("../config/app.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    header("Location: /login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

/* Ferias organizadas */
$sql = "SELECT * FROM ferias
        WHERE organizador_id = :user_id
        ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();

$feriasOrganizadas = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Ferias donde participa */
$sql = "SELECT ferias.*
        FROM participaciones
        INNER JOIN ferias ON participaciones.feria_id = ferias.id
        WHERE participaciones.user_id = :user_id
        ORDER BY ferias.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();

$feriasParticipando = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Favoritos */
$sql = "SELECT ferias.*
        FROM favoritos
        INNER JOIN ferias ON favoritos.feria_id = ferias.id
        WHERE favoritos.user_id = :user_id
        ORDER BY favoritos.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();

$feriasFavoritas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include("../templates/header.php"); ?>

<main class="container py-5">

    <div class="row">

        <?php require_once("../templates/sidebar.php"); ?>

        <section class="col-lg-9">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <?php
$breadcrumb = [
    "Inicio" => "/index.php",
    "Mi panel" => null
];

require_once("../templates/breadcrumb.php");
?>
        <h1>Mi panel</h1>
        <div class="d-flex flex-wrap gap-2 mb-4">

    <a href="/crear_feria.php" class="btn btn-primary">
        ➕ Crear feria
    </a>

    <a href="/perfil.php?id=<?= $_SESSION["user_id"] ?>" class="btn btn-outline-dark">
        👤 Mi perfil
    </a>

    <a href="/editar_perfil.php" class="btn btn-outline-secondary">
        ⚙️ Editar perfil
    </a>

    <a href="/ferias.php" class="btn btn-outline-primary">
        🎪 Ver ferias
    </a>

</div>

        
    </div>

    <!-- FERIAS ORGANIZADAS -->

    <h2 id="mis-ferias" class="mb-4">
    Ferias que organicé
</h2>

    <?php if (count($feriasOrganizadas) === 0) : ?>

        <div class="alert alert-info mb-5">
            Todavía no organizaste ninguna feria.
        </div>

    <?php else : ?>

        <div class="row mb-5">

            <?php foreach ($feriasOrganizadas as $feria) : ?>

                <div class="col-lg-4 col-md-6 mb-4">

                    <div class="card h-100 shadow-sm">

                        <?php if (!empty($feria["flyer"])) : ?>

                            <img
                                src="<?= htmlspecialchars($feria["flyer"]) ?>"
                                class="card-img-top"
                                alt="Flyer"
                                style="height:220px; object-fit:cover;"
                            >

                        <?php endif; ?>

                        <div class="card-body d-flex flex-column">

                            <h4>
                                <?= htmlspecialchars($feria["titulo"]) ?>
                            </h4>

                            <?php if (!empty($feria["categoria"])) : ?>
                                <span class="badge bg-primary mb-2 align-self-start">
                                    <?= htmlspecialchars($feria["categoria"]) ?>
                                </span>
                            <?php endif; ?>

                            <p>
                                <?= htmlspecialchars($feria["descripcion"]) ?>
                            </p>

                            <p>
                                <strong>Fecha:</strong>
                                <?= htmlspecialchars($feria["fecha"]) ?>
                            </p>

                            <div class="d-flex gap-2 mt-auto flex-wrap">

                                <a
                                    href="/feria.php?id=<?= $feria["id"] ?>"
                                    class="btn btn-outline-primary"
                                >
                                    Ver detalle
                                </a>

                                <a
                                    href="/editar_feria.php?id=<?= $feria["id"] ?>"
                                    class="btn btn-warning"
                                >
                                    Editar
                                </a>

                                <a
                                    href="/eliminar_feria.php?id=<?= $feria["id"] ?>"
                                    class="btn btn-danger"
                                    onclick="return confirm('¿Seguro que querés eliminar esta feria?')"
                                >
                                    Eliminar
                                </a>

                            </div>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

    <!-- PARTICIPANDO -->

    <h2 id="participaciones" class="mb-4">
    Ferias donde participo
</h2>

    <?php if (count($feriasParticipando) === 0) : ?>

        <div class="alert alert-info mb-5">
            Todavía no participás en ninguna feria.
        </div>

    <?php else : ?>

        <div class="row mb-5">

            <?php foreach ($feriasParticipando as $feria) : ?>

                <div class="col-lg-4 col-md-6 mb-4">

                    <div class="card h-100 shadow-sm">

                        <?php if (!empty($feria["flyer"])) : ?>

                            <img
                                src="<?= htmlspecialchars($feria["flyer"]) ?>"
                                class="card-img-top"
                                alt="Flyer"
                                style="height:220px; object-fit:cover;"
                            >

                        <?php endif; ?>

                        <div class="card-body d-flex flex-column">

                            <h4>
                                <?= htmlspecialchars($feria["titulo"]) ?>
                            </h4>

                            <?php if (!empty($feria["categoria"])) : ?>
                                <span class="badge bg-primary mb-2 align-self-start">
                                    <?= htmlspecialchars($feria["categoria"]) ?>
                                </span>
                            <?php endif; ?>

                            <p>
                                <?= htmlspecialchars($feria["descripcion"]) ?>
                            </p>

                            <p>
                                <strong>Fecha:</strong>
                                <?= htmlspecialchars($feria["fecha"]) ?>
                            </p>

                            <div class="mt-auto">

                                <a
                                    href="/feria.php?id=<?= $feria["id"] ?>"
                                    class="btn btn-outline-primary"
                                >
                                    Ver detalle
                                </a>

                            </div>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

    <!-- FAVORITOS -->

    <h2 id="favoritos" class="mb-4">
    ❤️ Mis ferias favoritas
</h2>

    <?php if (count($feriasFavoritas) === 0) : ?>

        <div class="alert alert-info">
            Todavía no guardaste ferias favoritas.
        </div>

    <?php else : ?>

        <div class="row">

            <?php foreach ($feriasFavoritas as $feria) : ?>

                <div class="col-lg-4 col-md-6 mb-4">

                    <div class="card h-100 shadow-sm">

                        <?php if (!empty($feria["flyer"])) : ?>

                            <img
                                src="<?= htmlspecialchars($feria["flyer"]) ?>"
                                class="card-img-top"
                                alt="Flyer"
                                style="height:220px; object-fit:cover;"
                            >

                        <?php endif; ?>

                        <div class="card-body d-flex flex-column">

                            <h4>
                                <?= htmlspecialchars($feria["titulo"]) ?>
                            </h4>

                            <?php if (!empty($feria["categoria"])) : ?>
                                <span class="badge bg-primary mb-2 align-self-start">
                                    <?= htmlspecialchars($feria["categoria"]) ?>
                                </span>
                            <?php endif; ?>

                            <p>
                                <?= htmlspecialchars($feria["descripcion"]) ?>
                            </p>

                            <p>
                                <strong>Fecha:</strong>
                                <?= htmlspecialchars($feria["fecha"]) ?>
                            </p>

                            <div class="mt-auto">

                                <a
                                    href="/feria.php?id=<?= $feria["id"] ?>"
                                    class="btn btn-outline-primary"
                                >
                                    Ver detalle
                                </a>

                            </div>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

        </section>

    </div>

</main>

<?php include("../templates/footer.php"); ?>