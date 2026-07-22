<?php
require_once("../config/app.php");

require_once("../config/database.php");


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function tiempoTranscurrido($fecha)
{
    $timestamp = strtotime($fecha);
    $diferencia = time() - $timestamp;

    if ($diferencia < 60) {
        return "Hace unos segundos";
    }

    if ($diferencia < 3600) {

        $minutos = floor($diferencia / 60);

        return $minutos == 1
            ? "Hace 1 minuto"
            : "Hace $minutos minutos";
    }

    if ($diferencia < 86400) {

        $horas = floor($diferencia / 3600);

        return $horas == 1
            ? "Hace 1 hora"
            : "Hace $horas horas";
    }

    $dias = floor($diferencia / 86400);

    return $dias == 1
        ? "Hace 1 día"
        : "Hace $dias días";
}

if (!isset($_GET["id"])) {
    header("Location: /ferias.php");
    exit;
}

$feria_id = $_GET["id"];
$mensaje = null;
$esFavorito = false;

$columnaUsuario = "user_id";

$check = $conn->prepare("
    SELECT column_name 
    FROM information_schema.columns 
    WHERE table_name = 'participaciones'
");
$check->execute();
$columnas = $check->fetchAll(PDO::FETCH_COLUMN);

if (in_array("usuario_id", $columnas)) {
    $columnaUsuario = "usuario_id";
}

/* Favoritos */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["agregar_favorito"]) && isset($_SESSION["user_id"])) {

    try {

        $sql = "INSERT INTO favoritos (user_id, feria_id)
                VALUES (:user_id, :feria_id)";

        $stmt = $conn->prepare($sql);

        $stmt->bindParam(":user_id", $_SESSION["user_id"]);
        $stmt->bindParam(":feria_id", $feria_id);

        $stmt->execute();

        $mensaje = "❤️ Feria agregada a favoritos";

    } catch (PDOException $e) {

        $mensaje = "ℹ️ Esta feria ya está en favoritos";

    }
}

/* Quitar favorito */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["quitar_favorito"]) && isset($_SESSION["user_id"])) {

    $sql = "DELETE FROM favoritos
            WHERE user_id = :user_id
            AND feria_id = :feria_id";

    $stmt = $conn->prepare($sql);

    $stmt->bindParam(":user_id", $_SESSION["user_id"]);
    $stmt->bindParam(":feria_id", $feria_id);

    $stmt->execute();

    $mensaje = "💔 Feria eliminada de favoritos";
}

/* Participar */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["participar"]) && isset($_SESSION["user_id"])) {

    try {

        $sql = "INSERT INTO participaciones ($columnaUsuario, feria_id)
                VALUES (:user_id, :feria_id)";

        $stmt = $conn->prepare($sql);

        $stmt->bindParam(":user_id", $_SESSION["user_id"]);
        $stmt->bindParam(":feria_id", $feria_id);

        $stmt->execute();

        $mensaje = "✅ Te sumaste a esta feria";

    } catch (PDOException $e) {

        $mensaje = "ℹ️ Ya estás participando en esta feria";

    }
}

/* Crear publicación */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["publicar"]) && isset($_SESSION["user_id"])) {

    $contenido = trim($_POST["contenido"]);

    if (!empty($contenido)) {

        try {

            $sql = "INSERT INTO publicaciones (user_id, feria_id, contenido)
                    VALUES (:user_id, :feria_id, :contenido)";

            $stmt = $conn->prepare($sql);

            $stmt->bindParam(":user_id", $_SESSION["user_id"]);
            $stmt->bindParam(":feria_id", $feria_id);
            $stmt->bindParam(":contenido", $contenido);

            $stmt->execute();

            $mensaje = "✅ Publicación creada";

        } catch (PDOException $e) {

            $mensaje = "❌ Error al publicar: " . $e->getMessage();

        }
    }
}

/* Comentarios */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["comentar"]) && isset($_SESSION["user_id"])) {

    $contenidoComentario = trim($_POST["comentario"]);
    $publicacion_id = $_POST["publicacion_id"];

    if (!empty($contenidoComentario)) {

        try {

            $sql = "INSERT INTO comentarios (publicacion_id, user_id, contenido)
                    VALUES (:publicacion_id, :user_id, :contenido)";

            $stmt = $conn->prepare($sql);

            $stmt->bindParam(":publicacion_id", $publicacion_id);
            $stmt->bindParam(":user_id", $_SESSION["user_id"]);
            $stmt->bindParam(":contenido", $contenidoComentario);

            $stmt->execute();

            $mensaje = "✅ Comentario agregado";

        } catch (PDOException $e) {

            $mensaje = "❌ Error al comentar";

        }
    }
}

/* Likes */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["dar_like"]) && isset($_SESSION["user_id"])) {

    $publicacion_id = $_POST["publicacion_id"];

    try {

        $sql = "INSERT INTO likes_publicaciones (publicacion_id, user_id)
                VALUES (:publicacion_id, :user_id)";

        $stmt = $conn->prepare($sql);

        $stmt->bindParam(":publicacion_id", $publicacion_id);
        $stmt->bindParam(":user_id", $_SESSION["user_id"]);

        $stmt->execute();

    } catch (PDOException $e) {
    }
}

/* Quitar like */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["quitar_like"]) && isset($_SESSION["user_id"])) {

    $publicacion_id = $_POST["publicacion_id"];

    $sql = "DELETE FROM likes_publicaciones
            WHERE publicacion_id = :publicacion_id
            AND user_id = :user_id";

    $stmt = $conn->prepare($sql);

    $stmt->bindParam(":publicacion_id", $publicacion_id);
    $stmt->bindParam(":user_id", $_SESSION["user_id"]);

    $stmt->execute();
}

/* Feria */
$sql = "
    SELECT
        f.*,
        u.nombre AS organizador,
        c.nombre AS categoria
    FROM ferias f

    LEFT JOIN users u
        ON u.id = f.organizador_id

    LEFT JOIN categorias c
        ON c.id = f.categoria_id

    WHERE f.id = :id

    LIMIT 1
";

$stmt = $conn->prepare($sql);

$stmt->bindParam(":id", $feria_id);

$stmt->execute();

$feria = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$feria) {
    include("../templates/header.php");
    ?>

    <div class="container py-4">

        <?php
        $breadcrumb = [
            "Inicio" => "/index.php",
            "Galería de Ferias" => "/ferias.php",
            "Feria no encontrada" => null
        ];

        require_once("../templates/breadcrumb.php");
        ?>

        <div class="alert alert-danger">
            Feria no encontrada.
        </div>

    </div>

    <?php
    include("../templates/footer.php");
    exit;
}

/* Favorito */
if (isset($_SESSION["user_id"])) {

    $sql = "SELECT COUNT(*) FROM favoritos
            WHERE user_id = :user_id
            AND feria_id = :feria_id";

    $stmt = $conn->prepare($sql);

    $stmt->bindParam(":user_id", $_SESSION["user_id"]);
    $stmt->bindParam(":feria_id", $feria_id);

    $stmt->execute();

    $esFavorito = $stmt->fetchColumn() > 0;
}

/* Participantes */
$sql = "SELECT COUNT(*) FROM participaciones
        WHERE feria_id = :feria_id";

$stmt = $conn->prepare($sql);

$stmt->bindParam(":feria_id", $feria_id);

$stmt->execute();

$totalParticipantes = $stmt->fetchColumn();

/* Lista participantes */
$sql = "SELECT users.nombre
        FROM participaciones
        JOIN users
            ON participaciones.$columnaUsuario = users.id
        WHERE participaciones.feria_id = :feria_id
        ORDER BY users.nombre ASC";

$stmt = $conn->prepare($sql);

$stmt->bindParam(":feria_id", $feria_id);

$stmt->execute();

$participantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Ya participa */
$yaParticipa = false;

if (isset($_SESSION["user_id"])) {

    $sql = "SELECT COUNT(*) FROM participaciones
            WHERE $columnaUsuario = :user_id
            AND feria_id = :feria_id";

    $stmt = $conn->prepare($sql);

    $stmt->bindParam(":user_id", $_SESSION["user_id"]);
    $stmt->bindParam(":feria_id", $feria_id);

    $stmt->execute();

    $yaParticipa = $stmt->fetchColumn() > 0;
}

/* Publicaciones */
$sql = "SELECT publicaciones.*,
               users.nombre AS autor,
               users.foto_perfil AS autor_foto
        FROM publicaciones
        LEFT JOIN users
            ON publicaciones.user_id = users.id
        WHERE publicaciones.feria_id = :feria_id
        ORDER BY publicaciones.created_at DESC";

$stmt = $conn->prepare($sql);

$stmt->bindParam(":feria_id", $feria_id);

$stmt->execute();

$publicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Likes */
$sql = "SELECT publicacion_id,
               COUNT(*) AS total
        FROM likes_publicaciones
        GROUP BY publicacion_id";

$stmt = $conn->prepare($sql);

$stmt->execute();

$likes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$likesPorPublicacion = [];

foreach ($likes as $like) {
    $likesPorPublicacion[$like["publicacion_id"]] = $like["total"];
}

$likesUsuario = [];

if (isset($_SESSION["user_id"])) {

    $sql = "SELECT publicacion_id
            FROM likes_publicaciones
            WHERE user_id = :user_id";

    $stmt = $conn->prepare($sql);

    $stmt->bindParam(":user_id", $_SESSION["user_id"]);

    $stmt->execute();

    $likesUsuario = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/* Comentarios */
$sql = "SELECT comentarios.*,
               users.nombre AS autor,
               users.foto_perfil AS autor_foto
        FROM comentarios
        LEFT JOIN users
            ON comentarios.user_id = users.id
        INNER JOIN publicaciones
            ON comentarios.publicacion_id = publicaciones.id
        WHERE publicaciones.feria_id = :feria_id
        ORDER BY comentarios.created_at ASC";

$stmt = $conn->prepare($sql);

$stmt->bindParam(":feria_id", $feria_id);

$stmt->execute();

$comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$comentariosPorPublicacion = [];

foreach ($comentarios as $comentario) {
    $comentariosPorPublicacion[$comentario["publicacion_id"]][] = $comentario;
}
?>
<?php include("../templates/header.php"); ?>
<link
    href="https://api.mapbox.com/mapbox-gl-js/v3.3.0/mapbox-gl.css"
    rel="stylesheet"
>

<script src="https://api.mapbox.com/mapbox-gl-js/v3.3.0/mapbox-gl.js"></script>
<div class="container py-4">

    <?php
    $breadcrumb = [
        "Inicio" => "/index.php",
        "Galería de Ferias" => "/ferias.php",
        $feria["titulo"] => null
    ];

    require_once("../templates/breadcrumb.php");
    ?>

    <div class="card shadow-sm mb-4">

        <?php if (!empty($feria["flyer"])) : ?>

            <img 
                src="<?= htmlspecialchars($feria["flyer"]) ?>"
                class="card-img-top"
                alt="Flyer feria"
                style="max-height:400px; object-fit:cover;"
            >

        <?php endif; ?>

        <div class="card-body">

            <h2>
                <?= htmlspecialchars($feria["titulo"]) ?>
            </h2>

            <p>
                <?= nl2br(htmlspecialchars($feria["descripcion"])) ?>
            </p>

            <?php if (!empty($feria["categoria"])) : ?>

                <span class="badge bg-primary mb-3">
                    <?= htmlspecialchars($feria["categoria"]) ?>
                </span>

            <?php endif; ?>

            <p>
                <strong>Fecha:</strong>
                <?= htmlspecialchars($feria["fecha"]) ?>
            </p>

<p>
    <strong>Ubicación:</strong>
    <?= htmlspecialchars($feria["ubicacion"]) ?>
</p>

<?php if (
    !empty($feria["latitud"]) &&
    !empty($feria["longitud"])
) : ?>

    <div class="mt-3 mb-4">

        <div
            id="mapa-feria"
            style="
                width: 100%;
                height: 350px;
                border-radius: 12px;
                overflow: hidden;
            "
        ></div>

        <div class="d-flex flex-wrap gap-2 mt-3">

            <a
                href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($feria["latitud"] . "," . $feria["longitud"]) ?>"
                target="_blank"
                rel="noopener noreferrer"
                class="btn btn-outline-primary"
            >
                📍 Ver en Google Maps
            </a>

            <a
                href="https://www.google.com/maps/dir/?api=1&destination=<?= urlencode($feria["latitud"] . "," . $feria["longitud"]) ?>"
                target="_blank"
                rel="noopener noreferrer"
                class="btn btn-primary"
            >
                🧭 Cómo llegar
            </a>

        </div>

    </div>

<?php else : ?>

    <div class="alert alert-light border mb-4">
        Esta feria todavía no tiene coordenadas registradas.
    </div>

<?php endif; ?>

            <p class="text-muted">
                Organiza:

                <a 
                    href="/perfil.php?id=<?= $feria["organizador_id"] ?>"
                    class="text-decoration-none"
                >
                    <?= htmlspecialchars($feria["organizador"] ?? "Sin organizador") ?>
                </a>
            </p>

            <p>
                <strong>Participantes:</strong>
                <?= $totalParticipantes ?>
            </p>

            <?php if (count($participantes) > 0) : ?>

                <div class="mt-4">

                    <h5>
                        Participantes registrados
                    </h5>

                    <ul class="list-group">

                        <?php foreach ($participantes as $participante) : ?>

                            <li class="list-group-item">
                                <?= htmlspecialchars($participante["nombre"]) ?>
                            </li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>

            <?php if (isset($_SESSION["user_id"])) : ?>

                <div class="d-flex flex-wrap gap-2 mt-4">

                    <?php if ($esFavorito) : ?>

                        <form method="POST">

                            <button name="quitar_favorito" class="btn btn-danger">
                                💔 Quitar favorito
                            </button>

                        </form>

                    <?php else : ?>

                        <form method="POST">

                            <button name="agregar_favorito" class="btn btn-outline-danger">
                                ❤️ Guardar favorito
                            </button>

                        </form>

                    <?php endif; ?>

                    <?php if ($yaParticipa) : ?>

                        <button class="btn btn-success" disabled>
                            ✅ Ya participás
                        </button>

                    <?php else : ?>

                        <form method="POST">

                            <button name="participar" class="btn btn-primary">
                                Participar
                            </button>

                        </form>

                    <?php endif; ?>

                </div>

            <?php else : ?>

                <a href="/login.php" class="btn btn-outline-primary mt-3">
                    Iniciar sesión para participar
                </a>

            <?php endif; ?>

        </div>

    </div>

    <div class="card shadow-sm">

        <div class="card-body">

            <h4 class="mb-4">
                Publicaciones de la feria
            </h4>

            <?php if (isset($_SESSION["user_id"])) : ?>

                <form method="POST" class="mb-4">

                    <div class="mb-3">

                        <label>
                            Crear publicación
                        </label>

                        <textarea 
                            name="contenido"
                            class="form-control"
                            rows="3"
                            placeholder="Escribí una novedad, consulta o comentario..."
                            required
                        ></textarea>

                    </div>

                    <button name="publicar" class="btn btn-primary">
                        Publicar
                    </button>

                </form>

            <?php endif; ?>

            <?php if (count($publicaciones) === 0) : ?>

                <div class="alert alert-secondary">
                    Todavía no hay publicaciones.
                </div>

            <?php endif; ?>

            <?php foreach ($publicaciones as $publicacion) : ?>

                <div class="card shadow-sm mb-4">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center mb-3">

                            <div class="d-flex align-items-center gap-2">

                                <?php if (!empty($publicacion["autor_foto"])) : ?>

                                    <img 
                                        src="<?= htmlspecialchars($publicacion["autor_foto"]) ?>"
                                        alt="Foto perfil"
                                        style="width:45px; height:45px; object-fit:cover; border-radius:50%;"
                                    >

                                <?php else : ?>

                                    <div 
                                        class="bg-secondary text-white d-flex align-items-center justify-content-center"
                                        style="width:45px; height:45px; border-radius:50%;"
                                    >
                                        👤
                                    </div>

                                <?php endif; ?>

                                <strong>

                                    <a 
                                        href="/perfil.php?id=<?= $publicacion["user_id"] ?>"
                                        class="text-decoration-none"
                                    >
                                        <?= htmlspecialchars($publicacion["autor"] ?? "Usuario") ?>
                                    </a>

                                </strong>

                            </div>

                            <small class="text-muted">
                                <?= tiempoTranscurrido($publicacion["created_at"]) ?>
                            </small>

                        </div>

                        <p>
                            <?= nl2br(htmlspecialchars($publicacion["contenido"])) ?>
                        </p>

                        <div class="d-flex align-items-center gap-2 mb-3">

                            <?php
                                $totalLikes = $likesPorPublicacion[$publicacion["id"]] ?? 0;
                                $usuarioDioLike = in_array($publicacion["id"], $likesUsuario);
                            ?>

                            <?php if (isset($_SESSION["user_id"])) : ?>

                                <?php if ($usuarioDioLike) : ?>

                                    <form method="POST">

                                        <input 
                                            type="hidden"
                                            name="publicacion_id"
                                            value="<?= $publicacion["id"] ?>"
                                        >

                                        <button name="quitar_like" class="btn btn-sm btn-primary">
                                            👍 Te gusta
                                        </button>

                                    </form>

                                <?php else : ?>

                                    <form method="POST">

                                        <input 
                                            type="hidden"
                                            name="publicacion_id"
                                            value="<?= $publicacion["id"] ?>"
                                        >

                                        <button name="dar_like" class="btn btn-sm btn-outline-primary">
                                            👍 Me gusta
                                        </button>

                                    </form>

                                <?php endif; ?>

                            <?php endif; ?>

                            <span class="text-muted">
                                <?= $totalLikes ?> me gusta
                            </span>
                            <?php if (isset($_SESSION["user_id"]) && $_SESSION["user_id"] == $publicacion["user_id"]) : ?>
    <a 
        href="/eliminar_publicacion.php?id=<?= $publicacion["id"] ?>&feria_id=<?= $feria_id ?>"
        class="btn btn-sm btn-outline-danger"
        onclick="return confirm('¿Seguro que querés eliminar esta publicación?')"
    >
        🗑️ Eliminar
    </a>
    <a 
    href="/editar_publicacion.php?id=<?= $publicacion["id"] ?>&feria_id=<?= $feria_id ?>"
    class="btn btn-sm btn-outline-warning"
>
    ✏️ Editar
</a>

<?php endif; ?>


                        </div>

                        <hr>

                        <h6>
                            Comentarios
                        </h6>

                        <?php if (!empty($comentariosPorPublicacion[$publicacion["id"]])) : ?>

                            <?php foreach ($comentariosPorPublicacion[$publicacion["id"]] as $comentario) : ?>

                                <div class="bg-light rounded p-3 mb-2">

                                    <div class="d-flex justify-content-between align-items-center mb-2">

                                        <div class="d-flex align-items-center gap-2">

                                            <?php if (!empty($comentario["autor_foto"])) : ?>

                                                <img 
                                                    src="<?= htmlspecialchars($comentario["autor_foto"]) ?>"
                                                    alt="Foto perfil"
                                                    style="width:35px; height:35px; object-fit:cover; border-radius:50%;"
                                                >

                                            <?php else : ?>

                                                <div 
                                                    class="bg-secondary text-white d-flex align-items-center justify-content-center"
                                                    style="width:35px; height:35px; border-radius:50%; font-size:14px;"
                                                >
                                                    👤
                                                </div>

                                            <?php endif; ?>

                                            <strong>

                                                <a 
                                                    href="/perfil.php?id=<?= $comentario["user_id"] ?>"
                                                    class="text-decoration-none"
                                                >
                                                    <?= htmlspecialchars($comentario["autor"] ?? "Usuario") ?>
                                                </a>
                                                <a 
    href="/editar_comentario.php?id=<?= $comentario["id"] ?>&feria_id=<?= $feria_id ?>"
    class="btn btn-sm btn-outline-warning mt-2"
>
    ✏️ Editar
</a>

                                            </strong>

                                        </div>

                                        <small class="text-muted">
                                            <?= tiempoTranscurrido($comentario["created_at"]) ?>
                                        </small>

                                    </div>

                                    <p class="mb-0">
                                        <?= nl2br(htmlspecialchars($comentario["contenido"])) ?>
                                    </p>
                                    <?php if (
    isset($_SESSION["user_id"]) &&
    $_SESSION["user_id"] == $comentario["user_id"]
) : ?>

    <a 
        href="/eliminar_comentario.php?id=<?= $comentario["id"] ?>&feria_id=<?= $feria_id ?>"
        class="btn btn-sm btn-outline-danger mt-2"
        onclick="return confirm('¿Seguro que querés eliminar este comentario?')"
    >
        🗑️ Eliminar
    </a>

<?php endif; ?>

                                </div>

                            <?php endforeach; ?>

                        <?php else : ?>

                            <p class="text-muted">
                                Todavía no hay comentarios.
                            </p>

                        <?php endif; ?>

                        <?php if (isset($_SESSION["user_id"])) : ?>

                            <form method="POST" class="mt-3">

                                <input 
                                    type="hidden"
                                    name="publicacion_id"
                                    value="<?= $publicacion["id"] ?>"
                                >

                                <div class="input-group">

                                    <input 
                                        type="text"
                                        name="comentario"
                                        class="form-control"
                                        placeholder="Escribí un comentario..."
                                        required
                                    >

                                    <button name="comentar" class="btn btn-outline-primary">
                                        Comentar
                                    </button>

                                </div>

                            </form>

                        <?php endif; ?>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </div>

</div>
<?php if (
    !empty($feria["latitud"]) &&
    !empty($feria["longitud"])
) : ?>

<script>
document.addEventListener("DOMContentLoaded", function () {

    mapboxgl.accessToken = <?= json_encode(MAPBOX_PUBLIC_TOKEN) ?>;

    const latitud = <?= json_encode((float) $feria["latitud"]) ?>;
    const longitud = <?= json_encode((float) $feria["longitud"]) ?>;

    const mapa = new mapboxgl.Map({
        container: "mapa-feria",
        style: "mapbox://styles/mapbox/streets-v12",
        center: [longitud, latitud],
        zoom: 14
    });

    mapa.addControl(
        new mapboxgl.NavigationControl(),
        "top-right"
    );

    new mapboxgl.Marker({
        color: "#dc3545"
    })
    .setLngLat([longitud, latitud])
    .setPopup(
        new mapboxgl.Popup({
            offset: 25
        }).setHTML(
            "<strong><?= htmlspecialchars(
                $feria["titulo"],
                ENT_QUOTES,
                "UTF-8"
            ) ?></strong><br>" +
            "<?= htmlspecialchars(
                $feria["ubicacion"],
                ENT_QUOTES,
                "UTF-8"
            ) ?>"
        )
    )
    .addTo(mapa);

});
</script>

<?php endif; ?>

<?php include("../templates/footer.php"); ?>