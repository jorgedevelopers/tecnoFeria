<?php
require_once("../templates/header.php");
?>

<main class="container py-4">

    <?php
    $breadcrumb = [
        "Inicio" => "/index.php",
        "Institucional" => null
    ];

    require_once("../templates/breadcrumb.php");
    ?>

    <div class="row justify-content-center">

        <div class="col-lg-9">

            <div class="card border-0 shadow-sm">

                <div class="card-body p-4 p-md-5">

                    <span class="badge bg-primary mb-3">
                        Institucional
                    </span>

                    <h2 class="fw-bold mb-4">
                        TecnoFeria Argentina
                    </h2>

                    <p class="lead">
                        Conectando personas, instituciones y empresas
                        para impulsar la innovación tecnológica.
                    </p>

                    <p>
                        TecnoFeria Argentina es una plataforma que busca
                        contribuir al crecimiento tecnológico del país
                        mediante la difusión de ferias, eventos, proyectos
                        e iniciativas vinculadas con la tecnología y la
                        innovación.
                    </p>

                    <p>
                        Nuestro propósito es construir una comunidad de
                        vinculación que facilite el encuentro y la
                        colaboración entre personas, empresas,
                        instituciones educativas, emprendedores,
                        investigadores y organizaciones.
                    </p>

                    <p>
                        La plataforma promueve la generación de relaciones
                        entre personas y personas, personas y empresas,
                        empresas y empresas, así como entre instituciones
                        y los distintos actores del sector tecnológico.
                    </p>

                    <p>
                        A través de TecnoFeria Argentina, los organizadores
                        pueden publicar y gestionar sus ferias, mientras
                        que los visitantes pueden descubrir eventos,
                        participar, comunicarse e interactuar con otros
                        integrantes de la comunidad.
                    </p>

                    <p class="mb-0">
                        Las empresas e instituciones también pueden
                        comunicarse con TecnoFeria Argentina para acceder
                        a servicios promocionados o publicaciones
                        destacadas, que les permitan obtener una mayor
                        visibilidad y aparecer entre los primeros
                        resultados de búsqueda de la plataforma.
                    </p>

                </div>

            </div>

        </div>

    </div>

</main>

<?php
require_once("../templates/footer.php");
?>