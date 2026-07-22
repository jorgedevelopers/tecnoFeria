<?php
require_once("../config/database.php");

$mensajeEstado = null;
$tipoMensaje = "info";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombreApellido = trim($_POST["nombre_apellido"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $asunto = trim($_POST["asunto"] ?? "");
    $mensaje = trim($_POST["mensaje"] ?? "");

    if (
        $nombreApellido === "" ||
        $email === "" ||
        $asunto === "" ||
        $mensaje === ""
    ) {
        $mensajeEstado = "Todos los campos son obligatorios.";
        $tipoMensaje = "warning";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensajeEstado = "Ingresá un correo electrónico válido.";
        $tipoMensaje = "warning";
    } else {

        try {

            $sql = "INSERT INTO contactos
                    (
                        nombre_apellido,
                        email,
                        asunto,
                        mensaje,
                        estado
                    )
                    VALUES
                    (
                        :nombre_apellido,
                        :email,
                        :asunto,
                        :mensaje,
                        'pendiente'
                    )";

            $stmt = $conn->prepare($sql);

            $stmt->bindValue(":nombre_apellido", $nombreApellido);
            $stmt->bindValue(":email", $email);
            $stmt->bindValue(":asunto", $asunto);
            $stmt->bindValue(":mensaje", $mensaje);

            $stmt->execute();

            $mensajeEstado = "✅ Tu consulta fue enviada correctamente.";
            $tipoMensaje = "success";

            $nombreApellido = "";
            $email = "";
            $asunto = "";
            $mensaje = "";

        } catch (PDOException $e) {

            $mensajeEstado = "❌ No se pudo enviar la consulta.";
            $tipoMensaje = "danger";
        }
    }
}

require_once("../templates/header.php");
?>

<main class="container py-4">

    <?php
    $breadcrumb = [
        "Inicio" => "/index.php",
        "Contacto" => null
    ];

    require_once("../templates/breadcrumb.php");
    ?>

    <div class="row justify-content-center">

        <div class="col-lg-8">

            <div class="card border-0 shadow-sm">

                <div class="card-body p-4 p-md-5">

                    <div class="text-center mb-4">

                        <span class="badge bg-primary mb-3">
                            Contacto
                        </span>

                        <h2 class="fw-bold mb-3">
                            Contactate con TecnoFeria Argentina
                        </h2>

                        <p class="text-muted mb-0">
                            Empresas, instituciones y organizadores
                            pueden comunicarse para solicitar información
                            sobre publicaciones destacadas y servicios
                            de promoción.
                        </p>

                    </div>

                    <?php if ($mensajeEstado) : ?>

                        <div class="alert alert-<?= $tipoMensaje ?>">
                            <?= htmlspecialchars($mensajeEstado) ?>
                        </div>

                    <?php endif; ?>

                    <form method="POST">

                        <div class="mb-3">

                            <label
                                for="nombre_apellido"
                                class="form-label"
                            >
                                Nombres y apellidos
                            </label>

                            <input
                                type="text"
                                id="nombre_apellido"
                                name="nombre_apellido"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $nombreApellido ?? ""
                                ) ?>"
                                required
                            >

                        </div>

                        <div class="mb-3">

                            <label
                                for="email"
                                class="form-label"
                            >
                                Correo electrónico
                            </label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $email ?? ""
                                ) ?>"
                                required
                            >

                        </div>

                        <div class="mb-3">

                            <label
                                for="asunto"
                                class="form-label"
                            >
                                Asunto
                            </label>

                            <input
                                type="text"
                                id="asunto"
                                name="asunto"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $asunto ?? ""
                                ) ?>"
                                required
                            >

                        </div>

                        <div class="mb-4">

                            <label
                                for="mensaje"
                                class="form-label"
                            >
                                Mensaje
                            </label>

                            <textarea
                                id="mensaje"
                                name="mensaje"
                                class="form-control"
                                rows="6"
                                required
                            ><?= htmlspecialchars(
                                $mensaje ?? ""
                            ) ?></textarea>

                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Enviar mensaje
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</main>

<?php
require_once("../templates/footer.php");
?>