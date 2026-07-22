<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$paginaActual = basename($_SERVER["PHP_SELF"]);
$esAdmin = isset($_SESSION["user_rol"]) &&
           $_SESSION["user_rol"] === "admin";
?>

<aside class="col-lg-3 mb-4">

    <div class="card border-0 shadow-sm">

        <div class="card-header bg-dark text-white fw-bold">
            <?= $esAdmin ? "Administración" : "Mi cuenta" ?>
        </div>

        <div class="list-group list-group-flush">

            <?php if ($esAdmin) : ?>

                <a
                    href="/admin.php#resumen"
                    class="list-group-item list-group-item-action
                    <?= $paginaActual === "admin.php"
                        ? "active"
                        : "" ?>"
                >
                    📊 Resumen general
                </a>

                <a
                    href="/admin.php#usuarios"
                    class="list-group-item list-group-item-action"
                >
                    👥 Usuarios
                </a>

                <a
                    href="/admin.php#ferias"
                    class="list-group-item list-group-item-action"
                >
                    📅 Ferias
                </a>

                <a
                    href="/admin.php#participaciones"
                    class="list-group-item list-group-item-action"
                >
                    🤝 Participaciones
                </a>

                <a
                    href="/admin.php#publicaciones"
                    class="list-group-item list-group-item-action"
                >
                    📝 Publicaciones
                </a>

                <a
                    href="/admin.php#comentarios"
                    class="list-group-item list-group-item-action"
                >
                    💬 Comentarios
                </a>

                <a
                    href="/index.php"
                    class="list-group-item list-group-item-action"
                >
                    🏠 Volver al sitio
                </a>

            <?php else : ?>

                <a
                    href="/dashboard.php"
                    class="list-group-item list-group-item-action
                    <?= $paginaActual === "dashboard.php"
                        ? "active"
                        : "" ?>"
                >
                    📊 Mi panel
                </a>

                <a
                    href="/perfil.php?id=<?= (int) (
                        $_SESSION["user_id"] ?? 0
                    ) ?>"
                    class="list-group-item list-group-item-action
                    <?= $paginaActual === "perfil.php"
                        ? "active"
                        : "" ?>"
                >
                    👤 Mi perfil
                </a>

                <a
                    href="/crear_feria.php"
                    class="list-group-item list-group-item-action
                    <?= $paginaActual === "crear_feria.php"
                        ? "active"
                        : "" ?>"
                >
                    ➕ Crear feria
                </a>

                <a
                    href="/dashboard.php#mis-ferias"
                    class="list-group-item list-group-item-action"
                >
                    📅 Mis ferias
                </a>

                <a
                    href="/dashboard.php#participaciones"
                    class="list-group-item list-group-item-action"
                >
                    🤝 Mis participaciones
                </a>

                <a
                    href="/dashboard.php#favoritos"
                    class="list-group-item list-group-item-action"
                >
                    ❤️ Mis favoritos
                </a>

                <a
                    href="/logout.php"
                    class="list-group-item
                           list-group-item-action
                           text-danger"
                >
                    🚪 Cerrar sesión
                </a>

            <?php endif; ?>

        </div>

    </div>

</aside>