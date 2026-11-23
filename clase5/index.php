<?php
$titulo_pagina = 'Consultas y búsqueda';
$body_class = 'fondo-purpura';
$base = '../';
$stylesheet_adicional = 'clase5/estilos-clase5.css';
require_once __DIR__ . '/../includes/funciones.php';
iniciar_sesion_segura();
$esAdministrador = ($_SESSION['rol'] ?? '') === 'admin';

function clase5_escape($value)
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function clase5_post_value($key)
{
    return isset($_POST[$key]) && is_string($_POST[$key])
        ? trim($_POST[$key])
        : '';
}

$nombre = '';
$correo = '';
$producto = '';
$mensaje = '';
$errores = [];
$consulta_enviada = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($esAdministrador) {
        http_response_code(403);
        exit('El administrador no puede enviar consultas.');
    }

    $nombre = clase5_post_value('nombre');
    $correo = clase5_post_value('correo');
    $producto = clase5_post_value('producto');
    $mensaje = clase5_post_value('mensaje');

    if (!isset($_POST['nombre']) || empty($nombre)) {
        $errores[] = 'Ingresa tu nombre completo.';
    }

    if (!isset($_POST['correo']) || empty($correo)) {
        $errores[] = 'Ingresa tu correo electrónico.';
    } elseif (filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
        $errores[] = 'Ingresa un correo electrónico válido.';
    }

    if (!isset($_POST['producto']) || empty($producto)) {
        $errores[] = 'Selecciona un tipo de producto.';
    } elseif (!in_array($producto, ['Consolas', 'Videojuegos', 'Accesorios', 'PC Gaming'], true)) {
        $errores[] = 'Selecciona una categoría de producto válida.';
    }

    if (!isset($_POST['mensaje']) || empty($mensaje)) {
        $errores[] = 'Escribe tu consulta.';
    }

    $consulta_enviada = count($errores) === 0;
}

$termino_busqueda = '';
$error_busqueda = '';
$resultados = [];
$productos_disponibles = [
    ['nombre' => 'PlayStation 5', 'tipo' => 'Consola'],
    ['nombre' => 'Nintendo Switch', 'tipo' => 'Consola'],
    ['nombre' => 'Xbox Series X', 'tipo' => 'Consola'],
    ['nombre' => 'The Legend of Zelda: Tears of the Kingdom', 'tipo' => 'Videojuego'],
    ['nombre' => 'Spider-Man 2', 'tipo' => 'Videojuego'],
    ['nombre' => 'Super Mario Bros. Wonder', 'tipo' => 'Videojuego'],
];

if (isset($_GET['q'])) {
    if (!is_string($_GET['q'])) {
        $error_busqueda = 'El término de búsqueda no es válido.';
    } else {
        $termino_busqueda = trim($_GET['q']);
        if (strlen($termino_busqueda) > 100) {
            $termino_busqueda = '';
            $error_busqueda = 'La búsqueda no puede superar los 100 caracteres.';
        } elseif ($termino_busqueda !== '') {
            foreach ($productos_disponibles as $item) {
                if (
                    stripos($item['nombre'], $termino_busqueda) !== false
                    || stripos($item['tipo'], $termino_busqueda) !== false
                ) {
                    $resultados[] = $item;
                }
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/nav.php';
?>

<main class="clase5-contenedor">
    <h1><?= $esAdministrador ? 'Búsqueda de productos' : 'Consultas y búsqueda de productos'; ?></h1>
    <p><?= $esAdministrador
        ? 'Busca videojuegos y consolas disponibles.'
        : '¿Tienes dudas sobre un videojuego o consola? Envíanos tu consulta o encuentra productos disponibles.'; ?></p>

    <?php if (!$esAdministrador): ?>
    <section class="clase5-seccion" aria-labelledby="titulo-consulta">
        <h2 id="titulo-consulta">Formulario de consultas</h2>

        <?php if ($consulta_enviada): ?>
            <p class="clase5-mensaje clase5-exito" role="status">
                Gracias, <?= clase5_escape($nombre); ?>. Tu consulta fue recibida correctamente.
            </p>
        <?php elseif ($errores !== []): ?>
            <div class="clase5-mensaje clase5-error" role="alert">
                <p>Revisa los siguientes campos:</p>
                <ul>
                    <?php foreach ($errores as $error): ?>
                        <li><?= clase5_escape($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="clase5/index.php" method="post">
            <label for="nombre">Nombre completo</label>
            <input
                type="text"
                id="nombre"
                name="nombre"
                maxlength="100"
                autocomplete="name"
                value="<?= clase5_escape($nombre); ?>"
                required
            >

            <label for="correo">Correo electrónico</label>
            <input
                type="email"
                id="correo"
                name="correo"
                maxlength="254"
                autocomplete="email"
                value="<?= clase5_escape($correo); ?>"
                required
            >

            <label for="producto">Tipo de producto</label>
            <select id="producto" name="producto" required>
                <option value="">Selecciona una categoría</option>
                <?php foreach (['Consolas', 'Videojuegos', 'Accesorios', 'PC Gaming'] as $categoria): ?>
                    <option
                        value="<?= clase5_escape($categoria); ?>"
                        <?= $producto === $categoria ? 'selected' : ''; ?>
                    ><?= clase5_escape($categoria); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="mensaje">Consulta o pedido</label>
            <textarea id="mensaje" name="mensaje" rows="5" maxlength="2000" required><?= clase5_escape($mensaje); ?></textarea>

            <button type="submit">Enviar consulta</button>
        </form>
    </section>
    <?php endif; ?>

    <section class="clase5-seccion" aria-labelledby="titulo-busqueda">
        <h2 id="titulo-busqueda">Búsqueda rápida</h2>
        <p>Busca consolas y videojuegos, por ejemplo, PlayStation 5 o Nintendo Switch.</p>

        <form action="clase5/index.php" method="get">
            <label for="busqueda">Nombre o categoría</label>
            <div class="clase5-busqueda">
                <input
                    type="search"
                    id="busqueda"
                    name="q"
                    maxlength="100"
                    value="<?= clase5_escape($termino_busqueda); ?>"
                >
                <button type="submit">Buscar</button>
            </div>
        </form>

        <?php if ($error_busqueda !== ''): ?>
            <p class="clase5-mensaje clase5-error" role="alert"><?= clase5_escape($error_busqueda); ?></p>
        <?php elseif (isset($_GET['q']) && $termino_busqueda !== ''): ?>
            <?php if ($resultados === []): ?>
                <p class="clase5-mensaje">No encontramos productos para “<?= clase5_escape($termino_busqueda); ?>”.</p>
            <?php else: ?>
                <h3>Resultados para “<?= clase5_escape($termino_busqueda); ?>”</h3>
                <ul class="clase5-resultados">
                    <?php foreach ($resultados as $item): ?>
                        <li>
                            <strong><?= clase5_escape($item['nombre']); ?></strong>
                            <span><?= clase5_escape($item['tipo']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
