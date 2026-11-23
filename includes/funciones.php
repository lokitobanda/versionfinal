<?php
/**
 * Funciones auxiliares compartidas: escape XSS, sesión segura y token CSRF.
 */

/** Escapa una salida HTML (protección XSS). */
function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

/** Sanitiza una entrada de $_POST o $_GET (trim); ignora arrays y nulos. */
function limpiar($valor): string
{
    return is_string($valor) ? trim($valor) : '';
}

/** Inicia la sesión con cookie segura (HttpOnly, SameSite, modo estricto). */
function iniciar_sesion_segura(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $config = require __DIR__ . '/../config/env.php';
    if (!empty($config['session_name'])) {
        session_name($config['session_name']);
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/** Token CSRF de la sesión actual. */
function csrf_token(): string
{
    iniciar_sesion_segura();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Campo oculto con el token CSRF para los formularios POST. */
function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Verifica el token CSRF recibido por POST. */
function csrf_valido(): bool
{
    iniciar_sesion_segura();
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], (string) $_POST['csrf']);
}
