<?php
require_once __DIR__ . '/includes/funciones.php';
require_once __DIR__ . '/config/conexion.php';
iniciar_sesion_segura();

$errores = [];
$nombre = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = limpiar($_POST['nombre'] ?? '');
    $email = strtolower(limpiar($_POST['email'] ?? ''));
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $password2 = is_string($_POST['password2'] ?? null) ? $_POST['password2'] : '';

    if (!csrf_valido()) $errores[] = 'La sesión del formulario venció.';
    if ($nombre === '' || mb_strlen($nombre) > 100) $errores[] = 'Ingresá un nombre válido.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) $errores[] = 'Ingresá un correo válido.';
    if (strlen($password) < 8) $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    if ($password !== $password2) $errores[] = 'Las contraseñas no coinciden.';

    if (!$errores) {
        $existe = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email');
        $existe->execute([':email' => $email]);
        if ($existe->fetch()) {
            $errores[] = 'Ese correo ya está registrado.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $pdo->prepare(
                "INSERT INTO usuarios (nombre, email, password, rol, rol_id)
                 VALUES (:nombre, :email, :password, 'cliente',
                         (SELECT id FROM roles WHERE nombre = 'cliente'))"
            );
            try {
                $insert->execute([':nombre' => $nombre, ':email' => $email, ':password' => $hash]);
                header('Location: login.php?status=registrado');
                exit;
            } catch (PDOException $e) {
                $errores[] = 'No se pudo completar el registro.';
            }
        }
    }
}

$titulo_pagina = 'Crear cuenta';
require_once __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center"><div class="col-md-7">
    <h1>Crear cuenta cliente</h1>
    <?php if ($errores): ?><div class="alert alert-danger"><ul class="mb-0">
        <?php foreach ($errores as $error): ?><li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li><?php endforeach; ?>
    </ul></div><?php endif; ?>
    <form method="post" action="registro.php">
        <?= csrf_campo(); ?>
        <div class="mb-3"><label class="form-label" for="nombre">Nombre</label>
            <input class="form-control" type="text" id="nombre" name="nombre" maxlength="100"
                   value="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?>" required></div>
        <div class="mb-3"><label class="form-label" for="email">Correo</label>
            <input class="form-control" type="email" id="email" name="email" maxlength="150"
                   value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required></div>
        <div class="mb-3"><label class="form-label" for="password">Contraseña</label>
            <input class="form-control" type="password" id="password" name="password" minlength="8" required></div>
        <div class="mb-3"><label class="form-label" for="password2">Repetir contraseña</label>
            <input class="form-control" type="password" id="password2" name="password2" minlength="8" required></div>
        <button class="btn btn-primary" type="submit">Registrarme</button>
    </form>
</div></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
