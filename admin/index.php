<?php
$base = '../';
$rol_requerido = 'admin';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/conexion.php';

$stmt = $pdo->query(
    'SELECT id, titulo, precio, descripcion
     FROM productos
     ORDER BY id DESC'
);
$productos = $stmt->fetchAll();

$mensajes = [
    'created' => 'Producto agregado correctamente.',
    'updated' => 'Producto actualizado correctamente.',
    'deleted' => 'Producto eliminado correctamente.',
];
$status = isset($_GET['status']) && is_string($_GET['status']) ? $_GET['status'] : '';
$mensaje = $mensajes[$status] ?? '';
$error = $_SESSION['admin_error'] ?? '';
unset($_SESSION['admin_error']);

$titulo_pagina = 'Añadir catálogos';
require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="mb-3">Añadir catálogos</h1>
<p>Gestioná los productos que aparecen en el catálogo de SkyGames.</p>

<?php if ($mensaje !== ''): ?>
    <div class="alert alert-success" role="status"><?= e($mensaje); ?></div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger" role="alert"><?= e($error); ?></div>
<?php endif; ?>

<p>
    <a class="btn btn-primary" href="crear.php">+ Añadir producto</a>
</p>

<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th scope="col">Nombre de producto</th>
                <th scope="col">Nivel de precio</th>
                <th scope="col">Descripción</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$productos): ?>
                <tr>
                    <td colspan="3">Todavía no hay productos en el catálogo.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($productos as $producto): ?>
                <tr>
                    <td>
                        <div><?= e($producto['titulo']); ?></div>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <a class="btn btn-sm btn-outline-primary"
                               href="editar.php?id=<?= e($producto['id']); ?>">Editar</a>
                            <form method="post" action="eliminar.php"
                                  onsubmit="return confirm('¿Eliminar este producto?')">
                                <?= csrf_campo(); ?>
                                <input type="hidden" name="id" value="<?= e($producto['id']); ?>">
                                <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                            </form>
                        </div>
                    </td>
                    <td>$<?= e(number_format((float) $producto['precio'], 2)); ?></td>
                    <td><?= e($producto['descripcion'] ?? ''); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
