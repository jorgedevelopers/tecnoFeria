<?php
require_once("../config/app.php");
require_once("../config/database.php");

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION["user_id"])) { header("Location: /login.php"); exit; }
if (($_SESSION["user_rol"] ?? "") !== "admin") { header("Location: /index.php"); exit; }

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }

$totalUsuarios=(int)$conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalActivos=(int)$conn->query("SELECT COUNT(*) FROM users WHERE activo=TRUE")->fetchColumn();
$totalFerias=(int)$conn->query("SELECT COUNT(*) FROM ferias")->fetchColumn();
$totalPublicaciones=(int)$conn->query("SELECT COUNT(*) FROM publicaciones")->fetchColumn();
$totalComentarios=(int)$conn->query("SELECT COUNT(*) FROM comentarios")->fetchColumn();

$q=trim($_GET["q_usuario"]??"");
$pagina=max(1,(int)($_GET["pagina_usuarios"]??1));
$limite=10;
$where="";
$params=[];
if($q!==""){
    $where="WHERE nombre ILIKE :q OR apellido ILIKE :q OR username ILIKE :q OR email ILIKE :q OR dni ILIKE :q";
    $params[":q"]="%".$q."%";
}
$stmt=$conn->prepare("SELECT COUNT(*) FROM users $where");
$stmt->execute($params);
$totalFiltrado=(int)$stmt->fetchColumn();
$totalPaginas=max(1,(int)ceil($totalFiltrado/$limite));
if($pagina>$totalPaginas)$pagina=$totalPaginas;
$offset=($pagina-1)*$limite;

$sql="SELECT id,nombre,apellido,username,dni,email,provincia,municipio,rol,activo,foto_perfil,created_at
      FROM users $where ORDER BY id ASC LIMIT :limite OFFSET :offset";
$stmt=$conn->prepare($sql);
foreach($params as $k=>$v)$stmt->bindValue($k,$v);
$stmt->bindValue(":limite",$limite,PDO::PARAM_INT);
$stmt->bindValue(":offset",$offset,PDO::PARAM_INT);
$stmt->execute();
$usuarios=$stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt=$conn->query("SELECT f.*,u.nombre AS organizador FROM ferias f LEFT JOIN users u ON f.organizador_id=u.id ORDER BY f.created_at DESC");
$ferias=$stmt->fetchAll(PDO::FETCH_ASSOC);

function paginaUrl(int $p,string $q):string{
    $a=["pagina_usuarios"=>$p];
    if($q!=="")$a["q_usuario"]=$q;
    return "/admin.php?".http_build_query($a)."#usuarios";
}
include("../templates/header.php");
?>
<main class="container py-5"><div class="row">
<?php require("../templates/sidebar.php"); ?>
<section class="col-lg-9">
<?php $breadcrumb=["Inicio"=>"/index.php","Administración"=>null]; require("../templates/breadcrumb.php"); ?>
<h1 class="mb-4">⚙️ Panel de Administración</h1>

<?php if(($_GET["usuario_eliminado"]??"")==="1"):?><div class="alert alert-success">Usuario eliminado correctamente.</div><?php endif;?>
<?php if(($_GET["eliminada"]??"")==="1"):?><div class="alert alert-success">Feria eliminada correctamente.</div><?php endif;?>
<?php if(($_GET["error"]??"")==="autoeliminar"):?><div class="alert alert-warning">No podés eliminar tu propio usuario administrador.</div><?php endif;?>

<div id="resumen" class="row g-3 mb-5">
<?php foreach([[$totalUsuarios,"Usuarios"],[$totalActivos,"Usuarios activos"],[$totalFerias,"Ferias"],[$totalPublicaciones,"Publicaciones"],[$totalComentarios,"Comentarios"]] as [$n,$l]):?>
<div class="col-md-6 col-xl"><div class="card border-0 shadow-sm h-100"><div class="card-body text-center"><div class="display-6 fw-bold"><?=$n?></div><p class="mb-0"><?=$l?></p></div></div></div>
<?php endforeach;?>
</div>

<div id="usuarios" class="card border-0 shadow-sm"><div class="card-body">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
<div><h2 class="h3 mb-1">👥 Usuarios registrados</h2><p class="text-muted mb-0"><?=$totalFiltrado?> resultado(s)</p></div>
<form method="GET" class="d-flex gap-2">
<input class="form-control" type="search" name="q_usuario" value="<?=h($q)?>" placeholder="Nombre, usuario, DNI o email">
<button class="btn btn-primary">Buscar</button>
<?php if($q!==""):?><a class="btn btn-outline-secondary" href="/admin.php#usuarios">Limpiar</a><?php endif;?>
</form>
</div>

<div class="table-responsive"><table class="table table-hover align-middle">
<thead><tr><th>Usuario</th><th>DNI</th><th>Email</th><th>Ubicación</th><th>Rol</th><th>Estado</th><th>Alta</th><th>Acciones</th></tr></thead>
<tbody>
<?php if(!$usuarios):?><tr><td colspan="8" class="text-center text-muted py-4">No se encontraron usuarios.</td></tr><?php endif;?>
<?php foreach($usuarios as $u): $nombre=trim(($u["nombre"]??"")." ".($u["apellido"]??""));?>
<tr>
<td><div class="d-flex align-items-center gap-2">
<?php if(!empty($u["foto_perfil"])):?><img src="<?=h($u["foto_perfil"])?>" width="42" height="42" class="rounded-circle border" style="object-fit:cover" alt="">
<?php else:?><div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:42px;height:42px">👤</div><?php endif;?>
<div><strong><?=h($nombre?:"Sin nombre")?></strong><div class="small text-muted">@<?=h($u["username"]??("usuario_".$u["id"]))?></div></div>
</div></td>
<td><?=h($u["dni"]?:"-")?></td>
<td><?=h($u["email"])?></td>
<td><?=h($u["municipio"]?:"-")?><?php if(!empty($u["provincia"])):?><div class="small text-muted"><?=h($u["provincia"])?></div><?php endif;?></td>
<td><?php if($u["rol"]==="admin"):?><span class="badge bg-warning text-dark">admin</span><?php else:?><span class="badge bg-primary"><?=h($u["rol"])?></span><?php endif;?></td>
<td><?php if((bool)$u["activo"]):?><span class="badge bg-success">Habilitado</span><?php else:?><span class="badge bg-danger">Deshabilitado</span><?php endif;?></td>
<td><?=!empty($u["created_at"])?h(date("d/m/Y",strtotime($u["created_at"]))):"-"?></td>
<td><div class="d-flex flex-wrap gap-2">
<a class="btn btn-sm btn-outline-primary" href="/perfil.php?id=<?=(int)$u["id"]?>">Ver</a>
<a class="btn btn-sm btn-outline-warning" href="/admin_editar_usuario.php?id=<?=(int)$u["id"]?>">Editar</a>
<?php if((int)$_SESSION["user_id"]!==(int)$u["id"]):?><a class="btn btn-sm btn-outline-danger" href="/admin_eliminar_usuario.php?id=<?=(int)$u["id"]?>" onclick="return confirm('¿Seguro que querés eliminar este usuario?')">Eliminar</a><?php endif;?>
</div></td>
</tr>
<?php endforeach;?>
</tbody></table></div>

<?php if($totalPaginas>1):?><nav><ul class="pagination justify-content-center mb-0">
<li class="page-item <?=$pagina<=1?"disabled":""?>"><a class="page-link" href="<?=h(paginaUrl($pagina-1,$q))?>">Anterior</a></li>
<?php for($p=1;$p<=$totalPaginas;$p++):?><li class="page-item <?=$p===$pagina?"active":""?>"><a class="page-link" href="<?=h(paginaUrl($p,$q))?>"><?=$p?></a></li><?php endfor;?>
<li class="page-item <?=$pagina>=$totalPaginas?"disabled":""?>"><a class="page-link" href="<?=h(paginaUrl($pagina+1,$q))?>">Siguiente</a></li>
</ul></nav><?php endif;?>
</div></div>

<div id="ferias" class="card border-0 shadow-sm mt-5"><div class="card-body">
<h2 class="h3 mb-4">🎪 Ferias registradas</h2>
<div class="table-responsive"><table class="table table-hover align-middle">
<thead><tr><th>ID</th><th>Título</th><th>Organizador</th><th>Categoría</th><th>Fecha</th><th>Ubicación</th><th>Acciones</th></tr></thead>
<tbody>
<?php foreach($ferias as $f):?><tr>
<td><?=(int)$f["id"]?></td><td><?=h($f["titulo"])?></td><td><?=h($f["organizador"]??"Sin organizador")?></td>
<td><?=!empty($f["categoria"])?'<span class="badge bg-primary">'.h($f["categoria"]).'</span>':'<span class="text-muted">Sin categoría</span>'?></td>
<td><?=h($f["fecha"])?></td><td><?=h($f["ubicacion"])?></td>
<td><div class="d-flex flex-wrap gap-2"><a class="btn btn-sm btn-outline-primary" href="/feria.php?id=<?=(int)$f["id"]?>">Ver</a><a class="btn btn-sm btn-outline-warning" href="/editar_feria.php?id=<?=(int)$f["id"]?>">Editar</a><a class="btn btn-sm btn-outline-danger" href="/eliminar_feria.php?id=<?=(int)$f["id"]?>" onclick="return confirm('¿Seguro que querés eliminar esta feria?')">Eliminar</a></div></td>
</tr><?php endforeach;?>
</tbody></table></div>
</div></div>
</section></div></main>
<?php include("../templates/footer.php"); ?>
