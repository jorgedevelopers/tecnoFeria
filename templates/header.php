<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("../config/database.php");

$fotoNavbar = null;

if (isset($_SESSION["user_id"])) {
    $sql = "SELECT foto_perfil
            FROM users
            WHERE id = :id
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(
        ":id",
        (int) $_SESSION["user_id"],
        PDO::PARAM_INT
    );
    $stmt->execute();

    $usuarioNavbar = $stmt->fetch(PDO::FETCH_ASSOC);

    $fotoNavbar = $usuarioNavbar["foto_perfil"] ?? null;
}

/*
|--------------------------------------------------------------------------
| Página actual
|--------------------------------------------------------------------------
| Se utiliza para resaltar en el menú la sección que está abierta.
*/
$paginaActual = basename($_SERVER["PHP_SELF"]);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <title>TecnoFeria Argentina</title>

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="description"
        content="TecnoFeria Argentina, Red Nacional de Ferias Tecnológicas."
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            min-height: 100vh;
            background-color: #f8f9fa;
        }

        .encabezado-principal {
            background: linear-gradient(
                135deg,
                #071426 0%,
                #0d2f5f 55%,
                #4b1d73 100%
            );
        }

        .logo-tecnoferia {
            width: 76px;
            height: 76px;
            object-fit: contain;
            background-color: #ffffff;
            border-radius: 18px;
            padding: 4px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }

        .nombre-tecnoferia {
            margin: 0;
            font-size: 1.65rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .descripcion-tecnoferia {
            margin: 4px 0 0;
            color: rgba(255, 255, 255, 0.82);
            font-size: 0.92rem;
        }

        .fecha-hora {
            min-width: 245px;
            padding: 8px 14px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 10px;
            background-color: rgba(255, 255, 255, 0.08);
            color: #ffffff;
            text-align: center;
        }

        .fecha-hora .fecha {
            display: block;
            font-size: 0.86rem;
        }

        .fecha-hora .hora {
            display: block;
            margin-top: 2px;
            font-size: 1.05rem;
            font-weight: 700;
        }

        .navbar-principal .nav-link {
            position: relative;
            color: rgba(255, 255, 255, 0.82);
            font-weight: 500;
            transition:
                color 0.2s ease,
                background-color 0.2s ease;
        }

        .navbar-principal .nav-link:hover,
        .navbar-principal .nav-link:focus {
            color: #ffffff;
        }

        .navbar-principal .nav-link.active {
            color: #ffffff;
            font-weight: 700;
        }

        .navbar-principal .nav-link.active::after {
            position: absolute;
            right: 8px;
            bottom: 3px;
            left: 8px;
            height: 2px;
            border-radius: 5px;
            background-color: #ffc107;
            content: "";
        }

        .foto-navbar {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.65);
            border-radius: 50%;
        }

        .avatar-navbar {
            width: 40px;
            height: 40px;
            border: 2px solid rgba(255, 255, 255, 0.45);
            border-radius: 50%;
        }

        .nombre-usuario-navbar {
            max-width: 140px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        @media (max-width: 991.98px) {
            .logo-tecnoferia {
                width: 60px;
                height: 60px;
            }

            .nombre-tecnoferia {
                font-size: 1.35rem;
            }

            .fecha-hora {
                width: 100%;
                min-width: auto;
                margin-top: 12px;
            }

            .navbar-principal .nav-link.active::after {
                display: none;
            }

            .navbar-principal .nav-link.active {
                padding-left: 12px;
                border-radius: 6px;
                background-color: rgba(255, 255, 255, 0.12);
            }

            .acciones-usuario {
                width: 100%;
                padding-top: 12px;
                border-top: 1px solid rgba(255, 255, 255, 0.15);
            }
        }
    </style>
</head>

<body>

<header class="encabezado-principal text-white py-3">

    <div class="container">

        <div
            class="d-flex flex-column flex-lg-row
                   justify-content-between align-items-lg-center gap-3"
        >

            <a
                href="/index.php"
                class="text-white text-decoration-none"
            >
                <div class="d-flex align-items-center gap-3">

                    <img
                        src="/assets/img/logo-tecnoferia.png"
                        alt="Logo de TecnoFeria Argentina"
                        class="logo-tecnoferia"
                    >

                    <div>
                        <h1 class="nombre-tecnoferia">
                            TecnoFeria Argentina
                        </h1>

                        <p class="descripcion-tecnoferia">
                            Red Nacional de Ferias Tecnológicas
                        </p>
                    </div>

                </div>
            </a>

            <div
                id="fechaHora"
                class="fecha-hora"
                aria-live="polite"
            >
                <span
                    id="fechaActual"
                    class="fecha"
                >
                    Cargando fecha...
                </span>

                <span
                    id="horaActual"
                    class="hora"
                >
                    --:--:--
                </span>
            </div>

        </div>

    </div>

</header>

<nav
    class="navbar navbar-expand-lg navbar-dark
           bg-dark shadow-sm navbar-principal"
>

    <div class="container">

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#menuPrincipal"
            aria-controls="menuPrincipal"
            aria-expanded="false"
            aria-label="Abrir menú de navegación"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <div
            class="collapse navbar-collapse"
            id="menuPrincipal"
        >

            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <li class="nav-item">
                    <a
                        href="/index.php"
                        class="nav-link
                        <?= $paginaActual === "index.php"
                            ? "active"
                            : "" ?>"
                    >
                        Inicio
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        href="/institucional.php"
                        class="nav-link
                        <?= $paginaActual === "institucional.php"
                            ? "active"
                            : "" ?>"
                    >
                        Institucional
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        href="/ferias.php"
                        class="nav-link
                        <?= in_array(
                            $paginaActual,
                            [
                                "ferias.php",
                                "feria.php",
                                "crear_feria.php",
                                "editar_feria.php"
                            ],
                            true
                        )
                            ? "active"
                            : "" ?>"
                    >
                        Galería de ferias
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        href="/servicios.php"
                        class="nav-link
                        <?= $paginaActual === "servicios.php"
                            ? "active"
                            : "" ?>"
                    >
                        Servicios
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        href="/contacto.php"
                        class="nav-link
                        <?= $paginaActual === "contacto.php"
                            ? "active"
                            : "" ?>"
                    >
                        Contacto
                    </a>
                </li>

            </ul>
                        <form
                action="/buscar.php"
                method="GET"
                class="d-flex me-lg-3 mb-3 mb-lg-0"
                role="search"
            >

                <input
                    type="search"
                    name="q"
                    class="form-control form-control-sm me-2"
                    placeholder="Buscar..."
                    value="<?= htmlspecialchars(
                        $_GET["q"] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    required
                >

                <button
                    class="btn btn-outline-light btn-sm"
                    type="submit"
                >
                    Buscar
                </button>

            </form>

            <div
                class="acciones-usuario d-flex flex-column
                       flex-lg-row align-items-lg-center gap-2"
            >

                <?php if (isset($_SESSION["user_nombre"])) : ?>

                    <a
                        href="/perfil.php?id=<?= (int) $_SESSION["user_id"] ?>"
                        class="text-decoration-none d-flex
                               align-items-center gap-2 text-white"
                        title="Ver mi perfil"
                    >

                        <?php if (!empty($fotoNavbar)) : ?>

                            <img
                                src="<?= htmlspecialchars(
                                    $fotoNavbar,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                                alt="Foto de perfil"
                                class="foto-navbar"
                            >

                        <?php else : ?>

                            <div
                                class="avatar-navbar bg-secondary
                                       text-white d-flex
                                       align-items-center
                                       justify-content-center"
                                aria-label="Usuario sin foto de perfil"
                            >
                                👤
                            </div>

                        <?php endif; ?>

                        <span class="nombre-usuario-navbar">
                            <?= htmlspecialchars(
                                $_SESSION["user_nombre"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </span>

                    </a>

                    <a
                        href="/dashboard.php"
                        class="btn btn-primary btn-sm"
                    >
                        Mi panel
                    </a>

                    <?php if (
                        isset($_SESSION["user_rol"]) &&
                        $_SESSION["user_rol"] === "admin"
                    ) : ?>

                        <a
                            href="/admin.php"
                            class="btn btn-warning btn-sm"
                        >
                            ⚙️ Administración
                        </a>

                    <?php endif; ?>

                    <a
                        href="/logout.php"
                        class="btn btn-outline-light btn-sm"
                    >
                        Cerrar sesión
                    </a>

                <?php else : ?>

                    <a
                        href="/login.php"
                        class="btn btn-outline-light btn-sm"
                    >
                        Iniciar sesión
                    </a>

                    <a
                        href="/registro.php"
                        class="btn btn-primary btn-sm"
                    >
                        Registrarse
                    </a>

                <?php endif; ?>

            </div>

        </div>

    </div>

</nav>

<script>
    function actualizarFechaHora() {
        const ahora = new Date();

        const opcionesFecha = {
            timeZone: "America/Argentina/Catamarca",
            weekday: "long",
            day: "2-digit",
            month: "long",
            year: "numeric"
        };

        const opcionesHora = {
            timeZone: "America/Argentina/Catamarca",
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
            hour12: false
        };

        let fecha = ahora.toLocaleDateString(
            "es-AR",
            opcionesFecha
        );

        const hora = ahora.toLocaleTimeString(
            "es-AR",
            opcionesHora
        );

        fecha = fecha.charAt(0).toUpperCase() + fecha.slice(1);

        document.getElementById(
            "fechaActual"
        ).textContent = fecha;

        document.getElementById(
            "horaActual"
        ).textContent = hora;
    }

    actualizarFechaHora();

    setInterval(actualizarFechaHora, 1000);
</script>