<?php
require_once("../config/database.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$mensaje = null;
$tipoMensaje = "info";
$enlaceRecuperacion = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");

    if ($email === "") {

        $mensaje = "Ingresá tu correo electrónico.";
        $tipoMensaje = "warning";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $mensaje = "Ingresá un correo electrónico válido.";
        $tipoMensaje = "warning";

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Buscar usuario
            |--------------------------------------------------------------------------
            */

            $sql = "SELECT id, nombre, email
                    FROM users
                    WHERE email = :email
                    LIMIT 1";

            $stmt = $conn->prepare($sql);
            $stmt->bindValue(":email", $email);
            $stmt->execute();

            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            /*
            |--------------------------------------------------------------------------
            | Respuesta genérica
            |--------------------------------------------------------------------------
            | Evita revelar si un email está registrado.
            */

            $mensaje = "Si el correo está registrado, se generó un enlace temporal de recuperación.";
            $tipoMensaje = "success";

            if ($usuario) {

                /*
                |--------------------------------------------------------------------------
                | Invalidar solicitudes anteriores sin usar
                |--------------------------------------------------------------------------
                */

                $sql = "UPDATE password_resets
                        SET used_at = CURRENT_TIMESTAMP
                        WHERE user_id = :user_id
                          AND used_at IS NULL";

                $stmt = $conn->prepare($sql);
                $stmt->bindValue(
                    ":user_id",
                    (int) $usuario["id"],
                    PDO::PARAM_INT
                );
                $stmt->execute();

                /*
                |--------------------------------------------------------------------------
                | Generar token seguro
                |--------------------------------------------------------------------------
                */

                $token = bin2hex(random_bytes(32));
                $tokenHash = hash("sha256", $token);

                $expiresAt = date(
                    "Y-m-d H:i:s",
                    time() + 3600
                );

                /*
                |--------------------------------------------------------------------------
                | Guardar recuperación
                |--------------------------------------------------------------------------
                */

                $sql = "INSERT INTO password_resets
                        (
                            user_id,
                            token_hash,
                            expires_at
                        )
                        VALUES
                        (
                            :user_id,
                            :token_hash,
                            :expires_at
                        )";

                $stmt = $conn->prepare($sql);

                $stmt->bindValue(
                    ":user_id",
                    (int) $usuario["id"],
                    PDO::PARAM_INT
                );

                $stmt->bindValue(
                    ":token_hash",
                    $tokenHash
                );

                $stmt->bindValue(
                    ":expires_at",
                    $expiresAt
                );

                $stmt->execute();

                /*
                |--------------------------------------------------------------------------
                | Enlace temporal de prueba
                |--------------------------------------------------------------------------
                */

                $enlaceRecuperacion =
                    "/restablecer_password.php?token=" .
                    urlencode($token);
            }

        } catch (Throwable $e) {

            $mensaje = "No se pudo procesar la solicitud de recuperación.";
            $tipoMensaje = "danger";
        }
    }
}

include("../templates/header.php");
?>

<main class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-7 col-lg-6">

            <div class="card border-0 shadow-sm">

                <div class="card-body p-4 p-md-5">

                    <h1 class="h3 fw-bold mb-3">
                        Recuperar contraseña
                    </h1>

                    <p class="text-muted">
                        Ingresá el correo electrónico asociado a tu cuenta.
                        El enlace temporal tendrá una validez de una hora.
                    </p>

                    <?php if ($mensaje !== null) : ?>

                        <div class="alert alert-<?= $tipoMensaje ?>">
                            <?= htmlspecialchars(
                                $mensaje,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </div>

                    <?php endif; ?>

                    <form method="POST">

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
                                    $_POST["email"] ?? "",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                                required
                            >

                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >
                            Generar enlace de recuperación
                        </button>

                    </form>

                    <?php if ($enlaceRecuperacion !== null) : ?>

                        <div class="alert alert-warning mt-4">

                            <strong>
                                Enlace temporal de prueba:
                            </strong>

                            <p class="mb-2 mt-2">
                                Este enlace se muestra solo mientras
                                configuramos el envío de correos.
                            </p>

                            <a
                                href="<?= htmlspecialchars(
                                    $enlaceRecuperacion,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                                class="btn btn-outline-dark"
                            >
                                Restablecer contraseña
                            </a>

                        </div>

                    <?php endif; ?>

                    <div class="text-center mt-4">

                        <a
                            href="/login.php"
                            class="text-decoration-none"
                        >
                            ← Volver al inicio de sesión
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</main>

<?php
include("../templates/footer.php");
?>