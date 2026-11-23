<?php
require_once __DIR__ . '/funciones.php';
iniciar_sesion_segura();
$titulo_pagina = $titulo_pagina ?? 'SKYGAMES';
$base = $base ?? '';
$rol = $_SESSION['rol'] ?? '';
$nombre = $_SESSION['usuario_nombre'] ?? '';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($titulo_pagina, ENT_QUOTES, 'UTF-8'); ?> - SkyGames</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">
    <link rel="stylesheet" href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>css/styles.css?v=<?= filemtime(__DIR__ . '/../css/styles.css'); ?>">
    <?php if (isset($stylesheet_adicional) && is_string($stylesheet_adicional) && $stylesheet_adicional !== ''): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($base . $stylesheet_adicional, ENT_QUOTES, 'UTF-8'); ?>?v=<?= filemtime(__DIR__ . '/../' . $stylesheet_adicional); ?>">
    <?php endif; ?>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark" aria-label="Navegación principal">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>index.php">SkyGames</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menuPrincipal">
            <div class="navbar-nav align-items-lg-center">
                <?php if (!empty($_SESSION['usuario_id'])): ?>
                    <span class="navbar-text me-2"><?= htmlspecialchars($nombre . ' (' . $rol . ')', ENT_QUOTES, 'UTF-8'); ?></span>
                    <form method="post" action="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>logout.php">
                        <?= csrf_campo(); ?>
                        <button class="btn btn-outline-light btn-sm" type="submit">Cerrar sesión</button>
                    </form>
                <?php else: ?>
                    <a class="nav-link" href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>login.php">Ingresar</a>
                    <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>registro.php">Registrarse</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<main class="container py-4">
