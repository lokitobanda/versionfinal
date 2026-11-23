<?php
/**
 * Guardián de rutas (TP4).
 * Uso: definir opcionalmente $base (prefijo de ruta) y $rol_requerido
 * antes de incluir este archivo al inicio de la página protegida.
 */
require_once __DIR__ . '/funciones.php';
iniciar_sesion_segura();

$base = $base ?? '';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . $base . 'login.php?error=acceso_denegado');
    exit;
}

if (!in_array($_SESSION['rol'] ?? '', ['admin', 'cliente'], true)) {
    unset($_SESSION['usuario_id'], $_SESSION['usuario_nombre'], $_SESSION['rol']);
    header('Location: ' . $base . 'login.php?error=acceso_denegado');
    exit;
}

if (isset($rol_requerido) && ($_SESSION['rol'] ?? '') !== $rol_requerido) {
    header('Location: ' . $base . 'dashboard.php?error=sin_permiso');
    exit;
}
