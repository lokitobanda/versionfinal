<?php
/**
 * Código común del CRUD de productos (solo admin).
 */
$base = '../';
$rol_requerido = 'admin';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/conexion.php';

/** Valida los datos del formulario de producto. Devuelve [datos, errores]. */
function validar_producto(PDO $pdo, array $in): array
{
    $datos = [
        'titulo'       => limpiar($in['titulo'] ?? ''),
        'descripcion'  => limpiar($in['descripcion'] ?? ''),
        'precio'       => limpiar($in['precio'] ?? ''),
        'stock'        => limpiar($in['stock'] ?? '0'),
        'categoria_id' => (int) ($in['categoria_id'] ?? 0),
        'activo'       => isset($in['activo']) ? 1 : 0,
        'plataformas'  => array_values(array_unique(array_map('intval', (array) ($in['plataformas'] ?? [])))),
    ];
    $errores = [];

    if (!csrf_valido()) {
        $errores[] = 'Sesión de formulario inválida, recargá la página.';
    }
    if ($datos['titulo'] === '' || mb_strlen($datos['titulo']) > 120) {
        $errores[] = 'El título es obligatorio (máximo 120 caracteres).';
    }
    if (!is_numeric($datos['precio']) || (float) $datos['precio'] < 0 || (float) $datos['precio'] > 99999999) {
        $errores[] = 'El precio debe ser un número válido mayor o igual a 0.';
    }
    if (filter_var($datos['stock'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false) {
        $errores[] = 'El stock debe ser un entero mayor o igual a 0.';
    }
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM categorias WHERE id = :id');
    $stmt->execute([':id' => $datos['categoria_id']]);
    if (!$stmt->fetchColumn()) {
        $errores[] = 'Elegí una categoría válida.';
    }
    return [$datos, $errores];
}

/** Reemplaza las plataformas de un producto (tabla N:M). */
function guardar_plataformas(PDO $pdo, int $productoId, array $plataformas): void
{
    $pdo->prepare('DELETE FROM producto_plataforma WHERE producto_id = :id')->execute([':id' => $productoId]);
    // INSERT ... SELECT: solo se insertan ids de plataformas existentes
    $ins = $pdo->prepare(
        'INSERT INTO producto_plataforma (producto_id, plataforma_id)
         SELECT :producto, id FROM plataformas WHERE id = :plataforma'
    );
    foreach ($plataformas as $plataformaId) {
        $ins->execute([':producto' => $productoId, ':plataforma' => $plataformaId]);
    }
}

function listar_categorias(PDO $pdo): array
{
    return $pdo->query('SELECT id, nombre FROM categorias ORDER BY nombre')->fetchAll();
}

function listar_plataformas(PDO $pdo): array
{
    return $pdo->query('SELECT id, nombre FROM plataformas ORDER BY nombre')->fetchAll();
}
