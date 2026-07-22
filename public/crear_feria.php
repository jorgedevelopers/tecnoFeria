<?php

require_once("../config/app.php");
require_once("../config/database.php");
require_once("../config/image_upload.php");
require_once("../config/mapbox.php");
require_once("../config/categorias.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Control de sesión
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"])) {
    header("Location: /login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Función para escapar contenido HTML
|--------------------------------------------------------------------------
*/

function h($valor): string
{
    return htmlspecialchars(
        (string) $valor,
        ENT_QUOTES,
        "UTF-8"
    );
}

/*
|--------------------------------------------------------------------------
| Variables iniciales
|--------------------------------------------------------------------------
*/

$mensaje = null;
$tipoMensaje = "info";
$errores = [];

$titulo = "";
$descripcion = "";
$categoriaId = "";
$fecha = "";
$ubicacion = "";
$direccionMapa = "";
$latitud = "";
$longitud = "";
$mapboxPlaceId = "";

$userId = (int) $_SESSION["user_id"];

/*
|--------------------------------------------------------------------------
| Obtener categorías activas
|--------------------------------------------------------------------------
*/

try {
    $categorias = obtenerCategorias($conn);
} catch (Throwable $e) {
    error_log(
        "Error al obtener categorías en crear_feria.php: " .
        $e->getMessage()
    );

    $categorias = [];
    $mensaje = "No se pudieron cargar las categorías.";
    $tipoMensaje = "danger";
}

/*
|--------------------------------------------------------------------------
| Generar token CSRF
|--------------------------------------------------------------------------
*/

if (empty($_SESSION["csrf_crear_feria"])) {
    $_SESSION["csrf_crear_feria"] = bin2hex(
        random_bytes(32)
    );
}

/*
|--------------------------------------------------------------------------
| Procesar formulario
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $titulo = trim($_POST["titulo"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $categoriaId = trim($_POST["categoria_id"] ?? "");
    $fecha = trim($_POST["fecha"] ?? "");
    $ubicacion = trim($_POST["ubicacion"] ?? "");
    $direccionMapa = trim($_POST["direccion_mapa"] ?? "");
    $latitud = trim($_POST["latitud"] ?? "");
    $longitud = trim($_POST["longitud"] ?? "");
    $mapboxPlaceId = trim($_POST["mapbox_place_id"] ?? "");

    /*
    |--------------------------------------------------------------------------
    | Validación CSRF
    |--------------------------------------------------------------------------
    */

    $csrfRecibido = $_POST["csrf_token"] ?? "";

    if (
        !hash_equals(
            $_SESSION["csrf_crear_feria"],
            $csrfRecibido
        )
    ) {
        $errores[] =
            "La sesión del formulario venció. Recargá la página.";
    }

    /*
    |--------------------------------------------------------------------------
    | Validación de datos generales
    |--------------------------------------------------------------------------
    */

    if ($titulo === "") {
        $errores[] = "El título es obligatorio.";
    } elseif (mb_strlen($titulo) > 150) {
        $errores[] =
            "El título no puede superar los 150 caracteres.";
    }

    if ($descripcion === "") {
        $errores[] = "La descripción es obligatoria.";
    }

    if ($categoriaId === "") {
        $errores[] = "La categoría es obligatoria.";
    } elseif (
        filter_var(
            $categoriaId,
            FILTER_VALIDATE_INT
        ) === false ||
        (int) $categoriaId <= 0
    ) {
        $errores[] = "La categoría seleccionada no es válida.";
    }

    if ($fecha === "") {

        $errores[] = "La fecha es obligatoria.";

    } else {

        $fechaObjeto = DateTime::createFromFormat(
            "Y-m-d",
            $fecha
        );

        $fechaValida =
            $fechaObjeto !== false &&
            $fechaObjeto->format("Y-m-d") === $fecha;

        if (!$fechaValida) {
            $errores[] = "La fecha ingresada no es válida.";
        }
    }

    if ($ubicacion === "") {
        $errores[] =
            "El nombre del lugar es obligatorio.";
    } elseif (mb_strlen($ubicacion) > 150) {
        $errores[] =
            "El nombre del lugar no puede superar los 150 caracteres.";
    }

    /*
    |--------------------------------------------------------------------------
    | Validación de Mapbox
    |--------------------------------------------------------------------------
    */

    if ($direccionMapa === "") {
        $errores[] =
            "Debés seleccionar una dirección en el mapa.";
    } elseif (mb_strlen($direccionMapa) > 255) {
        $errores[] =
            "La dirección seleccionada es demasiado extensa.";
    }

    if (
        $latitud === "" ||
        !is_numeric($latitud) ||
        (float) $latitud < -90 ||
        (float) $latitud > 90
    ) {
        $errores[] =
            "La latitud seleccionada no es válida.";
    }

    if (
        $longitud === "" ||
        !is_numeric($longitud) ||
        (float) $longitud < -180 ||
        (float) $longitud > 180
    ) {
        $errores[] =
            "La longitud seleccionada no es válida.";
    }

    /*
    |--------------------------------------------------------------------------
    | Verificar que la categoría exista y esté activa
    |--------------------------------------------------------------------------
    */

    if (
        $categoriaId !== "" &&
        filter_var(
            $categoriaId,
            FILTER_VALIDATE_INT
        ) !== false &&
        (int) $categoriaId > 0
    ) {
        try {

            $stmtCategoria = $conn->prepare("
                SELECT id
                FROM categorias
                WHERE id = :id
                  AND activo = TRUE
                LIMIT 1
            ");

            $stmtCategoria->bindValue(
                ":id",
                (int) $categoriaId,
                PDO::PARAM_INT
            );

            $stmtCategoria->execute();

            if (!$stmtCategoria->fetch(PDO::FETCH_ASSOC)) {
                $errores[] =
                    "La categoría seleccionada no existe o está desactivada.";
            }

        } catch (Throwable $e) {

            error_log(
                "Error al validar categoría: " .
                $e->getMessage()
            );

            $errores[] =
                "No se pudo validar la categoría seleccionada.";
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Crear la feria
    |--------------------------------------------------------------------------
    */

    if (empty($errores)) {

        try {

            $flyer = null;

            /*
            |--------------------------------------------------------------------------
            | Procesar flyer
            |--------------------------------------------------------------------------
            */

            if (
                isset($_FILES["flyer"]) &&
                ($_FILES["flyer"]["error"] ?? UPLOAD_ERR_NO_FILE)
                    !== UPLOAD_ERR_NO_FILE
            ) {
                $flyer = subirImagen(
                    $_FILES["flyer"],
                    "tecnoferia/flyers"
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Insertar feria
            |--------------------------------------------------------------------------
            */

            $sql = "
                INSERT INTO ferias
                (
                    titulo,
                    descripcion,
                    categoria_id,
                    fecha,
                    ubicacion,
                    direccion_mapa,
                    latitud,
                    longitud,
                    mapbox_place_id,
                    flyer,
                    organizador_id
                )
                VALUES
                (
                    :titulo,
                    :descripcion,
                    :categoria_id,
                    :fecha,
                    :ubicacion,
                    :direccion_mapa,
                    :latitud,
                    :longitud,
                    :mapbox_place_id,
                    :flyer,
                    :organizador_id
                )
            ";

            $stmt = $conn->prepare($sql);

            $stmt->bindValue(
                ":titulo",
                $titulo,
                PDO::PARAM_STR
            );

            $stmt->bindValue(
                ":descripcion",
                $descripcion,
                PDO::PARAM_STR
            );

            $stmt->bindValue(
                ":categoria_id",
                (int) $categoriaId,
                PDO::PARAM_INT
            );

            $stmt->bindValue(
                ":fecha",
                $fecha,
                PDO::PARAM_STR
            );

            $stmt->bindValue(
                ":ubicacion",
                $ubicacion,
                PDO::PARAM_STR
            );

            $stmt->bindValue(
                ":direccion_mapa",
                $direccionMapa,
                PDO::PARAM_STR
            );

            $stmt->bindValue(
                ":latitud",
                $latitud,
                PDO::PARAM_STR
            );

            $stmt->bindValue(
                ":longitud",
                $longitud,
                PDO::PARAM_STR
            );

            if ($mapboxPlaceId !== "") {

                $stmt->bindValue(
                    ":mapbox_place_id",
                    $mapboxPlaceId,
                    PDO::PARAM_STR
                );

            } else {

                $stmt->bindValue(
                    ":mapbox_place_id",
                    null,
                    PDO::PARAM_NULL
                );
            }

            if ($flyer !== null && $flyer !== "") {

                $stmt->bindValue(
                    ":flyer",
                    $flyer,
                    PDO::PARAM_STR
                );

            } else {

                $stmt->bindValue(
                    ":flyer",
                    null,
                    PDO::PARAM_NULL
                );
            }

            $stmt->bindValue(
                ":organizador_id",
                $userId,
                PDO::PARAM_INT
            );

            $stmt->execute();

            /*
            |--------------------------------------------------------------------------
            | Renovar token y redirigir
            |--------------------------------------------------------------------------
            */

            $_SESSION["csrf_crear_feria"] =
                bin2hex(random_bytes(32));

            $_SESSION["feria_success"] =
                "La feria fue creada correctamente.";

            header("Location: /ferias.php");
            exit;

        } catch (Throwable $e) {

            error_log(
                "Error al crear feria: " .
                $e->getMessage()
            );

            $mensaje =
                "No se pudo crear la feria. Intentá nuevamente.";

            $tipoMensaje = "danger";
        }

    } else {

        $mensaje = implode(" ", $errores);
        $tipoMensaje = "danger";
    }
}

include("../templates/header.php");
?>

<link
    href="https://api.mapbox.com/mapbox-gl-js/v3.25.0/mapbox-gl.css"
    rel="stylesheet"
>

<link
    href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.1.0/mapbox-gl-geocoder.css"
    rel="stylesheet"
>

<style>
    #mapa-feria {
        width: 100%;
        height: 420px;
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
    }

    .mapboxgl-ctrl-geocoder {
        width: 100%;
        max-width: none;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        box-shadow: none;
    }

    .mapboxgl-ctrl-geocoder--input {
        height: 44px;
    }
</style>

<main class="container py-4">

    <?php

    $breadcrumb = [
        "Inicio" => "/index.php",
        "Galería de Ferias" => "/ferias.php",
        "Crear feria" => null
    ];

    require("../templates/breadcrumb.php");

    ?>

    <div
        class="d-flex flex-column flex-md-row
               justify-content-between align-items-md-center
               gap-3 mb-4"
    >
        <h1 class="h2 mb-0">
            Crear feria
        </h1>

        <a
            href="/ferias.php"
            class="btn btn-outline-secondary"
        >
            Volver a la galería
        </a>
    </div>

    <?php if ($mensaje !== null) : ?>

        <div
            class="alert alert-<?= h($tipoMensaje) ?>"
            role="alert"
        >
            <?= h($mensaje) ?>
        </div>

    <?php endif; ?>

    <?php if (empty($categorias)) : ?>

        <div class="alert alert-warning">
            No hay categorías activas disponibles.
            Un administrador debe crear o activar al menos una categoría.
        </div>

    <?php endif; ?>

    <?php if (MAPBOX_PUBLIC_TOKEN === "") : ?>

        <div class="alert alert-warning">
            El token público de Mapbox no está configurado.
        </div>

    <?php endif; ?>

    <form
        method="POST"
        enctype="multipart/form-data"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= h($_SESSION["csrf_crear_feria"]) ?>"
        >

        <div class="card shadow-sm border-0 mb-4">

            <div class="card-body p-4">

                <h2 class="h5 border-bottom pb-2 mb-4">
                    Información de la feria
                </h2>

                <div class="mb-3">

                    <label
                        for="titulo"
                        class="form-label"
                    >
                        Título *
                    </label>

                    <input
                        type="text"
                        id="titulo"
                        name="titulo"
                        class="form-control"
                        maxlength="150"
                        value="<?= h($titulo) ?>"
                        required
                    >

                </div>

                <div class="mb-3">

                    <label
                        for="descripcion"
                        class="form-label"
                    >
                        Descripción *
                    </label>

                    <textarea
                        id="descripcion"
                        name="descripcion"
                        class="form-control"
                        rows="5"
                        required
                    ><?= h($descripcion) ?></textarea>

                </div>

                <div class="row">

                    <div class="col-md-6 mb-3">

                        <label
                            for="categoria_id"
                            class="form-label"
                        >
                            Categoría *
                        </label>

                        <select
                            id="categoria_id"
                            name="categoria_id"
                            class="form-select"
                            required
                            <?= empty($categorias) ? "disabled" : "" ?>
                        >

                            <option value="">
                                Seleccionar categoría
                            </option>

                            <?php foreach ($categorias as $categoria) : ?>

                                <option
                                    value="<?= (int) $categoria["id"] ?>"
                                    <?= (string) $categoriaId ===
                                        (string) $categoria["id"]
                                            ? "selected"
                                            : "" ?>
                                >
                                    <?= h($categoria["nombre"]) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="col-md-6 mb-3">

                        <label
                            for="fecha"
                            class="form-label"
                        >
                            Fecha *
                        </label>

                        <input
                            type="date"
                            id="fecha"
                            name="fecha"
                            class="form-control"
                            value="<?= h($fecha) ?>"
                            required
                        >

                    </div>

                </div>

            </div>

        </div>

        <div class="card shadow-sm border-0 mb-4">

            <div class="card-body p-4">

                <h2 class="h5 border-bottom pb-2 mb-4">
                    Lugar de realización
                </h2>

                <div class="mb-3">

                    <label
                        for="ubicacion"
                        class="form-label"
                    >
                        Nombre del lugar *
                    </label>

                    <input
                        type="text"
                        id="ubicacion"
                        name="ubicacion"
                        class="form-control"
                        maxlength="150"
                        placeholder="Ejemplo: Predio Ferial Catamarca"
                        value="<?= h($ubicacion) ?>"
                        required
                    >

                    <div class="form-text">
                        Escribí el nombre de la institución,
                        establecimiento, salón o predio.
                    </div>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Buscar dirección o lugar en el mapa
                    </label>

                    <div id="geocoder"></div>

                    <div class="form-text">
                        Buscá una dirección y seleccioná un resultado.
                        También podés hacer clic en el mapa o mover
                        el marcador.
                    </div>

                </div>

                <div
                    id="mapa-feria"
                    class="mb-3"
                ></div>

                <div class="mb-3">

                    <label
                        for="direccion_mapa"
                        class="form-label"
                    >
                        Dirección seleccionada *
                    </label>

                    <input
                        type="text"
                        id="direccion_mapa"
                        name="direccion_mapa"
                        class="form-control"
                        maxlength="255"
                        value="<?= h($direccionMapa) ?>"
                        readonly
                        required
                    >

                </div>

                <div class="row">

                    <div class="col-md-6 mb-3">

                        <label
                            for="latitud"
                            class="form-label"
                        >
                            Latitud
                        </label>

                        <input
                            type="text"
                            id="latitud"
                            name="latitud"
                            class="form-control"
                            value="<?= h($latitud) ?>"
                            readonly
                            required
                        >

                    </div>

                    <div class="col-md-6 mb-3">

                        <label
                            for="longitud"
                            class="form-label"
                        >
                            Longitud
                        </label>

                        <input
                            type="text"
                            id="longitud"
                            name="longitud"
                            class="form-control"
                            value="<?= h($longitud) ?>"
                            readonly
                            required
                        >

                    </div>

                </div>

                <input
                    type="hidden"
                    id="mapbox_place_id"
                    name="mapbox_place_id"
                    value="<?= h($mapboxPlaceId) ?>"
                >

            </div>

        </div>

        <div class="card shadow-sm border-0 mb-4">

            <div class="card-body p-4">

                <h2 class="h5 border-bottom pb-2 mb-4">
                    Flyer
                </h2>

                <div class="mb-3">

                    <label
                        for="flyer"
                        class="form-label"
                    >
                        Imagen de la feria
                    </label>

                    <input
                        type="file"
                        id="flyer"
                        name="flyer"
                        class="form-control"
                        accept="image/jpeg,image/png,image/webp"
                    >

                    <div class="form-text">
                        Formatos permitidos: JPG, PNG y WEBP.
                        Tamaño máximo: 2 MB.
                    </div>

                </div>

            </div>

        </div>

        <div class="d-flex justify-content-end gap-2">

            <a
                href="/ferias.php"
                class="btn btn-outline-secondary"
            >
                Cancelar
            </a>

            <button
                type="submit"
                class="btn btn-primary"
                <?= empty($categorias) ? "disabled" : "" ?>
            >
                Crear feria
            </button>

        </div>

    </form>

</main>

<script
    src="https://api.mapbox.com/mapbox-gl-js/v3.25.0/mapbox-gl.js"
></script>

<script
    src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.1.0/mapbox-gl-geocoder.min.js"
></script>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const token = <?= json_encode(
        MAPBOX_PUBLIC_TOKEN,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    ) ?>;

    if (!token) {
        return;
    }

    mapboxgl.accessToken = token;

    const latitudInput =
        document.getElementById("latitud");

    const longitudInput =
        document.getElementById("longitud");

    const direccionInput =
        document.getElementById("direccion_mapa");

    const placeIdInput =
        document.getElementById("mapbox_place_id");

    const latitudInicial =
        parseFloat(latitudInput.value);

    const longitudInicial =
        parseFloat(longitudInput.value);

    const tieneCoordenadas =
        Number.isFinite(latitudInicial) &&
        Number.isFinite(longitudInicial);

    const centroInicial = tieneCoordenadas
        ? [longitudInicial, latitudInicial]
        : [-64.1811, -31.4135];

    const zoomInicial = tieneCoordenadas
        ? 15
        : 4;

    const map = new mapboxgl.Map({
        container: "mapa-feria",
        style: "mapbox://styles/mapbox/streets-v12",
        center: centroInicial,
        zoom: zoomInicial
    });

    map.addControl(
        new mapboxgl.NavigationControl(),
        "top-right"
    );

    const marker = new mapboxgl.Marker({
        draggable: true
    });

    if (tieneCoordenadas) {
        marker
            .setLngLat(centroInicial)
            .addTo(map);
    }

    const geocoder = new MapboxGeocoder({
        accessToken: mapboxgl.accessToken,
        mapboxgl: mapboxgl,
        marker: false,
        language: "es",
        countries: "ar",
        placeholder:
            "Buscar dirección, institución o lugar"
    });

    document
        .getElementById("geocoder")
        .appendChild(
            geocoder.onAdd(map)
        );

    function guardarCoordenadas(lng, lat) {

        longitudInput.value =
            Number(lng).toFixed(7);

        latitudInput.value =
            Number(lat).toFixed(7);
    }

    async function buscarDireccion(lng, lat) {

        try {

            const url =
                "https://api.mapbox.com/geocoding/v5/" +
                "mapbox.places/" +
                encodeURIComponent(lng + "," + lat) +
                ".json?access_token=" +
                encodeURIComponent(token) +
                "&language=es&limit=1";

            const respuesta = await fetch(url);

            if (!respuesta.ok) {
                throw new Error(
                    "No se pudo consultar la dirección."
                );
            }

            const datos = await respuesta.json();

            if (
                Array.isArray(datos.features) &&
                datos.features.length > 0
            ) {

                const resultado =
                    datos.features[0];

                direccionInput.value =
                    resultado.place_name || "";

                placeIdInput.value =
                    resultado.id || "";
            }

        } catch (error) {

            console.error(
                "Error al buscar dirección:",
                error
            );
        }
    }

    function establecerUbicacion(
        lng,
        lat,
        direccion = "",
        placeId = ""
    ) {

        marker
            .setLngLat([lng, lat])
            .addTo(map);

        guardarCoordenadas(lng, lat);

        if (direccion !== "") {
            direccionInput.value = direccion;
        }

        if (placeId !== "") {
            placeIdInput.value = placeId;
        }

        map.flyTo({
            center: [lng, lat],
            zoom: 16
        });
    }

    geocoder.on("result", function (evento) {

        const resultado = evento.result;

        if (
            !resultado ||
            !resultado.geometry ||
            !Array.isArray(
                resultado.geometry.coordinates
            )
        ) {
            return;
        }

        const [lng, lat] =
            resultado.geometry.coordinates;

        establecerUbicacion(
            lng,
            lat,
            resultado.place_name ||
                resultado.text ||
                "",
            resultado.id || ""
        );
    });

    map.on("click", async function (evento) {

        const lng = evento.lngLat.lng;
        const lat = evento.lngLat.lat;

        establecerUbicacion(lng, lat);

        direccionInput.value = "";
        placeIdInput.value = "";

        await buscarDireccion(lng, lat);
    });

    marker.on("dragend", async function () {

        const posicion = marker.getLngLat();

        guardarCoordenadas(
            posicion.lng,
            posicion.lat
        );

        direccionInput.value = "";
        placeIdInput.value = "";

        await buscarDireccion(
            posicion.lng,
            posicion.lat
        );
    });

});
</script>

<?php include("../templates/footer.php"); ?>