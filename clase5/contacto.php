<?php
// TP5 - Formulario por POST: consulta de productos (se guarda en la tabla consultas)
$base = '../';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/conexion.php';
iniciar_sesion_segura();

if (($_SESSION['rol'] ?? '') === 'admin') {
    http_response_code(403);
    exit('El administrador no puede enviar consultas.');
}

$categorias = $pdo->query('SELECT id, nombre FROM categorias ORDER BY nombre')->fetchAll();

$datos = [
    'nombre'       => $_SESSION['usuario_nombre'] ?? '',
    'email'        => '',
    'categoria_id' => 0,
    'mensaje'      => '',
];
$errores = [];
$exito = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitización de entradas
    $datos['nombre']       = limpiar($_POST['nombre'] ?? '');
    $datos['email']        = strtolower(limpiar($_POST['email'] ?? ''));
    $datos['categoria_id'] = (int) filter_input(INPUT_POST, 'categoria_id', FILTER_VALIDATE_INT);
    $datos['mensaje']      = limpiar($_POST['mensaje'] ?? '');

    // Validación en servidor
    if (!csrf_valido()) {
        $errores[] = 'Sesión de formulario inválida, recargá la página e intentá de nuevo.';
    }
    if ($datos['nombre'] === '') {
        $errores[] = 'El nombre es obligatorio.';
    } elseif (!preg_match('/^[\p{L}\s\'.-]{2,100}$/u', $datos['nombre'])) {
        $errores[] = 'El nombre solo puede contener letras y espacios (2 a 100 caracteres).';
    }
    if ($datos['email'] === '') {
        $errores[] = 'El correo electrónico es obligatorio.';
    } elseif (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($datos['email']) > 150) {
        $errores[] = 'El formato del correo electrónico no es válido.';
    }
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM categorias WHERE id = :id');
    $stmt->execute([':id' => $datos['categoria_id']]);
    if (!$stmt->fetchColumn()) {
        $errores[] = 'Elegí un tipo de producto válido.';
    }
    if ($datos['mensaje'] === '') {
        $errores[] = 'La consulta no puede estar vacía.';
    } elseif (mb_strlen($datos['mensaje']) > 1000) {
        $errores[] = 'La consulta no puede superar los 1000 caracteres.';
    }

    if (!$errores) {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO consultas (usuario_id, categoria_id, estado_id, nombre, email, mensaje)
                 VALUES (
                     :usuario_id,
                     :categoria_id,
                     (SELECT id FROM estados_consulta WHERE nombre = 'Pendiente'),
                     :nombre,
                     :email,
                     :mensaje
                 )"
            );
            $stmt->execute([
                ':usuario_id'   => $_SESSION['usuario_id'] ?? null,
                ':categoria_id' => $datos['categoria_id'],
                ':nombre'       => $datos['nombre'],
                ':email'        => $datos['email'],
                ':mensaje'      => $datos['mensaje'],
            ]);
            $exito = true;
            // Formulario limpio tras el éxito
            $datos = ['nombre' => $_SESSION['usuario_nombre'] ?? '', 'email' => '', 'categoria_id' => 0, 'mensaje' => ''];
        } catch (PDOException $ex) {
            $errores[] = 'No se pudo guardar la consulta. Intentá más tarde.';
        }
    }
}

$titulo_pagina = 'Contacto';
$body_class = 'fondo-purpura';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav.php';
?>

<h2>FORMULARIO PARA CONSULTAS DE PRODUCTOS (método POST)</h2>

<?php if ($exito): ?>
    <div class="mensaje mensaje-ok" role="status">¡Consulta enviada correctamente! Te responderemos por correo.</div>
<?php endif; ?>

<?php if ($errores): ?>
    <div class="mensaje mensaje-error" role="alert">
        <strong>Revisá los siguientes errores:</strong>
        <ul>
            <?php foreach ($errores as $error): ?>
                <li><?= e($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="contacto.php" class="form-servidor" novalidate>
    <?= csrf_campo(); ?>

    <label for="nombre">Nombre completo:</label><br>
    <input type="text" id="nombre" name="nombre" value="<?= e($datos['nombre']); ?>" placeholder="Ingresa tu nombre"><br><br>

    <label for="email">Correo electrónico:</label><br>
    <input type="email" id="email" name="email" value="<?= e($datos['email']); ?>" placeholder="direccion de correo"><br><br>

    <label for="categoria_id">Tipo de producto elegido:</label><br>
    <select id="categoria_id" name="categoria_id">
        <option value="">-- Elegí una --</option>
        <?php foreach ($categorias as $cat): ?>
            <option value="<?= e($cat['id']); ?>" <?= (int) $datos['categoria_id'] === (int) $cat['id'] ? 'selected' : ''; ?>>
                <?= e($cat['nombre']); ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label for="mensaje">Consulta o pedido:</label><br>
    <textarea id="mensaje" name="mensaje" rows="5" placeholder="Escribe tu consulta"><?= e($datos['mensaje']); ?></textarea><br><br>

    <button type="submit">Enviar Consulta</button>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
