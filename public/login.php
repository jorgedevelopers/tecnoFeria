<?php
require_once("../config/app.php");
require_once("../config/database.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    try {

        $sql = "SELECT *
                FROM users
                WHERE email = :email
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user["password"])) {

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["user_nombre"] = $user["nombre"];
            $_SESSION["user_rol"] = $user["rol"];

            header("Location: /index.php");
            exit;

        } else {

            $mensaje = "❌ Email o contraseña incorrectos";

        }

    } catch (PDOException $e) {

        $mensaje = "❌ Error al iniciar sesión";

    }
}
?>

<?php include("../templates/header.php"); ?>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-6">

            <div class="card shadow-sm">

                <div class="card-body">

                    <h2 class="mb-4">
                        Iniciar sesión
                    </h2>
<?php if (isset($mensaje)) : ?>

    <div class="alert alert-danger">
        <?= $mensaje ?>
    </div>

<?php endif; ?>

<?php if (isset($_SESSION["password_reset_success"])) : ?>

    <div class="alert alert-success">
        <?= htmlspecialchars(
            $_SESSION["password_reset_success"],
            ENT_QUOTES,
            "UTF-8"
        ) ?>
    </div>

    <?php unset($_SESSION["password_reset_success"]); ?>

<?php endif; ?>
<?php if (isset($_SESSION["registro_success"])) : ?>

    <div class="alert alert-success">
        <?= htmlspecialchars(
            $_SESSION["registro_success"],
            ENT_QUOTES,
            "UTF-8"
        ) ?>
    </div>

    <?php unset($_SESSION["registro_success"]); ?>

<?php endif; ?>

<form method="POST">

                        <div class="mb-3">

                            <label class="form-label">
                                Email
                            </label>

                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                required
                            >

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Contraseña
                            </label>

                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                required
                            >

                        </div>

<button
    type="submit"
    class="btn btn-primary w-100"
>
    Ingresar
</button>

<div class="text-center mt-3">

    <a
        href="/recuperar_password.php"
        class="text-decoration-none"
    >
        ¿Olvidaste tu contraseña?
    </a>

</div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

<?php include("../templates/footer.php"); ?>