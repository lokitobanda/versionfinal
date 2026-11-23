<?php
$base = '../';
$rol_requerido = 'admin';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

if (!csrf_valido()) {
    http_response_code(403);
    exit('La sesión del formulario venció. Volvé a intentarlo.');
}

$idEntrada = $_POST['id'] ?? null;
$id = filter_var(is_string($idEntrada) ? $idEntrada : null, FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    exit('Producto inválido.');
}

$select = $pdo->prepare('SELECT imagen FROM productos WHERE id = :id');
$select->execute([':id' => $id]);
$producto = $select->fetch();

if (!$producto) {
    $_SESSION['admin_error'] = 'El producto que intentaste eliminar ya no existe.';
    header('Location: index.php');
    exit;
}

try {
    $delete = $pdo->prepare('DELETE FROM productos WHERE id = :id');
    $delete->execute([':id' => $id]);
} catch (PDOException $e) {
    error_log('No se pudo eliminar el producto ' . $id . ': ' . $e->getMessage());
    $_SESSION['admin_error'] = 'No se pudo eliminar el producto. Puede tener ventas asociadas.';
    header('Location: index.php');
    exit;
}

if (is_string($producto['imagen']) && strpos($producto['imagen'], 'uploads/') === 0) {
    $rutaImagen = __DIR__ . '/../uploads/' . basename($producto['imagen']);
    if (is_file($rutaImagen) && !unlink($rutaImagen)) {
        $_SESSION['admin_error'] = 'El producto se eliminó, pero no se pudo borrar su imagen.';
    }
}

header('Location: index.php?status=deleted');
exit;
