<?php
// Página protegida: solo rol admin
$rol_requerido = 'admin';
require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/config/conexion.php';

$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        $errores[] = 'La sesión del formulario venció.';
    } else {
        $usuarioId = filter_var($_POST['usuario_id'] ?? null, FILTER_VALIDATE_INT);
        $nuevoRol = limpiar($_POST['rol'] ?? '');
        $rolesPermitidos = ['admin', 'cliente'];

        if (!$usuarioId || !in_array($nuevoRol, $rolesPermitidos, true)) {
            $errores[] = 'Seleccioná una cuenta y un rol válidos.';
        } elseif ($usuarioId === (int) $_SESSION['usuario_id'] && $nuevoRol !== 'admin') {
            $errores[] = 'No podés quitarte el rol administrador.';
        } else {
            $stmt = $pdo->prepare(
                'UPDATE usuarios
                 SET rol = :rol, rol_id = (SELECT id FROM roles WHERE nombre = :rol_id)
                 WHERE id = :id'
            );
            $stmt->execute([':rol' => $nuevoRol, ':rol_id' => $nuevoRol, ':id' => $usuarioId]);
            $_SESSION['flash_success'] = 'Rol actualizado correctamente.';
        }
    }
}

$totales = [
    'productos' => (int) $pdo->query('SELECT COUNT(*) FROM productos')->fetchColumn(),
    'usuarios'  => (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn(),
    'consultas' => (int) $pdo->query('SELECT COUNT(*) FROM consultas')->fetchColumn(),
];
$tieneVentas = (bool) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = DATABASE() AND table_name = 'ventas'"
)->fetchColumn();
$totales['ventas'] = $tieneVentas
    ? (int) $pdo->query('SELECT COUNT(*) FROM ventas')->fetchColumn()
    : 0;

$usuarios = $pdo->query(
    'SELECT u.id, u.nombre, u.email, r.nombre AS rol
     FROM usuarios u
     INNER JOIN roles r ON r.id = u.rol_id
     ORDER BY u.id DESC'
)->fetchAll();

$ventas = [];
if ($tieneVentas) {
    $ventas = $pdo->query(
        'SELECT v.id, v.fecha, v.cantidad, v.total, p.titulo,
                cb.nombre AS comprador, vd.nombre AS vendedor
         FROM ventas v
         INNER JOIN productos p ON p.id = v.producto_id
         LEFT JOIN usuarios cb ON cb.id = v.usuario_id
         LEFT JOIN usuarios vd ON vd.id = v.vendedor_id
         ORDER BY v.fecha DESC LIMIT 50'
    )->fetchAll();
}

$consultas = $pdo->query(
    'SELECT c.id, c.nombre, c.email, c.mensaje, c.created_at, cat.nombre AS categoria, e.nombre AS estado
     FROM consultas c
     INNER JOIN categorias cat ON cat.id = c.categoria_id
     INNER JOIN estados_consulta e ON e.id = c.estado_id
     ORDER BY c.id DESC LIMIT 10'
)->fetchAll();

$titulo_pagina = 'Panel de administración';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
?>

<h1>Panel de administración</h1>

<?php if ($errores): ?>
    <div class="mensaje mensaje-error" role="alert">
        <ul><?php foreach ($errores as $error): ?><li><?= e($error); ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<ul>
    <li>Productos: <strong><?= e($totales['productos']); ?></strong> — <a href="admin/index.php">Gestionar productos (CRUD)</a></li>
    <li>Usuarios: <strong><?= e($totales['usuarios']); ?></strong></li>
    <li>Consultas recibidas: <strong><?= e($totales['consultas']); ?></strong></li>
    <li>Ventas registradas: <strong><?= e($totales['ventas']); ?></strong></li>
</ul>

<h2>Cuentas y roles</h2>
<table class="tabla-datos">
    <thead><tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol actual</th><th>Cambiar rol</th></tr></thead>
    <tbody>
    <?php foreach ($usuarios as $usuario): ?>
        <tr>
            <td><?= e($usuario['id']); ?></td>
            <td><?= e($usuario['nombre']); ?></td>
            <td><?= e($usuario['email']); ?></td>
            <td><?= e($usuario['rol']); ?></td>
            <td>
                <form method="post" action="admin_panel.php" class="form-servidor">
                    <?= csrf_campo(); ?>
                    <input type="hidden" name="usuario_id" value="<?= e($usuario['id']); ?>">
                    <select name="rol" aria-label="Nuevo rol">
                        <?php foreach (['cliente', 'admin'] as $rol): ?>
                            <option value="<?= e($rol); ?>" <?= $usuario['rol'] === $rol ? 'selected' : ''; ?>><?= e(ucfirst($rol)); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="boton-registro">Guardar</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<h2>Historial global de ventas</h2>
<?php if (!$ventas): ?>
    <p>Todavía no hay ventas registradas.</p>
<?php else: ?>
    <table class="tabla-datos">
        <thead><tr><th>Fecha</th><th>Producto</th><th>Comprador</th><th>Vendedor</th><th>Cantidad</th><th>Total</th></tr></thead>
        <tbody>
        <?php foreach ($ventas as $venta): ?>
            <tr>
                <td><?= e($venta['fecha']); ?></td>
                <td><?= e($venta['titulo']); ?></td>
                <td><?= e($venta['comprador'] ?? 'Cuenta eliminada'); ?></td>
                <td><?= e($venta['vendedor'] ?? 'Administración'); ?></td>
                <td><?= e($venta['cantidad']); ?></td>
                <td>$<?= e(number_format((float) $venta['total'], 2)); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h2>Últimas consultas</h2>
<?php if (!$consultas): ?>
    <p>Todavía no hay consultas.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <thead>
            <tr><th>ID</th><th>Fecha</th><th>Nombre</th><th>Email</th><th>Categoría</th><th>Estado</th><th>Mensaje</th></tr>
        </thead>
        <tbody>
        <?php foreach ($consultas as $c): ?>
            <tr>
                <td><?= e($c['id']); ?></td>
                <td><?= e($c['created_at']); ?></td>
                <td><?= e($c['nombre']); ?></td>
                <td><?= e($c['email']); ?></td>
                <td><?= e($c['categoria']); ?></td>
                <td><?= e($c['estado']); ?></td>
                <td><?= e($c['mensaje']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
