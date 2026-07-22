<?php
require_once("../config/database.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$token = trim($_GET["token"] ?? $_POST["token"] ?? "");

$mensaje = null;
$tipoMensaje = "info";
$tokenValido = false;
$recuperacion = null;

/*
|--------------------------------------------------------------------------
| Validar token recibido
|--------------------------------------------------------------------------
*/

if ($token !== "") {

    $tokenHash = hash("sha256", $token);

    try {

        $sql = "SELECT
                    password_resets.id,
                    password_resets.user_id,
                    password_resets.expires_at,
                    password_resets.used_at,
                    users.email,
                    users.nombre
                FROM password_resets

                INNER JOIN users
                    ON password_resets.user_id = users.id

                WHERE password_resets.token_hash = :token_hash
                  AND password_resets.used_at IS NULL
                  AND password_resets.expires_at > CURRENT_TIMESTAMP

                ORDER BY password_resets.created_at DESC

                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue(":token_hash", $tokenHash);
        $stmt->execute();

        $recuperacion = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($recuperacion) {
            $tokenValido = true;
        } else {
            $mensaje = "El enlace de recuperación es inválido, ya fue utilizado o está vencido.";
            $tipoMensaje = "danger";
        }

    } catch (PDOException $e) {

        $mensaje = "No se pudo validar el enlace de recuperación.";
        $tipoMensaje = "danger";
    }

} else {

    $mensaje = "No se recibió un token de recuperación.";
    $tipoMensaje = "danger";
}

/*
|--------------------------------------------------------------------------
| Procesar nueva contraseña
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    $tokenValido &&
    $recuperacion
) {

    $password = $_POST["password"] ?? "";
    $passwordConfirmacion =
        $_POST["password_confirmacion"] ?? "";

    if ($password === "" || $passwordConfirmacion === "") {

        $mensaje = "Completá ambos campos de contraseña.";
        $tipoMensaje = "warning";

    } elseif (strlen($password) < 8) {

        $mensaje = "La contraseña debe tener al menos 8 caracteres.";
        $tipoMensaje = "warning";

    } elseif ($password !== $passwordConfirmacion) {

        $mensaje = "Las contraseñas no coinciden.";
        $tipoMensaje = "warning";

    } else {

        try {

            $conn->beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | Actualizar contraseña
            |--------------------------------------------------------------------------
            */

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $sql = "UPDATE users
                    SET password = :password
                    WHERE id = :user_id";

            $stmt = $conn->prepare($sql);

            $stmt->bindValue(
                ":password",
                $passwordHash
            );

            $stmt->bindValue(
                ":user_id",
                (int) $recuperacion["user_id"],
                PDO::PARAM_INT
            );

            $stmt->execute();

            /*
            |--------------------------------------------------------------------------
            | Marcar token como utilizado
            |--------------------------------------------------------------------------
            */

            $sql = "UPDATE password_resets
                    SET used_at = CURRENT_TIMESTAMP
                    WHERE id = :id";

            $stmt = $conn->prepare($sql);

            $stmt->bindValue(
                ":id",
                (int) $recuperacion["id"],
                PDO::PARAM_INT
            );

            $stmt->execute();

            /*
            |--------------------------------------------------------------------------
            | Invalidar cualquier otro token pendiente
            |--------------------------------------------------------------------------
            */

            $sql = "UPDATE password_resets
                    SET used_at = CURRENT_TIMESTAMP
                    WHERE user_id = :user_id
                      AND used_at IS NULL";

            $stmt = $conn->prepare($sql);

            $stmt->bindValue(
                ":user_id",
                (int) $recuperacion["user_id"],
                PDO::PARAM_INT
            );

            $stmt->execute();

            $conn->commit();

            $_SESSION["password_reset_success"] =
                "Contraseña actualizada correctamente. Ya podés iniciar sesión.";

            header("Location: /login.php");
            exit;

        } catch (Throwable $e) {

            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $mensaje = "No se pudo actualizar la contraseña.";
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
                        Restablecer contraseña
                    </h1>

                    <?php if ($mensaje !== null) : ?>

                        <div class="alert alert-<?= $tipoMensaje ?>">
                            <?= htmlspecialchars(
                                $mensaje,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </div>

                    <?php endif; ?>

                    <?php if ($tokenValido && $recuperacion) : ?>

                        <p class="text-muted">
                            Vas a cambiar la contraseña de la cuenta:
                        </p>

                        <div class="alert alert-light border">

                            <strong>
                                <?= htmlspecialchars(
                                    $recuperacion["nombre"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </strong>

                            <br>

                            <span class="text-muted">
                                <?= htmlspecialchars(
                                    $recuperacion["email"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </span>

                        </div>

                        <form method="POST">

                            <input
                                type="hidden"
                                name="token"
                                value="<?= htmlspecialchars(
                                    $token,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                            >

                            <div class="mb-3">

                                <label
                                    for="password"
                                    class="form-label"
                                >
                                    Nueva contraseña
                                </label>

                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="form-control"
                                    minlength="8"
                                    autocomplete="new-password"
                                    required
                                >

                                <div class="form-text">
                                    Debe tener al menos 8 caracteres.
                                </div>

                            </div>

                            <div class="mb-4">

                                <label
                                    for="password_confirmacion"
                                    class="form-label"
                                >
                                    Confirmar nueva contraseña
                                </label>

                                <input
                                    type="password"
                                    id="password_confirmacion"
                                    name="password_confirmacion"
                                    class="form-control"
                                    minlength="8"
                                    autocomplete="new-password"
                                    required
                                >

                            </div>

                            <button
                                type="submit"
                                class="btn btn-primary w-100"
                            >
                                Guardar nueva contraseña
                            </button>

                        </form>

                    <?php else : ?>

                        <div class="text-center mt-4">

                            <a
                                href="/recuperar_password.php"
                                class="btn btn-outline-primary"
                            >
                                Solicitar un nuevo enlace
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