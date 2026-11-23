<?php
/**
 * Formulario compartido por crear.php y editar.php.
 * Variables: $datos, $errores, $categorias, $plataformas, $accion, $textoBoton
 */
?>
<?php if ($errores): ?>
    <div class="mensaje mensaje-error" role="alert">
        <ul>
            <?php foreach ($errores as $error): ?>
                <li><?= e($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="<?= e($accion); ?>" class="form-servidor" novalidate>
    <?= csrf_campo(); ?>

    <label for="titulo">Título:</label><br>
    <input type="text" id="titulo" name="titulo" value="<?= e($datos['titulo']); ?>" maxlength="120" required><br><br>

    <label for="descripcion">Descripción:</label><br>
    <textarea id="descripcion" name="descripcion" rows="4"><?= e($datos['descripcion']); ?></textarea><br><br>

    <label for="categoria_id">Categoría:</label><br>
    <select id="categoria_id" name="categoria_id" required>
        <option value="">-- Elegí una --</option>
        <?php foreach ($categorias as $cat): ?>
            <option value="<?= e($cat['id']); ?>" <?= (int) $datos['categoria_id'] === (int) $cat['id'] ? 'selected' : ''; ?>>
                <?= e($cat['nombre']); ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label for="precio">Precio:</label><br>
    <input type="number" id="precio" name="precio" step="0.01" min="0" value="<?= e($datos['precio']); ?>" required><br><br>

    <label for="stock">Stock:</label><br>
    <input type="number" id="stock" name="stock" min="0" value="<?= e($datos['stock']); ?>"><br><br>

    <fieldset>
        <legend>Plataformas</legend>
        <?php foreach ($plataformas as $pl): ?>
            <label>
                <input type="checkbox" name="plataformas[]" value="<?= e($pl['id']); ?>"
                    <?= in_array((int) $pl['id'], $datos['plataformas'], true) ? 'checked' : ''; ?>>
                <?= e($pl['nombre']); ?>
            </label>
        <?php endforeach; ?>
    </fieldset><br>

    <label>
        <input type="checkbox" name="activo" value="1" <?= $datos['activo'] ? 'checked' : ''; ?>>
        Producto activo (visible)
    </label><br><br>

    <button type="submit" class="boton-registro"><?= e($textoBoton); ?></button>
    <a href="index.php">Cancelar</a>
</form>
