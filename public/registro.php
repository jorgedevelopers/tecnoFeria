<?php
require_once("../config/app.php");
require_once("../config/database.php");
require_once("../config/image_upload.php");

if (session_status() === PHP_SESSION_NONE) session_start();

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }

if (empty($_SESSION["csrf_registro"])) {
    $_SESSION["csrf_registro"] = bin2hex(random_bytes(32));
}

$mensaje = null;
$tipo = "info";
$campos = ["nombre","apellido","username","dni","email","telefono","fecha_nacimiento","genero","pais","provincia","municipio","calle","altura","departamento"];
$datos = array_fill_keys($campos, "");
$datos["pais"] = "Argentina";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    foreach ($campos as $c) $datos[$c] = trim($_POST[$c] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmar = $_POST["password_confirmacion"] ?? "";

    if (!hash_equals($_SESSION["csrf_registro"], $_POST["csrf_token"] ?? "")) {
        $mensaje = "La sesión del formulario venció. Recargá la página."; $tipo = "danger";
    } elseif (in_array("", [$datos["nombre"],$datos["apellido"],$datos["username"],$datos["dni"],$datos["email"],$password,$confirmar], true)) {
        $mensaje = "Completá todos los campos obligatorios."; $tipo = "warning";
    } elseif (!filter_var($datos["email"], FILTER_VALIDATE_EMAIL)) {
        $mensaje = "El correo electrónico no es válido."; $tipo = "warning";
    } elseif (!preg_match('/^[a-zA-Z0-9_.-]{4,30}$/', $datos["username"])) {
        $mensaje = "El usuario debe tener entre 4 y 30 caracteres válidos."; $tipo = "warning";
    } elseif (!preg_match('/^[0-9]{7,10}$/', $datos["dni"])) {
        $mensaje = "El DNI debe contener entre 7 y 10 números."; $tipo = "warning";
    } elseif (strlen($password) < 8) {
        $mensaje = "La contraseña debe tener al menos 8 caracteres."; $tipo = "warning";
    } elseif ($password !== $confirmar) {
        $mensaje = "Las contraseñas no coinciden."; $tipo = "warning";
    } elseif ($datos["fecha_nacimiento"] !== "" && strtotime($datos["fecha_nacimiento"]) > time()) {
        $mensaje = "La fecha de nacimiento no puede ser futura."; $tipo = "warning";
    } else {
        try {
            $stmt = $conn->prepare("SELECT email, username, dni FROM users
                WHERE LOWER(email)=LOWER(:email) OR LOWER(username)=LOWER(:username) OR dni=:dni LIMIT 1");
            $stmt->execute([":email"=>$datos["email"],":username"=>$datos["username"],":dni"=>$datos["dni"]]);
            $existe = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existe) {
                if (strcasecmp((string)($existe["email"] ?? ""), $datos["email"]) === 0) $mensaje = "Ese email ya está registrado.";
                elseif (strcasecmp((string)($existe["username"] ?? ""), $datos["username"]) === 0) $mensaje = "Ese nombre de usuario ya está en uso.";
                else $mensaje = "Ese DNI ya está registrado.";
                $tipo = "warning";
            } else {
                $foto = null;
                if (!empty($_FILES["foto_perfil"]["tmp_name"])) {
                    $foto = subirImagen($_FILES["foto_perfil"], "tecnoferia/perfiles");
                }

                $sql = "INSERT INTO users
                    (nombre,apellido,username,dni,email,password,telefono,fecha_nacimiento,genero,pais,provincia,municipio,calle,altura,departamento,foto_perfil,rol,activo,created_at,updated_at)
                    VALUES
                    (:nombre,:apellido,:username,:dni,:email,:password,:telefono,:fecha_nacimiento,:genero,:pais,:provincia,:municipio,:calle,:altura,:departamento,:foto,'feriante',TRUE,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ":nombre"=>$datos["nombre"],":apellido"=>$datos["apellido"],":username"=>$datos["username"],
                    ":dni"=>$datos["dni"],":email"=>$datos["email"],":password"=>password_hash($password,PASSWORD_DEFAULT),
                    ":telefono"=>$datos["telefono"] ?: null,":fecha_nacimiento"=>$datos["fecha_nacimiento"] ?: null,
                    ":genero"=>$datos["genero"] ?: null,":pais"=>$datos["pais"] ?: null,
                    ":provincia"=>$datos["provincia"] ?: null,":municipio"=>$datos["municipio"] ?: null,
                    ":calle"=>$datos["calle"] ?: null,":altura"=>$datos["altura"] ?: null,
                    ":departamento"=>$datos["departamento"] ?: null,":foto"=>$foto
                ]);
                $_SESSION["registro_success"] = "Usuario registrado correctamente. Ya podés iniciar sesión.";
                unset($_SESSION["csrf_registro"]);
                header("Location: /login.php"); exit;
            }
        } catch (Throwable $e) {
            $mensaje = "No se pudo registrar el usuario."; $tipo = "danger";
        }
    }
}
include("../templates/header.php");
?>
<main class="container py-5">
<div class="row justify-content-center"><div class="col-xl-10">
<?php $breadcrumb=["Inicio"=>"/index.php","Registro"=>null]; require("../templates/breadcrumb.php"); ?>
<div class="card shadow-sm border-0"><div class="card-body p-4 p-lg-5">
<h1 class="h2">Crear una cuenta</h1>
<p class="text-muted">Registrate para crear ferias, participar y publicar novedades.</p>
<?php if ($mensaje): ?><div class="alert alert-<?=h($tipo)?>"><?=h($mensaje)?></div><?php endif; ?>

<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="csrf_token" value="<?=h($_SESSION["csrf_registro"])?>">

<h2 class="h5 border-bottom pb-2 mt-4">Datos personales</h2>
<div class="row">
<?php
$inputs=[
["nombre","Nombre *","text","6"],["apellido","Apellido *","text","6"],["dni","DNI *","text","4"],
["fecha_nacimiento","Fecha de nacimiento","date","4"]
];
foreach($inputs as [$n,$l,$t,$c]): ?>
<div class="col-md-<?=$c?> mb-3"><label class="form-label" for="<?=$n?>"><?=$l?></label>
<input class="form-control" id="<?=$n?>" name="<?=$n?>" type="<?=$t?>" value="<?=h($datos[$n])?>" <?=$n==="fecha_nacimiento"?'max="'.date("Y-m-d").'"':''?> <?=in_array($n,["nombre","apellido","dni"],true)?"required":""?>></div>
<?php endforeach; ?>
<div class="col-md-4 mb-3"><label class="form-label">Género</label><select class="form-select" name="genero">
<option value="">Seleccionar</option>
<?php foreach(["Masculino","Femenino","No binario","Otro","Prefiero no informar"] as $g): ?>
<option value="<?=h($g)?>" <?=$datos["genero"]===$g?"selected":""?>><?=h($g)?></option>
<?php endforeach; ?></select></div>
<div class="col-12 mb-3"><label class="form-label">Foto de perfil</label>
<input class="form-control" type="file" name="foto_perfil" accept="image/jpeg,image/png,image/webp">
<div class="form-text">JPG, PNG o WEBP. Máximo 2 MB.</div></div>
</div>

<h2 class="h5 border-bottom pb-2 mt-4">Contacto</h2>
<div class="row">
<div class="col-md-6 mb-3"><label class="form-label">Email *</label><input class="form-control" type="email" name="email" value="<?=h($datos["email"])?>" required></div>
<div class="col-md-6 mb-3"><label class="form-label">Teléfono</label><input class="form-control" name="telefono" value="<?=h($datos["telefono"])?>"></div>
</div>

<h2 class="h5 border-bottom pb-2 mt-4">Dirección</h2>
<div class="row">
<?php foreach([["pais","País","4"],["provincia","Provincia","4"],["municipio","Municipio/localidad","4"],["calle","Calle","6"],["altura","Altura","3"],["departamento","Departamento/piso/unidad","3"]] as [$n,$l,$c]): ?>
<div class="col-md-<?=$c?> mb-3"><label class="form-label"><?=$l?></label><input class="form-control" name="<?=$n?>" value="<?=h($datos[$n])?>"></div>
<?php endforeach; ?>
</div>

<h2 class="h5 border-bottom pb-2 mt-4">Datos de acceso</h2>
<div class="row">
<div class="col-12 mb-3"><label class="form-label">Nombre de usuario *</label><input class="form-control" name="username" value="<?=h($datos["username"])?>" minlength="4" maxlength="30" required></div>
<div class="col-md-6 mb-3"><label class="form-label">Contraseña *</label><input class="form-control" type="password" name="password" minlength="8" required></div>
<div class="col-md-6 mb-3"><label class="form-label">Confirmar contraseña *</label><input class="form-control" type="password" name="password_confirmacion" minlength="8" required></div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
<a class="btn btn-outline-secondary" href="/login.php">Ya tengo una cuenta</a>
<button class="btn btn-primary">Registrarme</button>
</div>
</form>
</div></div></div></div>
</main>
<?php include("../templates/footer.php"); ?>
