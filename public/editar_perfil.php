<?php
require_once("../config/app.php");
require_once("../config/database.php");
require_once("../config/image_upload.php");

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION["user_id"])) { header("Location: /login.php"); exit; }

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }

$userId=(int)$_SESSION["user_id"];
$mensaje=null; $tipo="info";
if (empty($_SESSION["csrf_editar_perfil"])) $_SESSION["csrf_editar_perfil"]=bin2hex(random_bytes(32));

$stmt=$conn->prepare("SELECT * FROM users WHERE id=:id LIMIT 1");
$stmt->execute([":id"=>$userId]);
$usuario=$stmt->fetch(PDO::FETCH_ASSOC);
if(!$usuario){ header("Location: /logout.php"); exit; }

if($_SERVER["REQUEST_METHOD"]==="POST"){
    $d=[];
    foreach(["nombre","apellido","email","telefono","fecha_nacimiento","genero","pais","provincia","municipio","calle","altura","departamento"] as $c){
        $d[$c]=trim($_POST[$c]??"");
    }

    if(!hash_equals($_SESSION["csrf_editar_perfil"],$_POST["csrf_token"]??"")){
        $mensaje="La sesión del formulario venció."; $tipo="danger";
    }elseif($d["nombre"]===""||$d["apellido"]===""||$d["email"]===""){
        $mensaje="Nombre, apellido y email son obligatorios."; $tipo="warning";
    }elseif(!filter_var($d["email"],FILTER_VALIDATE_EMAIL)){
        $mensaje="El email no es válido."; $tipo="warning";
    }elseif($d["fecha_nacimiento"]!==""&&strtotime($d["fecha_nacimiento"])>time()){
        $mensaje="La fecha de nacimiento no puede ser futura."; $tipo="warning";
    }else{
        try{
            $stmt=$conn->prepare("SELECT id FROM users WHERE LOWER(email)=LOWER(:email) AND id<>:id LIMIT 1");
            $stmt->execute([":email"=>$d["email"],":id"=>$userId]);
            if($stmt->fetch()){
                $mensaje="Ese email ya pertenece a otro usuario."; $tipo="warning";
            }else{
                $foto=$usuario["foto_perfil"]??null;
                if(!empty($_FILES["foto_perfil"]["tmp_name"])) $foto=subirImagen($_FILES["foto_perfil"],"tecnoferia/perfiles");

                $sql="UPDATE users SET nombre=:nombre,apellido=:apellido,email=:email,telefono=:telefono,
                    fecha_nacimiento=:fecha_nacimiento,genero=:genero,pais=:pais,provincia=:provincia,
                    municipio=:municipio,calle=:calle,altura=:altura,departamento=:departamento,
                    foto_perfil=:foto,updated_at=CURRENT_TIMESTAMP WHERE id=:id";
                $stmt=$conn->prepare($sql);
                $stmt->execute([
                    ":nombre"=>$d["nombre"],":apellido"=>$d["apellido"],":email"=>$d["email"],
                    ":telefono"=>$d["telefono"]?:null,":fecha_nacimiento"=>$d["fecha_nacimiento"]?:null,
                    ":genero"=>$d["genero"]?:null,":pais"=>$d["pais"]?:null,":provincia"=>$d["provincia"]?:null,
                    ":municipio"=>$d["municipio"]?:null,":calle"=>$d["calle"]?:null,":altura"=>$d["altura"]?:null,
                    ":departamento"=>$d["departamento"]?:null,":foto"=>$foto,":id"=>$userId
                ]);
                $_SESSION["user_nombre"]=$d["nombre"];
                $mensaje="Perfil actualizado correctamente."; $tipo="success";
                $stmt=$conn->prepare("SELECT * FROM users WHERE id=:id"); $stmt->execute([":id"=>$userId]);
                $usuario=$stmt->fetch(PDO::FETCH_ASSOC);
            }
        }catch(Throwable $e){ $mensaje="No se pudo actualizar el perfil."; $tipo="danger"; }
    }
}
include("../templates/header.php");
?>
<main class="container py-4">
<?php $breadcrumb=["Inicio"=>"/index.php","Mi perfil"=>"/perfil.php?id=".$userId,"Editar perfil"=>null]; require("../templates/breadcrumb.php"); ?>
<div class="row justify-content-center"><div class="col-xl-10">
<div class="d-flex justify-content-between align-items-center mb-4"><h1 class="h2 mb-0">Editar perfil</h1><a class="btn btn-outline-secondary" href="/perfil.php?id=<?=$userId?>">Volver</a></div>
<?php if($mensaje):?><div class="alert alert-<?=h($tipo)?>"><?=h($mensaje)?></div><?php endif;?>
<div class="card shadow-sm border-0"><div class="card-body p-4 p-lg-5">

<div class="d-flex align-items-center gap-3 mb-4">
<?php if(!empty($usuario["foto_perfil"])):?><img src="<?=h($usuario["foto_perfil"])?>" width="110" height="110" class="rounded-circle border" style="object-fit:cover" alt="Foto">
<?php else:?><div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:110px;height:110px;font-size:42px">👤</div><?php endif;?>
<div><h2 class="h5 mb-1"><?=h(trim(($usuario["nombre"]??"")." ".($usuario["apellido"]??"")))?></h2><p class="text-muted mb-1">@<?=h($usuario["username"]??"")?></p><span class="badge bg-primary"><?=h($usuario["rol"]??"feriante")?></span></div>
</div>

<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="csrf_token" value="<?=h($_SESSION["csrf_editar_perfil"])?>">

<h2 class="h5 border-bottom pb-2">Datos no modificables</h2>
<div class="row">
<?php foreach([["dni","DNI"],["username","Nombre de usuario"],["rol","Tipo de usuario"]] as [$n,$l]):?>
<div class="col-md-4 mb-3"><label class="form-label"><?=$l?></label><input class="form-control" value="<?=h($usuario[$n]??"")?>" disabled></div>
<?php endforeach;?>
</div>

<h2 class="h5 border-bottom pb-2 mt-4">Datos personales</h2>
<div class="row">
<div class="col-md-6 mb-3"><label class="form-label">Nombre *</label><input class="form-control" name="nombre" value="<?=h($usuario["nombre"]??"")?>" required></div>
<div class="col-md-6 mb-3"><label class="form-label">Apellido *</label><input class="form-control" name="apellido" value="<?=h($usuario["apellido"]??"")?>" required></div>
<div class="col-md-6 mb-3"><label class="form-label">Fecha de nacimiento</label><input class="form-control" type="date" name="fecha_nacimiento" value="<?=h($usuario["fecha_nacimiento"]??"")?>" max="<?=date("Y-m-d")?>"></div>
<div class="col-md-6 mb-3"><label class="form-label">Género</label><select class="form-select" name="genero"><option value="">Seleccionar</option>
<?php foreach(["Masculino","Femenino","No binario","Otro","Prefiero no informar"] as $g):?><option value="<?=h($g)?>" <?=($usuario["genero"]??"")===$g?"selected":""?>><?=h($g)?></option><?php endforeach;?></select></div>
<div class="col-12 mb-3"><label class="form-label">Cambiar foto</label><input class="form-control" type="file" name="foto_perfil" accept="image/jpeg,image/png,image/webp"></div>
</div>

<h2 class="h5 border-bottom pb-2 mt-4">Contacto</h2>
<div class="row">
<div class="col-md-6 mb-3"><label class="form-label">Email *</label><input class="form-control" type="email" name="email" value="<?=h($usuario["email"]??"")?>" required></div>
<div class="col-md-6 mb-3"><label class="form-label">Teléfono</label><input class="form-control" name="telefono" value="<?=h($usuario["telefono"]??"")?>"></div>
</div>

<h2 class="h5 border-bottom pb-2 mt-4">Dirección</h2>
<div class="row">
<?php foreach([["pais","País","4"],["provincia","Provincia","4"],["municipio","Municipio/localidad","4"],["calle","Calle","6"],["altura","Altura","3"],["departamento","Departamento/piso/unidad","3"]] as [$n,$l,$c]):?>
<div class="col-md-<?=$c?> mb-3"><label class="form-label"><?=$l?></label><input class="form-control" name="<?=$n?>" value="<?=h($usuario[$n]??"")?>"></div>
<?php endforeach;?>
</div>

<div class="d-flex justify-content-end gap-2 mt-4"><a class="btn btn-outline-secondary" href="/perfil.php?id=<?=$userId?>">Cancelar</a><button class="btn btn-primary">Guardar cambios</button></div>
</form>
</div></div></div></div>
</main>
<?php include("../templates/footer.php"); ?>