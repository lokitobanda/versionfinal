<?php
// Página protegida: cualquier usuario autenticado
require_once __DIR__ . '/includes/auth_guard.php';

$titulo_pagina = 'Mi cuenta';
$mensaje_exito = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
?>

<h1>Hola, <?= e($_SESSION['usuario_nombre']); ?></h1>

<?php if ($mensaje_exito !== ''): ?>
    <div class="mensaje mensaje-info" role="status"><?= e($mensaje_exito); ?></div>
<?php endif; ?>

<?php if (($_GET['error'] ?? '') === 'sin_permiso'): ?>
    <div class="mensaje mensaje-error" role="alert">No tenés permisos para acceder a esa sección.</div>
<?php endif; ?>

<p>Rol: <strong><?= e($_SESSION['rol']); ?></strong></p>

<ul>
    <?php if ($_SESSION['rol'] === 'cliente'): ?>
        <li><a href="clase5/contacto.php">Enviar una consulta</a></li>
    <?php endif; ?>
    <li><a href="catalogo.php">Ver catálogo y comprar</a></li>
    <?php if ($_SESSION['rol'] === 'admin'): ?>
        <li><a href="admin/index.php">Añadir catálogos</a></li>
        <li><a href="admin/usuarios.php">Usuarios</a></li>
        <li><a href="admin_panel.php">Panel de administración</a></li>
    <?php endif; ?>
    <li><a href="logout.php">Cerrar sesión</a></li>
</ul>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
