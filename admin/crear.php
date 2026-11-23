<?php
$base = '../';
$rol_requerido = 'admin';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/conexion.php';

$datos = ['titulo' => '', 'descripcion' => '', 'precio' => ''];
$errores = [];

$categoriaStmt = $pdo->prepare('SELECT id FROM categorias WHERE nombre = :nombre');
$categoriaStmt->execute([':nombre' => 'Videojuegos']);
$categoriaId = $categoriaStmt->fetchColumn();

if (!$categoriaId) {
    $errores[] = 'No existe la categoría Videojuegos para asignar el producto.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = [
        'titulo' => limpiar($_POST['titulo'] ?? ''),
        'descripcion' => limpiar($_POST['descripcion'] ?? ''),
        'precio' => limpiar($_POST['precio'] ?? ''),
    ];

    if (!csrf_valido()) {
        $errores[] = 'La sesión del formulario venció.';
    }
    if ($datos['titulo'] === '' || mb_strlen($datos['titulo']) > 120) {
        $errores[] = 'El nombre del producto es obligatorio (máximo 120 caracteres).';
    }
    if (mb_strlen($datos['descripcion']) > 5000) {
        $errores[] = 'La descripción no puede superar los 5000 caracteres.';
    }
    if (
        !is_numeric($datos['precio'])
        || (float) $datos['precio'] < 0
        || (float) $datos['precio'] > 99999999.99
    ) {
        $errores[] = 'El nivel de precio debe estar entre 0 y 99.999.999,99.';
    }

    if (!$errores) {
        try {
            $insert = $pdo->prepare(
                'INSERT INTO productos (categoria_id, titulo, descripcion, precio, stock, activo, imagen, vendedor_id)
                 VALUES (:categoria, :titulo, :descripcion, :precio, 1, 1, NULL, NULL)'
            );
            $insert->execute([
                ':categoria' => $categoriaId,
                ':titulo' => $datos['titulo'],
                ':descripcion' => $datos['descripcion'] !== '' ? $datos['descripcion'] : null,
                ':precio' => $datos['precio'],
            ]);
            header('Location: index.php?status=created');
            exit;
        } catch (PDOException $e) {
            error_log('No se pudo crear el producto: ' . $e->getMessage());
            $errores[] = 'No se pudo guardar el producto.';
        }
    }
}

$titulo_pagina = 'Añadir producto';
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Añadir producto</h1>
<p>El producto se agregará al catálogo de videojuegos con stock inicial de 1.</p>

<?php if ($errores): ?>
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0">
            <?php foreach ($errores as $error): ?>
                <li><?= e($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" class="form-servidor" novalidate>
    <?= csrf_campo(); ?>

    <div class="mb-3">
        <label class="form-label" for="titulo">Nombre de producto</label>
        <input class="form-control" id="titulo" name="titulo" maxlength="120"
               value="<?= e($datos['titulo']); ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label" for="precio">Nivel de precio</label>
        <input class="form-control" type="number" id="precio" name="precio"
               step="0.01" min="0" max="99999999.99"
               value="<?= e($datos['precio']); ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label" for="descripcion">Descripción</label>
        <textarea class="form-control" id="descripcion" name="descripcion"
                  maxlength="5000" rows="4"><?= e($datos['descripcion']); ?></textarea>
    </div>

    <button class="btn btn-primary" type="submit">Guardar producto</button>
    <a class="btn btn-secondary" href="index.php">Cancelar</a>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
