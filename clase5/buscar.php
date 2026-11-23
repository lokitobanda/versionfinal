<?php
// TP5 - Formulario por GET: búsqueda y filtro de productos
$base = '../';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/conexion.php';

$errores = [];
$resultados = [];
$q = '';
$categoria = 'todos';
$categorias = [
    'todos' => 'Todos los catálogos',
    'videojuegos' => 'Videojuegos',
    'consolas' => 'Consolas',
    'accesorios' => 'Accesorios',
    'pc-gaming' => 'PC Gaming',
    'perifericos' => 'Periféricos',
];
$categoriasProducto = [
    'videojuegos' => ['Videojuegos', 'Rol', 'Deportes', 'Carreras'],
    'consolas' => ['Consolas'],
    'accesorios' => ['Accesorios'],
    'pc-gaming' => ['PC Gaming'],
    'perifericos' => ['Periféricos', 'Accesorios'],
];

if (isset($_GET['q']) && !is_string($_GET['q'])) {
    $errores[] = 'El texto de búsqueda no es válido.';
} else {
    $q = limpiar($_GET['q'] ?? '');
}

if (isset($_GET['categoria']) && !is_string($_GET['categoria'])) {
    $errores[] = 'La categoría seleccionada no es válida.';
} else {
    $categoria = limpiar($_GET['categoria'] ?? 'todos');
    if (!array_key_exists($categoria, $categorias)) {
        $errores[] = 'Seleccioná una categoría válida.';
        $categoria = 'todos';
    }
}

if (mb_strlen($q) > 60) {
    $errores[] = 'El texto de búsqueda no puede superar los 60 caracteres.';
}

if (!$errores) {
    $sql = 'SELECT p.id, p.titulo, p.descripcion, p.precio, c.nombre AS categoria
            FROM productos p
            INNER JOIN categorias c ON c.id = p.categoria_id
            WHERE p.activo = 1';
    $params = [];

    if ($q !== '') {
        $termino = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($q, 'UTF-8'));
        $sql .= " AND LOWER(p.titulo) LIKE :q ESCAPE '!'";
        $params[':q'] = '%' . $termino . '%';
    }

    if ($categoria !== 'todos') {
        $nombresCategoria = $categoriasProducto[$categoria];
        $marcadores = [];
        foreach ($nombresCategoria as $indice => $nombreCategoria) {
            $marcador = ':categoria' . $indice;
            $marcadores[] = $marcador;
            $params[$marcador] = $nombreCategoria;
        }
        $sql .= ' AND c.nombre IN (' . implode(', ', $marcadores) . ')';
    }

    $sql .= ' ORDER BY p.titulo';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $resultados = $stmt->fetchAll();
    } catch (PDOException $ex) {
        $errores[] = 'No se pudo realizar la búsqueda. Intentá más tarde.';
    }
}

$titulo_pagina = 'Buscar productos';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav.php';
?>

<main>
<h1>Catálogo de productos</h1>
<p>Explorá consolas, videojuegos y accesorios. Elegí un catálogo para ver sus productos y una breve descripción.</p>

<form method="get" action="buscar.php" class="form-servidor">
    <label for="categoria_id">Elegí un catálogo:</label><br>
    <select id="categoria_id" name="categoria" aria-label="Elegí un catálogo">
        <?php foreach ($categorias as $valor => $nombre): ?>
            <option value="<?= e($valor); ?>" <?= $categoria === $valor ? 'selected' : ''; ?>>
                <?= e($nombre); ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label for="q">Buscar por nombre (opcional):</label><br>
    <input type="text" id="q" name="q" value="<?= e($q); ?>" maxlength="60" placeholder="Ej: Nova"><br><br>

    <button type="submit" name="buscar" value="1">Buscar</button>
</form>

<?php if ($errores): ?>
    <div class="mensaje mensaje-error" role="alert">
        <ul>
            <?php foreach ($errores as $error): ?>
                <li><?= e($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php else: ?>
    <div class="mensaje mensaje-info" role="status">
        <?= e(count($resultados)); ?> producto(s) encontrado(s).
    </div>

    <?php if ($resultados): ?>
        <table class="tabla-datos">
            <thead><tr><th>Producto</th><th>Catálogo</th><th>Información</th><th>Precio</th></tr></thead>
            <tbody>
            <?php foreach ($resultados as $r): ?>
                <tr>
                    <td><?= e($r['titulo']); ?></td>
                    <td><?= e($r['categoria']); ?></td>
                    <td><?= e($r['descripcion']); ?></td>
                    <td>$<?= e(number_format((float) $r['precio'], 2)); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No se encontraron productos.</p>
    <?php endif; ?>
<?php endif; ?>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
