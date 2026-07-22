<?php
ini_set("display_errors", 1);
error_reporting(E_ALL);

require_once("../config/database.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Funciones auxiliares
|--------------------------------------------------------------------------
*/

function escapar($valor): string
{
    return htmlspecialchars(
        (string) $valor,
        ENT_QUOTES,
        "UTF-8"
    );
}

function resumirTexto($texto, int $limite = 180): string
{
    $texto = trim((string) $texto);

    if ($texto === "") {
        return "";
    }

    if (strlen($texto) <= $limite) {
        return $texto;
    }

    return substr($texto, 0, $limite) . "...";
}

function formatearFecha($fecha): string
{
    if (empty($fecha)) {
        return "Sin fecha";
    }

    $timestamp = strtotime((string) $fecha);

    if ($timestamp === false) {
        return (string) $fecha;
    }

    return date("d/m/Y", $timestamp);
}

function crearUrlBusqueda(
    string $criterio,
    int $paginaFerias,
    int $paginaUsuarios,
    int $paginaPublicaciones
): string {
    return "/buscar.php?" . http_build_query([
        "q" => $criterio,
        "ferias_pagina" => $paginaFerias,
        "usuarios_pagina" => $paginaUsuarios,
        "publicaciones_pagina" => $paginaPublicaciones
    ]);
}

/*
|--------------------------------------------------------------------------
| Parámetros de búsqueda
|--------------------------------------------------------------------------
*/

$criterio = trim($_GET["q"] ?? "");

$porPaginaFerias = 6;
$porPaginaUsuarios = 6;
$porPaginaPublicaciones = 6;

$paginaFerias = max(
    1,
    (int) ($_GET["ferias_pagina"] ?? 1)
);

$paginaUsuarios = max(
    1,
    (int) ($_GET["usuarios_pagina"] ?? 1)
);

$paginaPublicaciones = max(
    1,
    (int) ($_GET["publicaciones_pagina"] ?? 1)
);

$ferias = [];
$usuarios = [];
$publicaciones = [];

$totalFerias = 0;
$totalUsuarios = 0;
$totalPublicaciones = 0;

$totalPaginasFerias = 1;
$totalPaginasUsuarios = 1;
$totalPaginasPublicaciones = 1;

/*
|--------------------------------------------------------------------------
| Ejecutar búsqueda
|--------------------------------------------------------------------------
*/

if ($criterio !== "") {

    $termino = "%" . $criterio . "%";

    /*
    |--------------------------------------------------------------------------
    | FERIAS: contar resultados
    |--------------------------------------------------------------------------
    */

    $sql = "SELECT COUNT(*)
            FROM ferias
            WHERE titulo ILIKE :criterio
               OR descripcion ILIKE :criterio
               OR ubicacion ILIKE :criterio
               OR categoria ILIKE :criterio";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(":criterio", $termino);
    $stmt->execute();

    $totalFerias = (int) $stmt->fetchColumn();

    $totalPaginasFerias = max(
        1,
        (int) ceil(
            $totalFerias / $porPaginaFerias
        )
    );

    if ($paginaFerias > $totalPaginasFerias) {
        $paginaFerias = $totalPaginasFerias;
    }

    $offsetFerias =
        ($paginaFerias - 1) * $porPaginaFerias;

    /*
    |--------------------------------------------------------------------------
    | FERIAS: obtener resultados
    |--------------------------------------------------------------------------
    */

    $sql = "SELECT
                ferias.*,
                users.nombre AS organizador
            FROM ferias

            LEFT JOIN users
                ON ferias.organizador_id = users.id

            WHERE ferias.titulo ILIKE :criterio
               OR ferias.descripcion ILIKE :criterio
               OR ferias.ubicacion ILIKE :criterio
               OR ferias.categoria ILIKE :criterio

            ORDER BY
                ferias.fecha ASC,
                ferias.id ASC

            LIMIT :limite
            OFFSET :offset";

    $stmt = $conn->prepare($sql);

    $stmt->bindValue(":criterio", $termino);
    $stmt->bindValue(
        ":limite",
        $porPaginaFerias,
        PDO::PARAM_INT
    );
    $stmt->bindValue(
        ":offset",
        $offsetFerias,
        PDO::PARAM_INT
    );

    $stmt->execute();

    $ferias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /*
    |--------------------------------------------------------------------------
    | USUARIOS: contar resultados
    |--------------------------------------------------------------------------
    */

    $sql = "SELECT COUNT(*)
            FROM users
            WHERE nombre ILIKE :criterio
               OR email ILIKE :criterio
               OR rol ILIKE :criterio";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(":criterio", $termino);
    $stmt->execute();

    $totalUsuarios = (int) $stmt->fetchColumn();

    $totalPaginasUsuarios = max(
        1,
        (int) ceil(
            $totalUsuarios / $porPaginaUsuarios
        )
    );

    if ($paginaUsuarios > $totalPaginasUsuarios) {
        $paginaUsuarios = $totalPaginasUsuarios;
    }

    $offsetUsuarios =
        ($paginaUsuarios - 1) * $porPaginaUsuarios;

    /*
    |--------------------------------------------------------------------------
    | USUARIOS: obtener resultados
    |--------------------------------------------------------------------------
    */

    $sql = "SELECT
                id,
                nombre,
                email,
                rol,
                created_at,
                foto_perfil
            FROM users

            WHERE nombre ILIKE :criterio
               OR email ILIKE :criterio
               OR rol ILIKE :criterio

            ORDER BY
                nombre ASC,
                id ASC

            LIMIT :limite
            OFFSET :offset";

    $stmt = $conn->prepare($sql);

    $stmt->bindValue(":criterio", $termino);
    $stmt->bindValue(
        ":limite",
        $porPaginaUsuarios,
        PDO::PARAM_INT
    );
    $stmt->bindValue(
        ":offset",
        $offsetUsuarios,
        PDO::PARAM_INT
    );

    $stmt->execute();

    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /*
    |--------------------------------------------------------------------------
    | PUBLICACIONES: contar resultados
    |--------------------------------------------------------------------------
    */

    $sql = "SELECT COUNT(*)
            FROM publicaciones

            LEFT JOIN users
                ON publicaciones.user_id = users.id

            LEFT JOIN ferias
                ON publicaciones.feria_id = ferias.id

            WHERE publicaciones.contenido ILIKE :criterio
               OR users.nombre ILIKE :criterio
               OR ferias.titulo ILIKE :criterio";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(":criterio", $termino);
    $stmt->execute();

    $totalPublicaciones =
        (int) $stmt->fetchColumn();

    $totalPaginasPublicaciones = max(
        1,
        (int) ceil(
            $totalPublicaciones /
            $porPaginaPublicaciones
        )
    );

    if (
        $paginaPublicaciones >
        $totalPaginasPublicaciones
    ) {
        $paginaPublicaciones =
            $totalPaginasPublicaciones;
    }

    $offsetPublicaciones =
        ($paginaPublicaciones - 1) *
        $porPaginaPublicaciones;

    /*
    |--------------------------------------------------------------------------
    | PUBLICACIONES: obtener resultados
    |--------------------------------------------------------------------------
    */

    $sql = "SELECT
                publicaciones.id,
                publicaciones.contenido,
                publicaciones.created_at,
                publicaciones.feria_id,

                COALESCE(
                    users.nombre,
                    'Usuario'
                ) AS autor,

                users.foto_perfil,

                COALESCE(
                    ferias.titulo,
                    'Feria'
                ) AS feria_titulo

            FROM publicaciones

            LEFT JOIN users
                ON publicaciones.user_id = users.id

            LEFT JOIN ferias
                ON publicaciones.feria_id = ferias.id

            WHERE publicaciones.contenido ILIKE :criterio
               OR users.nombre ILIKE :criterio
               OR ferias.titulo ILIKE :criterio

            ORDER BY
                publicaciones.created_at DESC,
                publicaciones.id DESC

            LIMIT :limite
            OFFSET :offset";

    $stmt = $conn->prepare($sql);

    $stmt->bindValue(":criterio", $termino);
    $stmt->bindValue(
        ":limite",
        $porPaginaPublicaciones,
        PDO::PARAM_INT
    );
    $stmt->bindValue(
        ":offset",
        $offsetPublicaciones,
        PDO::PARAM_INT
    );

    $stmt->execute();

    $publicaciones =
        $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$totalResultados =
    $totalFerias +
    $totalUsuarios +
    $totalPublicaciones;

/*
|--------------------------------------------------------------------------
| Encabezado y breadcrumb
|--------------------------------------------------------------------------
*/

include("../templates/header.php");

$breadcrumb = [
    "Inicio" => "/index.php",
    "Resultados de búsqueda" => null
];

require_once("../templates/breadcrumb.php");
?>

<main class="container py-5">

    <div class="mb-4">

        <h1 class="h3 fw-bold mb-3">
            Resultados de búsqueda
        </h1>

        <?php if ($criterio !== "") : ?>

            <p class="mb-1">
                Criterio buscado:
                <strong>
                    <?= escapar($criterio) ?>
                </strong>
            </p>

            <p class="text-muted mb-0">
                Se encontraron
                <strong><?= $totalResultados ?></strong>
                resultado<?= $totalResultados === 1
                    ? ""
                    : "s" ?>.
            </p>

        <?php endif; ?>

    </div>

    <form
        action="/buscar.php"
        method="GET"
        class="row g-2 mb-5"
    >

        <div class="col-md-9">

            <label
                for="criterioBusqueda"
                class="visually-hidden"
            >
                Buscar en todo el sitio
            </label>

            <input
                type="search"
                id="criterioBusqueda"
                name="q"
                class="form-control"
                placeholder="Buscar en todo el sitio..."
                value="<?= escapar($criterio) ?>"
                required
            >

        </div>

        <div class="col-md-3">

            <button
                type="submit"
                class="btn btn-primary w-100"
            >
                Buscar
            </button>

        </div>

    </form>

    <?php if ($criterio === "") : ?>

        <div class="alert alert-info">
            Ingresá un criterio para comenzar la búsqueda.
        </div>

    <?php elseif ($totalResultados === 0) : ?>

        <div class="alert alert-warning">
            No se encontraron resultados para
            <strong><?= escapar($criterio) ?></strong>.
        </div>

    <?php else : ?>

        <!-- =========================================================
             FERIAS
        ========================================================== -->

        <?php if ($totalFerias > 0) : ?>

            <section class="mb-5">

                <div
                    class="d-flex justify-content-between
                           align-items-center mb-4"
                >

                    <h2 class="h4 fw-bold mb-0">
                        🎪 Ferias encontradas
                    </h2>

                    <span class="badge bg-primary fs-6">
                        <?= $totalFerias ?>
                    </span>

                </div>

                <div class="row">

                    <?php foreach ($ferias as $feria) : ?>

                        <div
                            class="col-lg-4 col-md-6 mb-4"
                        >

                            <div class="card h-100 shadow-sm">

                                <?php if (
                                    !empty($feria["flyer"])
                                ) : ?>

                                    <img
                                        src="<?= escapar(
                                            $feria["flyer"]
                                        ) ?>"
                                        class="card-img-top"
                                        alt="Flyer de la feria"
                                        style="
                                            height:220px;
                                            object-fit:cover;
                                        "
                                    >

                                <?php else : ?>

                                    <div
                                        class="bg-secondary
                                               text-white
                                               d-flex
                                               align-items-center
                                               justify-content-center"
                                        style="height:220px;"
                                    >
                                        Sin flyer
                                    </div>

                                <?php endif; ?>

                                <div
                                    class="card-body
                                           d-flex flex-column"
                                >

                                    <h3 class="h5 card-title">
                                        <?= escapar(
                                            $feria["titulo"]
                                        ) ?>
                                    </h3>

                                    <?php if (
                                        !empty(
                                            $feria["categoria"]
                                        )
                                    ) : ?>

                                        <span
                                            class="badge
                                                   bg-primary
                                                   mb-2
                                                   align-self-start"
                                        >
                                            <?= escapar(
                                                $feria["categoria"]
                                            ) ?>
                                        </span>

                                    <?php endif; ?>

                                    <?php if (
                                        !empty(
                                            $feria["descripcion"]
                                        )
                                    ) : ?>

                                        <p class="card-text">
                                            <?= escapar(
                                                resumirTexto(
                                                    $feria[
                                                        "descripcion"
                                                    ],
                                                    200
                                                )
                                            ) ?>
                                        </p>

                                    <?php endif; ?>

                                    <p class="mb-1">
                                        <strong>Fecha:</strong>

                                        <?= escapar(
                                            formatearFecha(
                                                $feria["fecha"]
                                            )
                                        ) ?>
                                    </p>

                                    <p class="mb-1">
                                        <strong>Ubicación:</strong>

                                        <?= escapar(
                                            $feria["ubicacion"]
                                            ?? "Sin ubicación"
                                        ) ?>
                                    </p>

                                    <p class="text-muted">
                                        Organiza:

                                        <?= escapar(
                                            $feria["organizador"]
                                            ?? "Sin organizador"
                                        ) ?>
                                    </p>

                                    <div class="mt-auto">

                                        <a
                                            href="/feria.php?id=<?= (int) $feria["id"] ?>"
                                            class="btn
                                                   btn-outline-primary"
                                        >
                                            Ver detalle
                                        </a>

                                    </div>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

                <?php if (
                    $totalPaginasFerias > 1
                ) : ?>

                    <nav
                        aria-label="Paginación de ferias"
                    >

                        <ul
                            class="pagination
                                   justify-content-center
                                   flex-wrap"
                        >

                            <li
                                class="page-item
                                <?= $paginaFerias <= 1
                                    ? "disabled"
                                    : "" ?>"
                            >
                                <a
                                    class="page-link"
                                    href="<?= escapar(
                                        crearUrlBusqueda(
                                            $criterio,
                                            1,
                                            $paginaUsuarios,
                                            $paginaPublicaciones
                                        )
                                    ) ?>"
                                >
                                    Primera
                                </a>
                            </li>

                            <li
                                class="page-item
                                <?= $paginaFerias <= 1
                                    ? "disabled"
                                    : "" ?>"
                            >
                                <a
                                    class="page-link"
                                    href="<?= escapar(
                                        crearUrlBusqueda(
                                            $criterio,
                                            max(
                                                1,
                                                $paginaFerias - 1
                                            ),
                                            $paginaUsuarios,
                                            $paginaPublicaciones
                                        )
                                    ) ?>"
                                >
                                    Anterior
                                </a>
                            </li>

                            <?php
                            $inicioFerias = max(
                                1,
                                $paginaFerias - 2
                            );

                            $finFerias = min(
                                $totalPaginasFerias,
                                $paginaFerias + 2
                            );
                            ?>

                            <?php for (
                                $i = $inicioFerias;
                                $i <= $finFerias;
                                $i++
                            ) : ?>

                                <li
                                    class="page-item
                                    <?= $i === $paginaFerias
                                        ? "active"
                                        : "" ?>"
                                >
                                    <a
                                        class="page-link"
                                        href="<?= escapar(
                                            crearUrlBusqueda(
                                                $criterio,
                                                $i,
                                                $paginaUsuarios,
                                                $paginaPublicaciones
                                            )
                                        ) ?>"
                                    >
                                        <?= $i ?>
                                    </a>
                                </li>

                            <?php endfor; ?>

                            <li
                                class="page-item
                                <?= $paginaFerias >=
                                    $totalPaginasFerias
                                    ? "disabled"
                                    : "" ?>"
                            >
                                <a
                                    class="page-link"
                                    href="<?= escapar(
                                        crearUrlBusqueda(
                                            $criterio,
                                            min(
                                                $totalPaginasFerias,
                                                $paginaFerias + 1
                                            ),
                                            $paginaUsuarios,
                                            $paginaPublicaciones
                                        )
                                    ) ?>"
                                >
                                    Siguiente
                                </a>
                            </li>

                            <li
                                class="page-item
                                <?= $paginaFerias >=
                                    $totalPaginasFerias
                                    ? "disabled"
                                    : "" ?>"
                            >
                                <a
                                    class="page-link"
                                    href="<?= escapar(
                                        crearUrlBusqueda(
                                            $criterio,
                                            $totalPaginasFerias,
                                            $paginaUsuarios,
                                            $paginaPublicaciones
                                        )
                                    ) ?>"
                                >
                                    Última
                                </a>
                            </li>

                        </ul>

                    </nav>

                <?php endif; ?>

            </section>

        <?php endif; ?>

        <!-- =========================================================
             USUARIOS
        ========================================================== -->

        <?php if ($totalUsuarios > 0) : ?>

            <section class="mb-5">

                <div
                    class="d-flex justify-content-between
                           align-items-center mb-4"
                >

                    <h2 class="h4 fw-bold mb-0">
                        👤 Usuarios encontrados
                    </h2>

                    <span class="badge bg-success fs-6">
                        <?= $totalUsuarios ?>
                    </span>

                </div>

                <div class="row">

                    <?php foreach ($usuarios as $usuario) : ?>

                        <div
                            class="col-md-6
                                   col-lg-4
                                   mb-4"
                        >

                            <div
                                class="card h-100
                                       shadow-sm border-0"
                            >

                                <div
                                    class="card-body
                                           text-center
                                           d-flex flex-column"
                                >

                                    <?php if (
                                        !empty(
                                            $usuario[
                                                "foto_perfil"
                                            ]
                                        )
                                    ) : ?>

                                        <img
                                            src="<?= escapar(
                                                $usuario[
                                                    "foto_perfil"
                                                ]
                                            ) ?>"
                                            alt="Foto de <?= escapar(
                                                $usuario["nombre"]
                                            ) ?>"
                                            class="rounded-circle
                                                   mx-auto mb-3"
                                            style="
                                                width:110px;
                                                height:110px;
                                                object-fit:cover;
                                            "
                                        >

                                    <?php else : ?>

                                        <div
                                            class="rounded-circle
                                                   bg-secondary
                                                   text-white
                                                   d-flex
                                                   align-items-center
                                                   justify-content-center
                                                   mx-auto mb-3"
                                            style="
                                                width:110px;
                                                height:110px;
                                                font-size:42px;
                                            "
                                        >
                                            👤
                                        </div>

                                    <?php endif; ?>

                                    <h3 class="h5 fw-bold">
                                        <?= escapar(
                                            $usuario["nombre"]
                                        ) ?>
                                    </h3>

                                    <p class="mb-2">

                                        <span
                                            class="badge bg-success"
                                        >
                                            <?= escapar(
                                                $usuario["rol"]
                                            ) ?>
                                        </span>

                                    </p>

                                    <p
                                        class="small
                                               text-muted
                                               mb-2"
                                    >
                                        <?= escapar(
                                            $usuario["email"]
                                        ) ?>
                                    </p>

                                    <p class="small text-muted">

                                        Usuario desde

                                        <?= escapar(
                                            formatearFecha(
                                                $usuario[
                                                    "created_at"
                                                ]
                                            )
                                        ) ?>

                                    </p>

                                    <div class="mt-auto">

                                        <a
                                            href="/perfil.php?id=<?= (int) $usuario["id"] ?>"
                                            class="btn
                                                   btn-outline-success"
                                        >
                                            Ver perfil
                                        </a>

                                    </div>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

                <?php if (
                    $totalPaginasUsuarios > 1
                ) : ?>

                    <nav
                        aria-label="Paginación de usuarios"
                    >

                        <ul
                            class="pagination
                                   justify-content-center
                                   flex-wrap"
                        >

                            <li
                                class="page-item
                                <?= $paginaUsuarios <= 1
                                    ? "disabled"
                                    : "" ?>"
                            >
                                <a
                                    class="page-link"
                                    href="<?= escapar(
                                        crearUrlBusqueda(
                                            $criterio,
                                            $paginaFerias,
                                            1,
                                            $paginaPublicaciones
                                        )
                                    ) ?>"
                                >
                                    Primera
                                </a>
                            </li>

                            <li
                                class="page-item
                                <?= $paginaUsuarios <= 1
                                    ? "disabled"
                                    : "" ?>"
                            >
                                <a
                                    class="page-link"
                                    href="<?= escapar(
                                        crearUrlBusqueda(
                                            $criterio,
                                            $paginaFerias,
                                            max(
                                                1,
                                                $paginaUsuarios - 1
                                            ),
                                            $paginaPublicaciones
                                        )
                                    ) ?>"
                                >
                                    Anterior
                                </a>
                            </li>

                            <?php
                            $inicioUsuarios = max(
                                1,
                                $paginaUsuarios - 2
                            );

                            $finUsuarios = min(
                                $totalPaginasUsuarios,
                                $paginaUsuarios + 2
                            );
                            ?>

                            <?php for (
                                $i = $inicioUsuarios;
                                $i <= $finUsuarios;
                                $i++
                            ) : ?>

                                <li
                                    class="page-item
                                    <?= $i === $paginaUsuarios
                                        ? "active"
                                        : "" ?>"
                                >
                                    <a
                                        class="page-link"
                                        href="<?= escapar(
                                            crearUrlBusqueda(
                                                $criterio,
                                                $paginaFerias,
                                                $i,
                                                $paginaPublicaciones
                                            )
                                        ) ?>"
                                    >
                                        <?= $i ?>
                                    </a>
                                </li>

                            <?php endfor; ?>

                            <li
                                class="page-item
                                <?= $paginaUsuarios >=
                                    $totalPaginasUsuarios
                                    ? "disabled"
                                    : "" ?>"
                            >
                                <a
                                    class="page-link"
                                    href="<?= escapar(
                                        crearUrlBusqueda(
                                            $criterio,
                                            $paginaFerias,
                                            min(
                                                $totalPaginasUsuarios,
                                                $paginaUsuarios + 1
                                            ),
                                            $paginaPublicaciones
                                        )
                                    ) ?>"
                                >
                                    Siguiente
                                </a>
                            </li>

                            <li
                                class="page-item
                                <?= $paginaUsuarios >=
                                    $totalPaginasUsuarios
                                    ? "disabled"
                                    : "" ?>"
                            >
                                <a
                                    class="page-link"
                                    href="<?= escapar(
                                        crearUrlBusqueda(
                                            $criterio,
                                            $paginaFerias,
                                            $totalPaginasUsuarios,
                                            $paginaPublicaciones
                                        )
                                    ) ?>"
                                >
                                    Última
                                </a>
                            </li>

                        </ul>

                    </nav>

                <?php endif; ?>

            </section>

        <?php endif; ?>

        <!-- =========================================================
             PUBLICACIONES
        ========================================================== -->

        <?php if (
            $totalPublicaciones > 0
        ) : ?>

            <section class="mb-5">

                <div
                    class="d-flex justify-content-between
                           align-items-center mb-4"
                >

                    <h2 class="h4 fw-bold mb-0">
                        📝 Publicaciones encontradas
                    </h2>

                    <span
                        class="badge
                               bg-secondary
                               fs-6"
                    >
                        <?= $totalPublicaciones ?>
                    </span>

                </div>

                <div class="row">

                    <?php foreach (
                        $publicaciones as $publicacion
                    ) : ?>

                        <div class="col-lg-6 mb-4">

                            <div
                                class="card h-100
                                       shadow-sm border-0"
                            >

                                <div
                                    class="card-body
                                           d-flex flex-column"
                                >

                                    <div
                                        class="d-flex
                                               align-items-center
                                               gap-3 mb-3"
                                    >

                                        <?php if (
                                            !empty(
                                                $publicacion[
                                                    "foto_perfil"
                                                ]
                                            )
                                        ) : ?>

                                            <img
                                                src="<?= escapar(
                                                    $publicacion[
                                                        "foto_perfil"
                                                    ]
                                                ) ?>"
                                                alt="Foto del autor"
                                                class="rounded-circle"
                                                style="
                                                    width:50px;
                                                    height:50px;
                                                    object-fit:cover;
                                                "
                                            >

                                        <?php else : ?>

                                            <div
                                                class="rounded-circle
                                                       bg-secondary
                                                       text-white
                                                       d-flex
                                                       align-items-center
                                                       justify-content-center"
                                                style="
                                                    width:50px;
                                                    height:50px;
                                                "
                                            >
                                                👤
                                            </div>

                                        <?php endif; ?>

                                        <div>

                                            <strong>
                                                <?= escapar(
                                                    $publicacion[
                                                        "autor"
                                                    ]
                                                ) ?>
                                            </strong>

                                            <div
                                                class="small
                                                       text-muted"
                                            >
                                                <?= escapar(
                                                    formatearFecha(
                                                        $publicacion[
                                                            "created_at"
                                                        ]
                                                    )
                                                ) ?>
                                            </div>

                                        </div>

                                    </div>

                                    <h3 class="h5 fw-bold">
                                        <?= escapar(
                                            $publicacion[
                                                "feria_titulo"
                                            ]
                                        ) ?>
                                    </h3>

                                    <p class="text-muted">
                                        <?= nl2br(
                                            escapar(
                                                resumirTexto(
                                                    $publicacion[
                                                        "contenido"
                                                    ],
                                                    260
                                                )
                                            )
                                        ) ?>
                                    </p>

                                    <div class="mt-auto">

                                        <a
                                            href="/feria.php?id=<?= (int) $publicacion["feria_id"] ?>"
                                            class="btn
                                                   btn-outline-secondary"
                                        >
                                            Ver publicación
                                        </a>

                                    </div>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

                <?php if (
                    $totalPaginasPublicaciones > 1
                ) : ?>

                    <nav
                        aria-label="Paginación de publicaciones"
                    >

                        <ul
                            class="pagination
                                   justify-content-center
                                   flex-wrap"
                        >

                            <li
                                class="page-item
                                <?= $paginaPublicaciones <= 1
                                    ? "disabled"
                                    : "" ?>"
                            >
                                <a
                                    class="page-link"
                                    href="<?= escapar(
                                        crearUrlBusqueda(
                                            $criterio,
                                            $paginaFerias,
                                            $paginaUsuarios,
                                            1
                                        )
                                    ) ?>"
                                >
                                    Primera
                                </a>
                            </li>

                            <li
                                class="page-item
                                <?= $paginaPublicaciones <= 1
                                    ? "disabled"
                                    : "" ?>"
                            >
                                <a
                                    class="page-link"
                                    href="<?= escapar(
                                        crearUrlBusqueda(
                                            $criterio,
                                            $paginaFerias,
                                            $paginaUsuarios,
                                            max(
                                                1,
                                                $paginaPublicaciones - 1
                                            )
                                        )
                                    ) ?>"
                                >
                                    Anterior
                                </a>
                            </li>

                            <?php
                            $inicioPublicaciones = max(
                                1,
                                $paginaPublicaciones - 2
                            );

                            $finPublicaciones = min(
                                $totalPaginasPublicaciones,
                                $paginaPublicaciones + 2
                            );
                            ?>

                            <?php for (
                                $i = $inicioPublicaciones;
                                $i <= $finPublicaciones;
                                $i++
                            ) : ?>

                                <li
                                    class="page-item
                                    <?= $i ===
                                        $paginaPublicaciones
                                        ? "active"
                                        : "" ?>"
                                >
                                    <a
                                        class="page-link"
                                        href="<?= escapar(
                                            crearUrlBusqueda(
                                                $criterio,
                                                $paginaFerias,
                                                $paginaUsuarios,
                                                $i
                                            )
                                        ) ?>"
                                    >
                                        <?= $i ?>
                                    </a>
                                </li>

                            <?php endfor; ?>

                            <li
                                class="page-item
                                <?= $paginaPublicaciones >=
                                    $totalPaginasPublicaciones
                                    ? "disabled"
                                    : "" ?>"
                            >
                                <a
                                    class="page-link"
                                    href="<?= escapar(
                                        crearUrlBusqueda(
                                            $criterio,
                                            $paginaFerias,
                                            $paginaUsuarios,
                                            min(
                                                $totalPaginasPublicaciones,
                                                $paginaPublicaciones + 1
                                            )
                                        )
                                    ) ?>"
                                >
                                    Siguiente
                                </a>
                            </li>

                            <li
                                class="page-item
                                <?= $paginaPublicaciones >=
                                    $totalPaginasPublicaciones
                                    ? "disabled"
                                    : "" ?>"
                            >
                                <a
                                    class="page-link"
                                    href="<?= escapar(
                                        crearUrlBusqueda(
                                            $criterio,
                                            $paginaFerias,
                                            $paginaUsuarios,
                                            $totalPaginasPublicaciones
                                        )
                                    ) ?>"
                                >
                                    Última
                                </a>
                            </li>

                        </ul>

                    </nav>

                <?php endif; ?>

            </section>

        <?php endif; ?>

    <?php endif; ?>

</main>

<?php
include("../templates/footer.php");
?>