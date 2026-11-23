CREATE DATABASE IF NOT EXISTS skygames_db
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE skygames_db;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL UNIQUE,
    descripcion VARCHAR(150) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO roles (nombre, descripcion) VALUES
    ('admin', 'Administrador'),
    ('cliente', 'Cliente');

CREATE TABLE IF NOT EXISTS categorias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(60) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO categorias (nombre, descripcion) VALUES
    ('Videojuegos', 'Juegos digitales y físicos'),
    ('Consolas', 'Consolas de sobremesa y portátiles'),
    ('Accesorios', 'Accesorios para consolas'),
    ('PC Gaming', 'Equipos y componentes gaming'),
    ('Periféricos', 'Teclados, controles y auriculares')
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    username VARCHAR(60) NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin','cliente') NOT NULL DEFAULT 'cliente',
    rol_id INT UNSIGNED NOT NULL,
    telefono VARCHAR(30) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fecha_creacion TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_usuarios_rol (rol_id),
    CONSTRAINT fk_usuarios_rol FOREIGN KEY (rol_id) REFERENCES roles(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS username VARCHAR(60) NULL UNIQUE,
    ADD COLUMN IF NOT EXISTS email VARCHAR(150) NULL UNIQUE,
    ADD COLUMN IF NOT EXISTS rol ENUM('admin','cliente') NOT NULL DEFAULT 'cliente',
    ADD COLUMN IF NOT EXISTS rol_id INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS telefono VARCHAR(30) NULL,
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS fecha_creacion TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;

UPDATE usuarios
SET username = CASE
        WHEN nombre = 'adminSKYGAMES' THEN 'adminSKYGAMES'
        WHEN nombre = 'clienteDemo' THEN 'clienteDemo'
        ELSE CONCAT('usuario', id)
    END
WHERE username IS NULL OR username = '';

UPDATE usuarios
SET email = CASE
        WHEN username = 'adminSKYGAMES' THEN 'admin@skygames.local'
        WHEN username = 'clienteDemo' THEN 'cliente.demo@skygames.local'
        ELSE CONCAT('usuario', id, '@skygames.local')
    END
WHERE email IS NULL OR email = '';

UPDATE usuarios u
INNER JOIN roles r ON r.nombre = u.rol
SET u.rol_id = r.id;

ALTER TABLE usuarios
    MODIFY COLUMN username VARCHAR(60) NULL,
    MODIFY COLUMN email VARCHAR(150) NOT NULL,
    MODIFY COLUMN rol ENUM('admin','cliente') NOT NULL DEFAULT 'cliente',
    MODIFY COLUMN rol_id INT UNSIGNED NOT NULL;

SET @drop_nombre_unique = (
    SELECT IF(COUNT(*) > 0, 'ALTER TABLE usuarios DROP INDEX nombre', 'SELECT 1')
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'usuarios'
      AND index_name = 'nombre'
      AND non_unique = 0
);
PREPARE drop_nombre_unique_stmt FROM @drop_nombre_unique;
EXECUTE drop_nombre_unique_stmt;
DEALLOCATE PREPARE drop_nombre_unique_stmt;

SET @add_usuario_rol_fk = (
    SELECT IF(COUNT(*) > 0, 'SELECT 1',
        'ALTER TABLE usuarios ADD CONSTRAINT fk_usuarios_rol FOREIGN KEY (rol_id) REFERENCES roles(id) ON UPDATE CASCADE ON DELETE RESTRICT')
    FROM information_schema.referential_constraints
    WHERE constraint_schema = DATABASE()
      AND constraint_name = 'fk_usuarios_rol'
);
PREPARE add_usuario_rol_fk_stmt FROM @add_usuario_rol_fk;
EXECUTE add_usuario_rol_fk_stmt;
DEALLOCATE PREPARE add_usuario_rol_fk_stmt;

INSERT INTO usuarios (nombre, username, email, password, rol, rol_id)
SELECT 'Administrador SkyGames', 'adminSKYGAMES', 'admin@skygames.local',
       '$2y$10$GhMG3qVQV0PKsa2JXJfaGe6l5ubMxEqhLYUdDdwku7.0uD0AVbIxW',
       'admin', r.id
FROM roles r
WHERE r.nombre = 'admin'
  AND NOT EXISTS (
      SELECT 1 FROM usuarios u
      WHERE u.username = 'adminSKYGAMES' OR u.email = 'admin@skygames.local'
  );

INSERT INTO usuarios (nombre, username, email, password, rol, rol_id)
SELECT 'Cliente de demostración', 'clienteDemo', 'cliente.demo@skygames.local',
       '$2y$10$lnSz7DqCY7fPe7hUz8voke26PNP2NiVpLcMntjEd4RQOSsdqIl2Z6',
       'cliente', r.id
FROM roles r
WHERE r.nombre = 'cliente'
  AND NOT EXISTS (
      SELECT 1 FROM usuarios u
      WHERE u.username = 'clienteDemo' OR u.email = 'cliente.demo@skygames.local'
  );

CREATE TABLE IF NOT EXISTS catalogo (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    descripcion TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS productos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT UNSIGNED NOT NULL,
    titulo VARCHAR(120) NOT NULL,
    descripcion TEXT NULL,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock INT UNSIGNED NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    imagen VARCHAR(255) NULL,
    vendedor_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_productos_categoria (categoria_id),
    KEY idx_productos_vendedor (vendedor_id),
    CONSTRAINT fk_productos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_productos_vendedor FOREIGN KEY (vendedor_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO productos (categoria_id, titulo, descripcion, precio, stock, activo)
SELECT cat.id, legacy.nombre, legacy.descripcion, legacy.precio, 1, 1
FROM catalogo legacy
INNER JOIN categorias cat ON cat.nombre = 'Videojuegos'
WHERE NOT EXISTS (
    SELECT 1 FROM productos p WHERE p.titulo = legacy.nombre
);

INSERT INTO productos (categoria_id, titulo, descripcion, precio, stock, activo)
SELECT cat.id, 'Nebula Quest',
       'Aventura de exploración espacial con misiones y mundos generados.',
       24999.00, 12, 1
FROM categorias cat
WHERE cat.nombre = 'Videojuegos'
  AND NOT EXISTS (SELECT 1 FROM productos p WHERE p.titulo = 'Nebula Quest');

INSERT INTO productos (categoria_id, titulo, descripcion, precio, stock, activo)
SELECT cat.id, 'Circuito Extremo',
       'Juego de carreras arcade con circuitos urbanos y vehículos personalizables.',
       18999.00, 8, 1
FROM categorias cat
WHERE cat.nombre = 'Videojuegos'
  AND NOT EXISTS (SELECT 1 FROM productos p WHERE p.titulo = 'Circuito Extremo');

INSERT INTO productos (categoria_id, titulo, descripcion, precio, stock, activo)
SELECT cat.id, 'Control Inalámbrico Aurora',
       'Control inalámbrico ergonómico con batería recargable.',
       12999.00, 20, 1
FROM categorias cat
WHERE cat.nombre = 'Accesorios'
  AND NOT EXISTS (SELECT 1 FROM productos p WHERE p.titulo = 'Control Inalámbrico Aurora');

INSERT INTO productos (categoria_id, titulo, descripcion, precio, stock, activo)
SELECT cat.id, 'Teclado Mecánico Prisma',
       'Teclado mecánico compacto con iluminación configurable.',
       15999.00, 10, 1
FROM categorias cat
WHERE cat.nombre = 'Periféricos'
  AND NOT EXISTS (SELECT 1 FROM productos p WHERE p.titulo = 'Teclado Mecánico Prisma');

INSERT INTO productos (categoria_id, titulo, descripcion, precio, stock, activo)
SELECT cat.id, 'Estación Gaming Nova',
       'Equipo de escritorio para juegos y creación de contenido.',
       459999.00, 3, 1
FROM categorias cat
WHERE cat.nombre = 'PC Gaming'
  AND NOT EXISTS (SELECT 1 FROM productos p WHERE p.titulo = 'Estación Gaming Nova');

CREATE TABLE IF NOT EXISTS ventas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NULL,
    cliente_id INT UNSIGNED NULL,
    vendedor_id INT UNSIGNED NULL,
    producto_id INT UNSIGNED NOT NULL,
    cantidad INT UNSIGNED NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(12,2) NOT NULL,
    fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ventas_usuario (usuario_id),
    KEY idx_ventas_cliente (cliente_id),
    KEY idx_ventas_vendedor (vendedor_id),
    KEY idx_ventas_producto (producto_id),
    CONSTRAINT fk_ventas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_ventas_cliente FOREIGN KEY (cliente_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_ventas_vendedor FOREIGN KEY (vendedor_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_ventas_producto FOREIGN KEY (producto_id) REFERENCES productos(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE ventas
    ADD COLUMN IF NOT EXISTS usuario_id INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS cliente_id INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS vendedor_id INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS precio_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS fecha TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;

UPDATE ventas
SET usuario_id = cliente_id
WHERE usuario_id IS NULL AND cliente_id IS NOT NULL;

CREATE TABLE IF NOT EXISTS estados_consulta (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(40) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO estados_consulta (nombre) VALUES
    ('Pendiente'),
    ('En revisión'),
    ('Respondida'),
    ('Cerrada');

CREATE TABLE IF NOT EXISTS consultas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NULL,
    categoria_id INT UNSIGNED NOT NULL,
    estado_id INT UNSIGNED NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    mensaje TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_consultas_usuario (usuario_id),
    KEY idx_consultas_categoria (categoria_id),
    KEY idx_consultas_estado (estado_id),
    CONSTRAINT fk_consultas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_consultas_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_consultas_estado FOREIGN KEY (estado_id) REFERENCES estados_consulta(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS plataformas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO plataformas (nombre) VALUES ('PC'), ('Consola'), ('Móvil');

CREATE TABLE IF NOT EXISTS producto_plataforma (
    producto_id INT UNSIGNED NOT NULL,
    plataforma_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (producto_id, plataforma_id),
    KEY idx_producto_plataforma_plataforma (plataforma_id),
    CONSTRAINT fk_producto_plataforma_producto FOREIGN KEY (producto_id) REFERENCES productos(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_producto_plataforma_plataforma FOREIGN KEY (plataforma_id) REFERENCES plataformas(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
