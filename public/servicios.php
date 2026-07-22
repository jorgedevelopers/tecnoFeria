<?php
require_once("../templates/header.php");
?>

<main class="container py-4">

    <?php
    $breadcrumb = [
        "Inicio" => "/index.php",
        "Servicios" => null
    ];

    require_once("../templates/breadcrumb.php");
    ?>
    <div class="text-center mb-5">

        <span class="badge bg-primary mb-3">
            Servicios
        </span>

        <h2 class="fw-bold">
            Servicios de TecnoFeria Argentina
        </h2>

        <p class="text-muted">
            Herramientas para organizar, difundir y descubrir
            ferias tecnológicas.
        </p>

    </div>

    <div class="row g-4">

        <div class="col-md-6 col-lg-4">

            <div class="card h-100 border-0 shadow-sm">

                <div class="card-body p-4">

                    <div class="fs-1 mb-3">
                        📅
                    </div>

                    <h3 class="h5 fw-bold">
                        Crear y organizar ferias
                    </h3>

                    <p class="text-muted mb-0">
                        Los usuarios registrados pueden publicar,
                        editar y administrar sus propias ferias
                        tecnológicas.
                    </p>

                </div>

            </div>

        </div>

        <div class="col-md-6 col-lg-4">

            <div class="card h-100 border-0 shadow-sm">

                <div class="card-body p-4">

                    <div class="fs-1 mb-3">
                        🔎
                    </div>

                    <h3 class="h5 fw-bold">
                        Descubrir eventos
                    </h3>

                    <p class="text-muted mb-0">
                        Los visitantes pueden explorar, buscar y filtrar
                        ferias según su fecha, ubicación, categoría o
                        temática.
                    </p>

                </div>

            </div>

        </div>

        <div class="col-md-6 col-lg-4">

            <div class="card h-100 border-0 shadow-sm">

                <div class="card-body p-4">

                    <div class="fs-1 mb-3">
                        🤝
                    </div>

                    <h3 class="h5 fw-bold">
                        Participar y vincularse
                    </h3>

                    <p class="text-muted mb-0">
                        Las personas pueden participar en eventos,
                        conocer organizadores e integrarse a una
                        comunidad tecnológica.
                    </p>

                </div>

            </div>

        </div>

        <div class="col-md-6 col-lg-4">

            <div class="card h-100 border-0 shadow-sm">

                <div class="card-body p-4">

                    <div class="fs-1 mb-3">
                        💬
                    </div>

                    <h3 class="h5 fw-bold">
                        Compartir novedades
                    </h3>

                    <p class="text-muted mb-0">
                        Los usuarios pueden publicar novedades,
                        realizar comentarios y reaccionar a los
                        contenidos de las ferias.
                    </p>

                </div>

            </div>

        </div>

        <div class="col-md-6 col-lg-4">

            <div class="card h-100 border-0 shadow-sm">

                <div class="card-body p-4">

                    <div class="fs-1 mb-3">
                        📣
                    </div>

                    <h3 class="h5 fw-bold">
                        Promoción destacada
                    </h3>

                    <p class="text-muted mb-0">
                        Empresas e instituciones pueden solicitar
                        publicaciones promocionadas para aumentar
                        su visibilidad en la plataforma.
                    </p>

                </div>

            </div>

        </div>

        <div class="col-md-6 col-lg-4">

            <div class="card h-100 border-0 shadow-sm">

                <div class="card-body p-4">

                    <div class="fs-1 mb-3">
                        🚀
                    </div>

                    <h3 class="h5 fw-bold">
                        Posicionamiento preferencial
                    </h3>

                    <p class="text-muted mb-0">
                        Las publicaciones destacadas podrán aparecer
                        entre los primeros resultados de búsqueda y
                        en espacios de mayor visibilidad.
                    </p>

                </div>

            </div>

        </div>

    </div>

    <div class="text-center mt-5">

        <a
            href="/contacto.php"
            class="btn btn-primary btn-lg"
        >
            Consultar por servicios promocionados
        </a>

    </div>

</main>

<?php
require_once("../templates/footer.php");
?>