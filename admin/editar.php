<?php
$base='../'; $rol_requerido='admin';
require_once __DIR__.'/../includes/auth_guard.php';
require_once __DIR__.'/../config/conexion.php';
$idEntrada=$_GET['id'] ?? null;
$id=filter_var(is_string($idEntrada) ? $idEntrada : null,FILTER_VALIDATE_INT);
if(!$id){header('Location:index.php');exit;}
$stmt=$pdo->prepare('SELECT * FROM productos WHERE id=:id');$stmt->execute([':id'=>$id]);$producto=$stmt->fetch();
if(!$producto){header('Location:index.php');exit;}
$datos=['titulo'=>$producto['titulo'],'descripcion'=>$producto['descripcion']??'','precio'=>$producto['precio'],'stock'=>$producto['stock'],'categoria_id'=>$producto['categoria_id'],'activo'=>(int)$producto['activo']];
$errores=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
 $datos=['titulo'=>limpiar($_POST['titulo']??''),'descripcion'=>limpiar($_POST['descripcion']??''),'precio'=>limpiar($_POST['precio']??''),'stock'=>limpiar($_POST['stock']??'0'),'categoria_id'=>limpiar($_POST['categoria_id']??''),'activo'=>isset($_POST['activo'])?1:0];
 if(!csrf_valido())$errores[]='La sesión venció.';
 if($datos['titulo']===''||mb_strlen($datos['titulo'])>120)$errores[]='El nombre del producto es obligatorio (máximo 120 caracteres).';
 if(mb_strlen($datos['descripcion'])>5000)$errores[]='La descripción no puede superar los 5000 caracteres.';
 if(!is_numeric($datos['precio'])||(float)$datos['precio']<0||(float)$datos['precio']>99999999.99)$errores[]='El nivel de precio debe estar entre 0 y 99.999.999,99.';
 if(filter_var($datos['stock'],FILTER_VALIDATE_INT,['options'=>['min_range'=>0]])===false)$errores[]='Stock inválido.';
 $categoria=$pdo->prepare('SELECT id FROM categorias WHERE id=:id');$categoria->execute([':id'=>$datos['categoria_id']]);if(!$categoria->fetch())$errores[]='Categoría inválida.';
 $imagen=$producto['imagen'];
 if(isset($_FILES['imagen'])&&$_FILES['imagen']['error']!==UPLOAD_ERR_NO_FILE){
  $tipos=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];$mime=(new finfo(FILEINFO_MIME_TYPE))->file($_FILES['imagen']['tmp_name']);
  if($_FILES['imagen']['error']!==UPLOAD_ERR_OK||$_FILES['imagen']['size']>5*1024*1024||!isset($tipos[$mime]))$errores[]='Imagen inválida.';
  else{$nombre=bin2hex(random_bytes(16)).'.'.$tipos[$mime];if(move_uploaded_file($_FILES['imagen']['tmp_name'],__DIR__.'/../uploads/'.$nombre))$imagen='uploads/'.$nombre;else$errores[]='No se pudo guardar la imagen.';}
 }
 if(!$errores){
  try{
   $update=$pdo->prepare('UPDATE productos SET categoria_id=:categoria,titulo=:titulo,descripcion=:descripcion,precio=:precio,stock=:stock,activo=:activo,imagen=:imagen WHERE id=:id');
   $update->execute([':categoria'=>$datos['categoria_id'],':titulo'=>$datos['titulo'],':descripcion'=>$datos['descripcion']!==''?$datos['descripcion']:null,':precio'=>$datos['precio'],':stock'=>(int)$datos['stock'],':activo'=>$datos['activo'],':imagen'=>$imagen,':id'=>$id]);
   header('Location:index.php?status=updated');exit;
  }catch(PDOException $e){
   error_log('No se pudo actualizar el producto ' . $id . ': ' . $e->getMessage());
   $errores[]='No se pudo actualizar el producto.';
  }
 }
}
$cat=$pdo->prepare('SELECT id,nombre FROM categorias ORDER BY nombre');$cat->execute();$categorias=$cat->fetchAll();
$titulo_pagina='Editar publicación';require_once __DIR__.'/../includes/header.php';
?>
<h1>Editar producto</h1><?php if($errores):?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($errores as $e):?><li><?=htmlspecialchars($e,ENT_QUOTES,'UTF-8');?></li><?php endforeach;?></ul></div><?php endif;?>
<form method="post" enctype="multipart/form-data"><?=csrf_campo();?>
<div class="mb-3"><label class="form-label" for="titulo">Nombre de producto</label><input class="form-control" id="titulo" name="titulo" maxlength="120" value="<?=htmlspecialchars($datos['titulo'],ENT_QUOTES,'UTF-8');?>" required></div>
<div class="mb-3"><label class="form-label" for="descripcion">Descripción</label><textarea class="form-control" id="descripcion" name="descripcion" maxlength="5000" rows="4"><?=htmlspecialchars($datos['descripcion'],ENT_QUOTES,'UTF-8');?></textarea></div>
<div class="mb-3"><label class="form-label" for="categoria_id">Categoría</label><select class="form-select" id="categoria_id" name="categoria_id" required><?php foreach($categorias as $c):?><option value="<?=htmlspecialchars($c['id'],ENT_QUOTES,'UTF-8');?>" <?=(int)$datos['categoria_id']===(int)$c['id']?'selected':''?>><?=htmlspecialchars($c['nombre'],ENT_QUOTES,'UTF-8');?></option><?php endforeach;?></select></div>
<div class="row"><div class="col-md-6 mb-3"><label class="form-label" for="precio">Nivel de precio</label><input class="form-control" type="number" id="precio" name="precio" step="0.01" min="0" max="99999999.99" value="<?=htmlspecialchars($datos['precio'],ENT_QUOTES,'UTF-8');?>"></div><div class="col-md-6 mb-3"><label class="form-label" for="stock">Stock</label><input class="form-control" type="number" id="stock" name="stock" min="0" value="<?=htmlspecialchars($datos['stock'],ENT_QUOTES,'UTF-8');?>"></div></div>
<div class="mb-3"><label class="form-label" for="imagen">Nueva foto</label><input class="form-control" type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp"></div>
<div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="activo" name="activo" <?=$datos['activo']?'checked':''?>><label class="form-check-label" for="activo">Activo</label></div>
<button class="btn btn-primary" type="submit">Guardar cambios</button> <a class="btn btn-secondary" href="index.php">Cancelar</a>
</form><?php require_once __DIR__.'/../includes/footer.php'; ?>
