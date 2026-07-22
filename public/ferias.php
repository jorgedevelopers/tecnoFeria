<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once("../config/database.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$busqueda = $_GET["buscar"] ?? "";
$filtroFecha = $_GET["fecha"] ?? "";
$filtroCategoria = $_GET["categoria"] ?? "";

$condiciones = [];
$parametros = [];

if (!empty($busqueda)) {
    $condiciones[] = "(ferias.titulo ILIKE :busqueda 
                    OR ferias.descripcion ILIKE :busqueda 
                    OR ferias.ubicacion ILIKE :busqueda)";
    $parametros[":busqueda"] = "%" . $busqueda . "%";
}

if (!empty($filtroCategoria)) {
    $condiciones[] = "ferias.categoria = :categoria";
    $parametros[":categoria"] = $filtroCategoria;
}

if ($filtroFecha === "hoy") {
    $condiciones[] = "ferias.fecha = CURRENT_DATE";
}

if ($filtroFecha === "proximas") {
    $condiciones[] = "ferias.fecha >= CURRENT_DATE";
}

if ($filtroFecha === "mes") {
    $condiciones[] = "EXTRACT(MONTH FROM ferias.fecha) = EXTRACT(MONTH FROM CURRENT_DATE)
                      AND EXTRACT(YEAR FROM ferias.fecha) = EXTRACT(YEAR FROM CURRENT_DATE)";
}

$where = "";

if (count($condiciones) > 0) {
    $where = "WHERE " . implode(" AND ", $condiciones);
}

$sql = "SELECT ferias.*, users.nombre AS organizador
        FROM ferias
        LEFT JOIN users ON ferias.organizador_id = users.id
        $where
        ORDER BY ferias.fecha ASC";

$stmt = $conn->prepare($sql);

foreach ($parametros as $clave => $valor) {
    $stmt->bindValue($clave, $valor);
}

$stmt->execute();
$ferias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php
include("../templates/header.php");

$breadcrumb = [
    "Inicio" => "/index.php",
    "Galería de Ferias" => null
];

require_once("../templates/breadcrumb.php");
?>


<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-0">Ferias tecnológicas</h2>
            <p class="text-muted mb-0">
                Descubrí eventos, exposiciones y encuentros tecnológicos
            </p>
        </div>

        <?php if (isset($_SESSION["user_id"])) : ?>
            <a href="/crear_feria.php" class="btn btn-primary">
                Crear feria
            </a>
        <?php endif; ?>
    </div>

    <form method="GET" class="mb-4">

        <div class="row g-2">

            <div class="col-md-4">
                <input 
                    type="text" 
                    name="buscar" 
                    class="form-control"
                    placeholder="Buscar por título, descripción o ubicación..."
                    value="<?= htmlspecialchars($busqueda) ?>"
                >
            </div>

            <div class="col-md-3">
                <select name="categoria" class="form-select">
                    <option value="">Todas las categorías</option>

                    <option value="Inteligencia Artificial" <?= $filtroCategoria === "Inteligencia Artificial" ? "selected" : "" ?>>
                        Inteligencia Artificial
                    </option>

                    <option value="Robótica" <?= $filtroCategoria === "Robótica" ? "selected" : "" ?>>
                        Robótica
                    </option>

                    <option value="Programación" <?= $filtroCategoria === "Programación" ? "selected" : "" ?>>
                        Programación
                    </option>

                    <option value="Hardware" <?= $filtroCategoria === "Hardware" ? "selected" : "" ?>>
                        Hardware
                    </option>

                    <option value="Emprendimiento" <?= $filtroCategoria === "Emprendimiento" ? "selected" : "" ?>>
                        Emprendimiento
                    </option>

                    <option value="Educación tecnológica" <?= $filtroCategoria === "Educación tecnológica" ? "selected" : "" ?>>
                        Educación tecnológica
                    </option>

                    <option value="Ciencia" <?= $filtroCategoria === "Ciencia" ? "selected" : "" ?>>
                        Ciencia
                    </option>

                    <option value="Otro" <?= $filtroCategoria === "Otro" ? "selected" : "" ?>>
                        Otro
                    </option>
                </select>
            </div>

            <div class="col-md-3">
                <select name="fecha" class="form-select">
                    <option value="">Todas las fechas</option>

                    <option value="hoy" <?= $filtroFecha === "hoy" ? "selected" : "" ?>>
                        Hoy
                    </option>

                    <option value="proximas" <?= $filtroFecha === "proximas" ? "selected" : "" ?>>
                        Próximas ferias
                    </option>

                    <option value="mes" <?= $filtroFecha === "mes" ? "selected" : "" ?>>
                        Este mes
                    </option>
                </select>
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-outline-primary w-100">
                    Filtrar
                </button>

                <?php if (!empty($busqueda) || !empty($filtroFecha) || !empty($filtroCategoria)) : ?>
                    <a href="/ferias.php" class="btn btn-outline-secondary">
                        Limpiar
                    </a>
                <?php endif; ?>
            </div>

        </div>

    </form>

    <?php if (count($ferias) === 0) : ?>
        <div class="alert alert-info">
            No se encontraron ferias.
        </div>
    <?php endif; ?>

    <div class="row">
        <?php foreach ($ferias as $feria) : ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100 shadow-sm">

                    <?php if (!empty($feria["flyer"])) : ?>
                        <img 
                            src="<?= htmlspecialchars($feria["flyer"]) ?>" 
                            class="card-img-top" 
                            alt="Flyer feria"
                            style="height: 220px; object-fit: cover;"
                        >
                    <?php else : ?>
                        <div 
                            class="bg-secondary text-white d-flex align-items-center justify-content-center"
                            style="height: 220px;"
                        >
                            Sin flyer
                        </div>
                    <?php endif; ?>

                    <div class="card-body d-flex flex-column">

                        <h5 class="card-title">
                            <?= htmlspecialchars($feria["titulo"]) ?>
                        </h5>

                        <?php if (!empty($feria["categoria"])) : ?>
                            <span class="badge bg-primary mb-2 align-self-start">
                                <?= htmlspecialchars($feria["categoria"]) ?>
                            </span>
                        <?php endif; ?>

                        <p class="card-text">
                            <?= htmlspecialchars($feria["descripcion"]) ?>
                        </p>

                        <p class="mb-1">
                            <strong>Fecha:</strong>
                            <?= htmlspecialchars($feria["fecha"]) ?>
                        </p>

                        <p class="mb-1">
                            <strong>Ubicación:</strong>
                            <?= htmlspecialchars($feria["ubicacion"]) ?>
                        </p>

                        <p class="text-muted">
                            Organiza:
                            <?= htmlspecialchars($feria["organizador"] ?? "Sin organizador") ?>
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

</div>

<?php include("../templates/footer.php"); ?>