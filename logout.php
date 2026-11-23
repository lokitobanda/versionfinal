<?php
require_once __DIR__ . '/includes/funciones.php';
iniciar_sesion_segura();

// Vaciar variables de sesión
$_SESSION = [];

// Eliminar la cookie de sesión del navegador
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}

// Destruir la sesión
session_destroy();

header('Location: login.php?status=logout');
exit;
