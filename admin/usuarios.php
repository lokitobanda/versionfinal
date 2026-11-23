<?php
$base='../'; $rol_requerido='admin';
require_once __DIR__.'/../includes/auth_guard.php';
require_once __DIR__.'/../config/conexion.php';
$errores=[];
$datos=['nombre'=>'','email'=>'','password'=>'','rol'=>'cliente'];

if($_SERVER['REQUEST_METHOD']==='POST'){
 $accion=limpiar($_POST['accion']??'');
 if(!csrf_valido())$errores[]='La sesión venció.';
 elseif($accion==='crear'){
  $datos=['nombre'=>limpiar($_POST['nombre']??''),'email'=>strtolower(limpiar($_POST['email']??'')),'password'=>is_string($_POST['password']??null)?$_POST['password']:'','rol'=>limpiar($_POST['rol']??'')];
  if($datos['nombre']===''||mb_strlen($datos['nombre'])>100)$errores[]='Nombre inválido.';
  if(!filter_var($datos['email'],FILTER_VALIDATE_EMAIL))$errores[]='Correo inválido.';
  if(strlen($datos['password'])<8)$errores[]='La contraseña debe tener al menos 8 caracteres.';
  if(!in_array($datos['rol'],['admin','cliente'],true))$errores[]='Rol inválido.';
  if(!$errores){
   $existe=$pdo->prepare('SELECT id FROM usuarios WHERE email=:email');$existe->execute([':email'=>$datos['email']]);
   if($existe->fetch())$errores[]='Ese correo ya existe.';
   else{$insert=$pdo->prepare('INSERT INTO usuarios(nombre,email,password,rol,rol_id) VALUES(:nombre,:email,:password,:rol,(SELECT id FROM roles WHERE nombre=:rol_id))');
    $insert->execute([':nombre'=>$datos['nombre'],':email'=>$datos['email'],':password'=>password_hash($datos['password'],PASSWORD_DEFAULT),':rol'=>$datos['rol'],':rol_id'=>$datos['rol']]);header('Location:usuarios.php?status=created');exit;}
  }
 }elseif($accion==='editar'){
  $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT);
  $nombre=limpiar($_POST['nombre']??'');$email=strtolower(limpiar($_POST['email']??''));$rol=limpiar($_POST['rol']??'');
  if(!$id||$nombre===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||!in_array($rol,['admin','cliente'],true))$errores[]='Datos de usuario inválidos.';
  elseif($id==(int)$_SESSION['usuario_id']&&$rol!==$_SESSION['rol'])$errores[]='No podés cambiar tu propio rol.';
  else{$update=$pdo->prepare('UPDATE usuarios SET nombre=:nombre,email=:email,rol=:rol,rol_id=(SELECT id FROM roles WHERE nombre=:rol_nombre) WHERE id=:id');
   try{$update->execute([':nombre'=>$nombre,':email'=>$email,':rol'=>$rol,':rol_nombre'=>$rol,':id'=>$id]);header('Location:usuarios.php?status=updated');exit;}catch(PDOException $e){$errores[]='No se pudo actualizar la cuenta.';}}
 }elseif($accion==='eliminar'){
  $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT);
  if(!$id)$errores[]='Usuario inválido.';
  elseif($id==(int)$_SESSION['usuario_id'])$errores[]='No podés eliminar tu propia cuenta.';
  else{$delete=$pdo->prepare('DELETE FROM usuarios WHERE id=:id');
   try{$delete->execute([':id'=>$id]);header('Location:usuarios.php?status=deleted');exit;}catch(PDOException $e){$errores[]='El usuario tiene relaciones que impiden eliminarlo.';}}
 }
}
$stmt=$pdo->prepare('SELECT u.id,u.nombre,u.email,r.nombre AS rol,u.created_at FROM usuarios u INNER JOIN roles r ON r.id=u.rol_id ORDER BY u.id DESC');$stmt->execute();$usuarios=$stmt->fetchAll();
$titulo_pagina='Usuarios';require_once __DIR__.'/../includes/header.php';
?>
<h1>CRUD de usuarios</h1>
<?php if($errores):?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($errores as $e):?><li><?=htmlspecialchars($e,ENT_QUOTES,'UTF-8');?></li><?php endforeach;?></ul></div><?php endif;?>
<h2>Crear cuenta</h2>
<form method="post" class="row g-3 mb-5"><?=csrf_campo();?><input type="hidden" name="accion" value="crear">
<div class="col-md-4"><label class="form-label" for="nombre">Nombre</label><input class="form-control" id="nombre" name="nombre" value="<?=htmlspecialchars($datos['nombre'],ENT_QUOTES,'UTF-8');?>" required></div>
<div class="col-md-4"><label class="form-label" for="email">Correo</label><input class="form-control" type="email" id="email" name="email" value="<?=htmlspecialchars($datos['email'],ENT_QUOTES,'UTF-8');?>" required></div>
<div class="col-md-4"><label class="form-label" for="password">Contraseña</label><input class="form-control" type="password" id="password" name="password" minlength="8" required></div>
<div class="col-md-4"><label class="form-label" for="rol">Rol</label><select class="form-select" id="rol" name="rol"><option value="cliente">Cliente</option><option value="admin">Administrador</option></select></div>
<div class="col-12"><button class="btn btn-primary" type="submit">Crear cuenta</button></div></form>

<h2>Listado</h2><div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Acciones</th></tr></thead><tbody>
<?php foreach($usuarios as $u):?>
<tr><form method="post"><?=csrf_campo();?><input type="hidden" name="accion" value="editar"><input type="hidden" name="id" value="<?=htmlspecialchars($u['id'],ENT_QUOTES,'UTF-8');?>">
<td><?=htmlspecialchars($u['id'],ENT_QUOTES,'UTF-8');?></td>
<td><input class="form-control form-control-sm" name="nombre" value="<?=htmlspecialchars($u['nombre'],ENT_QUOTES,'UTF-8');?>"></td>
<td><input class="form-control form-control-sm" type="email" name="email" value="<?=htmlspecialchars($u['email'],ENT_QUOTES,'UTF-8');?>"></td>
<td><select class="form-select form-select-sm" name="rol"><?php foreach(['cliente','admin'] as $rol):?><option value="<?=$rol?>" <?=$u['rol']===$rol?'selected':''?>><?=htmlspecialchars(ucfirst($rol),ENT_QUOTES,'UTF-8');?></option><?php endforeach;?></select></td>
<td><button class="btn btn-sm btn-outline-primary" type="submit">Guardar</button></form>
<form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar esta cuenta?')"><?=csrf_campo();?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?=htmlspecialchars($u['id'],ENT_QUOTES,'UTF-8');?>"><button class="btn btn-sm btn-danger" type="submit">Eliminar</button></form></td></tr>
<?php endforeach;?>
</tbody></table></div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
