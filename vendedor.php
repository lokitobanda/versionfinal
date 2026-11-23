<?php
$base = '';
$rol_requerido = 'vendedor';
require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/config/conexion.php';

$errores = [];
$datos = [
    'titulo' => '',
    'descripcion' => '',
    'precio' => '',
    'stock' => '0',
    'categoria_id' => 0,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = [
        'titulo' => limpiar($_POST['titulo'] ?? ''),
        'descripcion' => limpiar($_POST['descripcion'] ?? ''),
        'precio' => limpiar($_POST['precio'] ?? ''),
        'stock' => limpiar($_POST['stock'] ?? '0'),
        'categoria_id' => (int) ($_POST['categoria_id'] ?? 0),
    ];

    if (!csrf_valido()) {
        $errores[] = 'La sesión del formulario venció.';
    }
    if ($datos['titulo'] === '' || mb_strlen($datos['titulo']) > 120) {
        $errores[] = 'Ingresá un título válido de hasta 120 caracteres.';
    }
    if (!is_numeric($datos['precio']) || (float) $datos['precio'] < 0) {
        $errores[] = 'Ingresá un precio válido.';
    }
    if (filter_var($datos['stock'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false) {
        $errores[] = 'Ingresá un stock válido.';
    }

    $categoria = $pdo->prepare('SELECT id FROM categorias WHERE id = :id');
    $categoria->execute([':id' => $datos['categoria_id']]);
    if (!$categoria->fetchColumn()) {
        $errores[] = 'Elegí una categoría válida.';
    }

    $imagen = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
            $errores[] = 'No se pudo subir la imagen.';
        } elseif ($_FILES['imagen']['size'] > 5 * 1024 * 1024) {
            $errores[] = 'La imagen no puede superar 5 MB.';
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['imagen']['tmp_name']);
            $formatos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!isset($formatos[$mime])) {
                $errores[] = 'La imagen debe estar en formato JPG, PNG o WebP.';
            } else {
                $nombre = bin2hex(random_bytes(16)) . '.' . $formatos[$mime];
                $destino = __DIR__ . '/uploads/' . $nombre;
                if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $destino)) {
                    $errores[] = 'No se pudo guardar la imagen.';
                } else {
                    $imagen = 'uploads/' . $nombre;
                }
            }
        }
    }

    if (!$errores) {
        $stmt = $pdo->prepare(
            'INSERT INTO productos
             (categoria_id, titulo, descripcion, precio, stock, activo, imagen, vendedor_id)
             VALUES (:categoria_id, :titulo, :descripcion, :precio, :stock, 1, :imagen, :vendedor_id)'
        );
        $stmt->execute([
            ':categoria_id' => $datos['categoria_id'],
            ':titulo' => $datos['titulo'],
            ':descripcion' => $datos['descripcion'] !== '' ? $datos['descripcion'] : null,
            ':precio' => $datos['precio'],
            ':stock' => (int) $datos['stock'],
            ':imagen' => $imagen,
            ':vendedor_id' => $_SESSION['usuario_id'],
        ]);
        $_SESSION['flash_success'] = 'Producto publicado correctamente.';
        header('Location: vendedor.php');
        exit;
    }
}

$categorias = $pdo->query('SELECT id, nombre FROM categorias ORDER BY nombre')->fetchAll();

$productosStmt = $pdo->prepare(
    'SELECT id, titulo, precio, stock, activo
     FROM productos
     WHERE vendedor_id = :vendedor_id
     ORDER BY id DESC'
);
$productosStmt->execute([':vendedor_id' => $_SESSION['usuario_id']]);
$productos = $productosStmt->fetchAll();

$ventasStmt = $pdo->prepare(
    'SELECT v.id, v.cantidad, v.total, v.fecha, p.titulo,
            u.nombre AS comprador
     FROM ventas v
     INNER JOIN productos p ON p.id = v.producto_id
     LEFT JOIN usuarios u ON u.id = v.usuario_id
     WHERE v.vendedor_id = :vendedor_id
     ORDER BY v.fecha DESC'
);
$ventasStmt->execute([':vendedor_id' => $_SESSION['usuario_id']]);
$ventas = $ventasStmt->fetchAll();

$titulo_pagina = 'Panel vendedor';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
?>

<h1>Panel vendedor</h1>

<?php if ($errores): ?>
    <div class="mensaje mensaje-error" role="alert">
        <ul><?php foreach ($errores as $error): ?><li><?= e($error); ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<h2>Publicar producto</h2>
<form method="post" action="vendedor.php" enctype="multipart/form-data" class="form-servidor">
    <?= csrf_campo(); ?>
    <label for="titulo">Título:</label><br>
    <input type="text" id="titulo" name="titulo" value="<?= e($datos['titulo']); ?>" maxlength="120" required><br><br>
    <label for="descripcion">Descripción:</label><br>
    <textarea id="descripcion" name="descripcion" rows="4"><?= e($datos['descripcion']); ?></textarea><br><br>
    <label for="categoria_id">Categoría:</label><br>
    <select id="categoria_id" name="categoria_id" required>
        <option value="">Elegí una categoría</option>
        <?php foreach ($categorias as $categoria): ?>
            <option value="<?= e($categoria['id']); ?>" <?= (int) $datos['categoria_id'] === (int) $categoria['id'] ? 'selected' : ''; ?>>
                <?= e($categoria['nombre']); ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>
    <label for="precio">Precio:</label><br>
    <input type="number" id="precio" name="precio" step="0.01" min="0" value="<?= e($datos['precio']); ?>" required><br><br>
    <label for="stock">Stock:</label><br>
    <input type="number" id="stock" name="stock" min="0" value="<?= e($datos['stock']); ?>" required><br><br>
    <label for="imagen">Foto del producto:</label><br>
    <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp"><br><br>
    <button type="submit" class="boton-registro">Publicar</button>
</form>

<h2>Mis productos</h2>
<table class="tabla-datos">
    <thead><tr><th>ID</th><th>Producto</th><th>Precio</th><th>Stock</th><th>Estado</th></tr></thead>
    <tbody>
    <?php foreach ($productos as $producto): ?>
        <tr>
            <td><?= e($producto['id']); ?></td>
            <td><?= e($producto['titulo']); ?></td>
            <td>$<?= e(number_format((float) $producto['precio'], 2)); ?></td>
            <td><?= e($producto['stock']); ?></td>
            <td><?= $producto['activo'] ? 'Activo' : 'Inactivo'; ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$productos): ?><tr><td colspan="5">Todavía no publicaste productos.</td></tr><?php endif; ?>
    </tbody>
</table>

<h2>Mis ventas</h2>
<table class="tabla-datos">
    <thead><tr><th>Fecha</th><th>Producto</th><th>Comprador</th><th>Cantidad</th><th>Total</th></tr></thead>
    <tbody>
    <?php foreach ($ventas as $venta): ?>
        <tr>
            <td><?= e($venta['fecha']); ?></td>
            <td><?= e($venta['titulo']); ?></td>
            <td><?= e($venta['comprador'] ?? 'Cuenta eliminada'); ?></td>
            <td><?= e($venta['cantidad']); ?></td>
            <td>$<?= e(number_format((float) $venta['total'], 2)); ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$ventas): ?><tr><td colspan="5">Todavía no registraste ventas.</td></tr><?php endif; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
