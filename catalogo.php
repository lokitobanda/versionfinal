<?php
require_once __DIR__ . '/includes/funciones.php';
require_once __DIR__ . '/config/conexion.php';
iniciar_sesion_segura();

$errores = [];
$categoria = limpiar($_GET['categoria'] ?? '');
$nombre = limpiar($_GET['q'] ?? '');
$rol = $_SESSION['rol'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        $errores[] = 'La sesión del formulario venció.';
    } elseif (empty($_SESSION['usuario_id'])) {
        header('Location: login.php?error=acceso_denegado');
        exit;
    } elseif ($rol !== 'cliente') {
        $errores[] = 'Solo las cuentas cliente pueden comprar.';
    } else {
        $productoId = filter_var($_POST['producto_id'] ?? null, FILTER_VALIDATE_INT);
        $cantidad = filter_var($_POST['cantidad'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 99],
        ]);

        if (!$productoId || $cantidad === false) {
            $errores[] = 'Cantidad inválida.';
        } else {
            try {
                $pdo->beginTransaction();
                $select = $pdo->prepare(
                    'SELECT id, precio, stock, vendedor_id
                     FROM productos
                     WHERE id = :id AND activo = 1
                     FOR UPDATE'
                );
                $select->execute([':id' => $productoId]);
                $producto = $select->fetch();

                if (!$producto || (int) $producto['stock'] < $cantidad) {
                    throw new DomainException('No hay stock suficiente.');
                }

                $update = $pdo->prepare(
                    'UPDATE productos
                     SET stock = stock - :cantidad
                     WHERE id = :id AND activo = 1 AND stock >= :stock'
                );
                $update->execute([
                    ':cantidad' => $cantidad,
                    ':id' => $productoId,
                    ':stock' => $cantidad,
                ]);
                if ($update->rowCount() !== 1) {
                    throw new DomainException('El stock cambió.');
                }

                $total = (float) $producto['precio'] * $cantidad;
                $insert = $pdo->prepare(
                    'INSERT INTO ventas (usuario_id, cliente_id, producto_id, cantidad, precio_unitario, total)
                     VALUES (:usuario, :cliente, :producto, :cantidad, :precio, :total)'
                );
                $precio = number_format((float) $producto['precio'], 2, '.', '');
                $insert->execute([
                    ':usuario' => (int) $_SESSION['usuario_id'],
                    ':cliente' => (int) $_SESSION['usuario_id'],
                    ':producto' => $productoId,
                    ':cantidad' => $cantidad,
                    ':precio' => $precio,
                    ':total' => number_format($total, 2, '.', ''),
                ]);
                $pdo->commit();
                header('Location: catalogo.php?status=compra');
                exit;
            } catch (DomainException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errores[] = $e->getMessage();
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errores[] = 'No se pudo completar la compra.';
            }
        }
    }
}

$catStmt = $pdo->prepare('SELECT id, nombre FROM categorias ORDER BY nombre');
$catStmt->execute();
$categorias = $catStmt->fetchAll();

$columnaStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = :tabla AND column_name = :columna"
);
$columnaStmt->execute([':tabla' => 'productos', ':columna' => 'imagen']);
$tieneImagen = (bool) $columnaStmt->fetchColumn();
$columnaStmt->execute([':tabla' => 'productos', ':columna' => 'vendedor_id']);
$tieneVendedor = (bool) $columnaStmt->fetchColumn();

$sql = 'SELECT p.id, p.titulo, p.descripcion, p.precio, p.stock,
               ' . ($tieneImagen ? 'p.imagen' : 'NULL AS imagen') . ',
               c.nombre AS categoria,
               ' . ($tieneVendedor ? 'u.nombre AS vendedor' : 'NULL AS vendedor') . '
        FROM productos p
        INNER JOIN categorias c ON c.id = p.categoria_id';
$sql .= $tieneVendedor
    ? ' LEFT JOIN usuarios u ON u.id = p.vendedor_id'
    : ' LEFT JOIN (SELECT NULL AS id, NULL AS nombre) u ON 1 = 0';
$sql .= ' WHERE p.activo = 1 AND p.stock > 0';
$params = [];

if ($categoria !== '' && ctype_digit($categoria)) {
    $sql .= ' AND p.categoria_id = :categoria';
    $params[':categoria'] = (int) $categoria;
}
if ($nombre !== '') {
    $sql .= ' AND LOWER(p.titulo) LIKE :nombre';
    $params[':nombre'] = '%' . mb_strtolower($nombre, 'UTF-8') . '%';
}
$sql .= ' ORDER BY p.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll();

$titulo_pagina = 'Catálogo';
$catalogo_action = $catalogo_action ?? 'catalogo.php';
require_once __DIR__ . '/includes/header.php';
?>
<section class="mb-4">
    <h1>Catálogo SkyGames</h1>
    <p>Explorá videojuegos, consolas, accesorios y periféricos.</p>
</section>

<form method="get" action="<?= htmlspecialchars($catalogo_action, ENT_QUOTES, 'UTF-8'); ?>" class="catalog-controls row g-2 mb-4">
    <div class="col-md-5">
        <label class="form-label" for="categoria">Catálogo</label>
        <select class="form-select" id="categoria" name="categoria">
            <option value="">Todos los catálogos</option>
            <?php foreach ($categorias as $c): ?>
                <option value="<?= htmlspecialchars($c['id'], ENT_QUOTES, 'UTF-8'); ?>" <?= $categoria === (string) $c['id'] ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($c['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-5">
        <label class="form-label" for="q">Buscar por nombre</label>
        <input class="form-control" type="text" id="q" name="q" maxlength="60"
               value="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?>">
    </div>
    <div class="col-md-2 d-flex align-items-end">
        <button class="btn btn-primary w-100" type="submit">Buscar</button>
    </div>
</form>

<?php if ($errores): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errores as $error): ?>
                <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!$productos): ?>
    <div class="alert alert-info">No se encontraron productos.</div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($productos as $p): ?>
            <div class="col-sm-6 col-lg-4">
                <article class="card product-card shadow-sm">
                    <?php if ($p['imagen']): ?>
                        <img class="product-image card-img-top"
                             src="<?= htmlspecialchars($p['imagen'], ENT_QUOTES, 'UTF-8'); ?>"
                             alt="<?= htmlspecialchars($p['titulo'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php else: ?>
                        <div class="product-placeholder">SkyGames</div>
                    <?php endif; ?>
                    <div class="card-body d-flex flex-column">
                        <span class="badge text-bg-light align-self-start">
                            <?= htmlspecialchars($p['categoria'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                        <h2 class="h5 mt-2"><?= htmlspecialchars($p['titulo'], ENT_QUOTES, 'UTF-8'); ?></h2>
                        <p class="text-secondary flex-grow-1">
                            <?= nl2br(htmlspecialchars($p['descripcion'] ?? '', ENT_QUOTES, 'UTF-8')); ?>
                        </p>
                        <p class="h4 text-primary">$<?= htmlspecialchars(number_format((float) $p['precio'], 2), ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="small">
                            Stock: <?= htmlspecialchars($p['stock'], ENT_QUOTES, 'UTF-8'); ?>
                            <?php if ($p['vendedor']): ?>
                                · <?= htmlspecialchars($p['vendedor'], ENT_QUOTES, 'UTF-8'); ?>
                            <?php endif; ?>
                        </p>
                        <?php if ($rol === 'cliente'): ?>
                            <form method="post">
                                <?= csrf_campo(); ?>
                                <input type="hidden" name="producto_id"
                                       value="<?= htmlspecialchars($p['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="input-group">
                                    <input class="form-control" type="number" name="cantidad" value="1"
                                           min="1" max="<?= htmlspecialchars(min(99, (int) $p['stock']), ENT_QUOTES, 'UTF-8'); ?>" required>
                                    <button class="btn btn-primary" type="submit">Comprar</button>
                                </div>
                            </form>
                        <?php elseif (empty($_SESSION['usuario_id'])): ?>
                            <a class="btn btn-outline-primary" href="login.php">Ingresá para comprar</a>
                        <?php endif; ?>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
