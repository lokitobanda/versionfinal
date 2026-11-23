<?php
$menu = [
    ['inicio.php', 'Inicio'],
    ['ofertas.php', 'Ofertas'],
    ['pagos.php', 'Pagos'],
    ['envios.php', 'Envíos'],
    ['vendedores.php', 'Vendedores'],
    ['novedades.php', 'Novedades'],
    ['opciones.php', 'Opciones'],
    ['clasificacion.php', 'Clasificación'],
    ['fin.php', 'Fin'],
    ['curso.php', 'Curso'],
    ['blog.php', 'Blog'],
    ['cv.php', 'CV'],
    ['preguntas.php', 'Preguntas Frecuentes'],
];
?>
<div class="hearder">
    <h1>SKYGAMES</h1>
    <p>BIENVENIDO!!</p>
    <p id="fechaHora"></p>
</div>

<button id="modoOscuro">🌙 Activar modo oscuro</button>

<nav class="menu-principal" aria-label="Navegación principal">
    <?php foreach ($menu as $index => $item): ?>
        <?php if ($index > 0): ?> | <?php endif; ?>
        <a href="<?= e($base . $item[0]); ?>"><?= e($item[1]); ?></a>
    <?php endforeach; ?>
    <?php if (($_SESSION['rol'] ?? '') !== 'admin'): ?>
        | <a href="<?= e($base); ?>clase5/contacto.php">Contacto</a>
    <?php endif; ?>
    | <a href="<?= e($base); ?>buscar.php">Buscar</a>
    <?php if (isset($_SESSION['usuario_id'])): ?>
        | <a href="<?= e($base); ?>dashboard.php">Mi cuenta (<?= e($_SESSION['usuario_nombre']); ?>)</a>
        | <a href="<?= e($base); ?>logout.php">Salir</a>
    <?php else: ?>
        | <a href="<?= e($base); ?>login.php">Ingresar</a>
        | <a href="<?= e($base); ?>registro.php">Registrarse</a>
    <?php endif; ?>
</nav>
<hr>
