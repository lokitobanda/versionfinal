<?php
require_once __DIR__ . '/includes/funciones.php';
require_once __DIR__ . '/config/conexion.php';
iniciar_sesion_segura();

if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errores = [];
$identificador = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identificador = limpiar($_POST['identificador'] ?? '');
    if (strpos($identificador, '@') !== false) {
        $identificador = strtolower($identificador);
    }
    $password = isset($_POST['password']) && is_string($_POST['password'])
        ? $_POST['password']
        : '';

    if (!csrf_valido()) {
        $errores[] = 'Sesión de formulario inválida, recargá la página e intentá de nuevo.';
    }
    if (
        $identificador === ''
        || strlen($identificador) > 150
        || (strpos($identificador, '@') !== false && !filter_var($identificador, FILTER_VALIDATE_EMAIL))
        || (strpos($identificador, '@') === false && !preg_match('/^[A-Za-z0-9_]{3,60}$/', $identificador))
    ) {
        $errores[] = 'Ingresá un correo electrónico o nombre de usuario válido.';
    }
    if ($password === '') {
        $errores[] = 'Ingresá tu contraseña.';
    }

    if (!$errores) {
        try {
            $stmt = $pdo->prepare(
                'SELECT u.id, u.nombre, u.password, r.nombre AS rol
                 FROM usuarios u
                 INNER JOIN roles r ON r.id = u.rol_id
                 WHERE u.email = :email OR u.username = :username
                 LIMIT 1'
            );
            $stmt->execute([
                ':email' => $identificador,
                ':username' => $identificador,
            ]);
            $usuario = $stmt->fetch();

            if ($usuario && password_verify($password, $usuario['password'])) {
                // Mitiga la fijación de sesión
                session_regenerate_id(true);
                $_SESSION['usuario_id']     = (int) $usuario['id'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'];
                $_SESSION['rol']            = $usuario['rol'];
                $_SESSION['flash_success'] = 'Inicio de sesión exitoso.';
                header('Location: dashboard.php');
                exit;
            }
            // Mensaje genérico: no revela si el correo existe
            $errores[] = 'Correo o contraseña incorrectos.';
        } catch (PDOException $ex) {
            $errores[] = 'No se pudo iniciar sesión. Intentá más tarde.';
        }
    }
}

$aviso = '';
if (($_GET['error'] ?? '') === 'acceso_denegado') {
    $aviso = 'Debés iniciar sesión para acceder a esa página.';
} elseif (($_GET['status'] ?? '') === 'registrado') {
    $aviso = 'Usuario registrado correctamente. Ya podés iniciar sesión.';
} elseif (($_GET['status'] ?? '') === 'logout') {
    $aviso = 'Sesión cerrada correctamente.';
}

$titulo_pagina = 'Iniciar sesión';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
?>

<h1>Iniciar sesión</h1>

<?php if ($aviso !== ''): ?>
    <div class="mensaje mensaje-info" role="status"><?= e($aviso); ?></div>
<?php endif; ?>

<?php if ($errores): ?>
    <div class="mensaje mensaje-error" role="alert">
        <ul>
            <?php foreach ($errores as $error): ?>
                <li><?= e($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="login.php" class="form-servidor" novalidate>
    <?= csrf_campo(); ?>

    <label for="identificador">Correo electrónico o nombre de usuario:</label><br>
    <input type="text" id="identificador" name="identificador" value="<?= e($identificador); ?>" autocomplete="username" required><br><br>

    <label for="password">Contraseña:</label><br>
    <input type="password" id="password" name="password" autocomplete="current-password" required><br><br>

    <button type="submit" class="boton-registro">Ingresar</button>
</form>

<p>¿No tenés cuenta? <a href="registro.php">Registrate</a></p>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
