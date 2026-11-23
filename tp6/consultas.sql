-- Datos de catálogo de SKYGAMES y consultas de lectura.
-- Ejecutar después de schema.sql. Se puede volver a ejecutar sin duplicar datos.
-- Las cuentas reales se crean en registro.php y se guardan en la tabla usuarios.
USE skygames_db;

-- Categorías que usan el catálogo, el CRUD y el formulario de consultas.
INSERT IGNORE INTO categorias (nombre, descripcion) VALUES
    ('Videojuegos', 'Juegos digitales y físicos de distintos géneros'),
    ('Consolas', 'Consolas de sobremesa y portátiles'),
    ('Accesorios', 'Accesorios para consolas y videojuegos'),
    ('PC Gaming', 'Computadoras y componentes para jugar'),
    ('Periféricos', 'Controles, teclados, auriculares y otros periféricos'),
    ('Rol', 'Juegos de rol y aventura'),
    ('Deportes', 'Videojuegos de deportes'),
    ('Carreras', 'Videojuegos de conducción');

INSERT IGNORE INTO plataformas (nombre) VALUES
    ('PS5'),
    ('Xbox'),
    ('PC'),
    ('Switch');

-- Productos de ejemplo con descripciones breves y datos para el catálogo.
-- Cada producto se agrega solo si todavía no existe un producto con ese título.
INSERT INTO productos (categoria_id, titulo, descripcion, precio, stock)
SELECT c.id, 'Consola Nova X',
       'Consola de sobremesa con imagen 4K y 1 TB de almacenamiento.',
       499.99, 12
FROM categorias c
WHERE c.nombre = 'Consolas'
  AND NOT EXISTS (SELECT 1 FROM productos WHERE titulo = 'Consola Nova X');

INSERT INTO productos (categoria_id, titulo, descripcion, precio, stock)
SELECT c.id, 'Consola portátil Pixel Go',
       'Consola portátil con pantalla de 7 pulgadas y controles integrados.',
       279.99, 18
FROM categorias c
WHERE c.nombre = 'Consolas'
  AND NOT EXISTS (SELECT 1 FROM productos WHERE titulo = 'Consola portátil Pixel Go');

INSERT INTO productos (categoria_id, titulo, descripcion, precio, stock)
SELECT c.id, 'Reinos de Cristal',
       'Aventura de rol con exploración, misiones y mundos fantásticos.',
       49.99, 25
FROM categorias c
WHERE c.nombre = 'Videojuegos'
  AND NOT EXISTS (SELECT 1 FROM productos WHERE titulo = 'Reinos de Cristal');

INSERT INTO productos (categoria_id, titulo, descripcion, precio, stock)
SELECT c.id, 'Operación Eclipse',
       'Juego de acción cooperativo con una campaña de ciencia ficción.',
       39.99, 16
FROM categorias c
WHERE c.nombre = 'Videojuegos'
  AND NOT EXISTS (SELECT 1 FROM productos WHERE titulo = 'Operación Eclipse');

INSERT INTO productos (categoria_id, titulo, descripcion, precio, stock)
SELECT c.id, 'Control Nova inalámbrico',
       'Control ergonómico inalámbrico con vibración y batería recargable.',
       59.99, 30
FROM categorias c
WHERE c.nombre = 'Accesorios'
  AND NOT EXISTS (SELECT 1 FROM productos WHERE titulo = 'Control Nova inalámbrico');

INSERT INTO productos (categoria_id, titulo, descripcion, precio, stock)
SELECT c.id, 'PC Gamer Nebula',
       'Computadora para juegos en 1440p con procesador de seis núcleos.',
       1099.99, 8
FROM categorias c
WHERE c.nombre = 'PC Gaming'
  AND NOT EXISTS (SELECT 1 FROM productos WHERE titulo = 'PC Gamer Nebula');

INSERT INTO productos (categoria_id, titulo, descripcion, precio, stock)
SELECT c.id, 'Auriculares Quantum',
       'Auriculares gamer con micrófono integrado y sonido envolvente.',
       79.99, 14
FROM categorias c
WHERE c.nombre = 'Periféricos'
  AND NOT EXISTS (SELECT 1 FROM productos WHERE titulo = 'Auriculares Quantum');

INSERT INTO productos (categoria_id, titulo, descripcion, precio, stock)
SELECT c.id, 'Teclado mecánico RGB',
       'Teclado compacto con switches mecánicos e iluminación configurable.',
       69.99, 22
FROM categorias c
WHERE c.nombre = 'Periféricos'
  AND NOT EXISTS (SELECT 1 FROM productos WHERE titulo = 'Teclado mecánico RGB');

-- Plataformas disponibles para los videojuegos de ejemplo.
INSERT IGNORE INTO producto_plataforma (producto_id, plataforma_id)
SELECT p.id, pl.id
FROM productos p
JOIN plataformas pl ON pl.nombre IN ('PS5', 'Xbox', 'PC')
WHERE p.titulo = 'Reinos de Cristal';

INSERT IGNORE INTO producto_plataforma (producto_id, plataforma_id)
SELECT p.id, pl.id
FROM productos p
JOIN plataformas pl ON pl.nombre IN ('PS5', 'Xbox')
WHERE p.titulo = 'Operación Eclipse';

-- Catálogo de productos activos.
SELECT p.id, p.titulo, c.nombre AS categoria, p.descripcion, p.precio, p.stock
FROM productos p
INNER JOIN categorias c ON c.id = p.categoria_id
WHERE p.activo = 1
ORDER BY c.nombre, p.titulo;

-- Cuentas registradas en el sitio (sin mostrar hashes de contraseñas).
SELECT u.id, u.nombre, u.email, u.telefono, r.nombre AS rol, u.created_at
FROM usuarios u
INNER JOIN roles r ON r.id = u.rol_id
ORDER BY u.created_at DESC;

-- Consultas recibidas desde el formulario de contacto.
SELECT c.id, c.nombre, c.email, cat.nombre AS categoria,
       e.nombre AS estado, c.mensaje, c.created_at
FROM consultas c
INNER JOIN categorias cat ON cat.id = c.categoria_id
INNER JOIN estados_consulta e ON e.id = c.estado_id
ORDER BY c.created_at DESC;
