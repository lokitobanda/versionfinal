-- Esquema MySQL/MariaDB de SKYGAMES.
-- Puede ejecutarse más de una vez: no elimina tablas ni cuentas registradas.

CREATE DATABASE IF NOT EXISTS skygames_db
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE skygames_db;

-- Actualiza una instalación existente sin duplicar columnas ni eliminar datos.
SET @add_imagen := (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE productos ADD COLUMN imagen VARCHAR(255) NULL AFTER activo',
        'SELECT 1')
    FROM information_schema.columns
    WHERE table_schema = 'skygames_db' AND table_name = 'productos' AND column_name = 'imagen'
);
PREPARE skygames_stmt FROM @add_imagen;
EXECUTE skygames_stmt;
DEALLOCATE PREPARE skygames_stmt;

SET @add_vendedor := (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE productos ADD COLUMN vendedor_id INT NULL AFTER imagen',
        'SELECT 1')
    FROM information_schema.columns
    WHERE table_schema = 'skygames_db' AND table_name = 'productos' AND column_name = 'vendedor_id'
);
PREPARE skygames_stmt FROM @add_vendedor;
EXECUTE skygames_stmt;
DEALLOCATE PREPARE skygames_stmt;

CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL UNIQUE,
    descripcion VARCHAR(150) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(60) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS plataformas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(40) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS estados_consulta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- registro.php inserta las cuentas en esta tabla con la contraseña hasheada.
-- No se incluyen cuentas ficticias ni contraseñas en los scripts SQL.
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rol_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    telefono VARCHAR(30) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuarios_rol FOREIGN KEY (rol_id) REFERENCES roles(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    titulo VARCHAR(120) NOT NULL,
    descripcion TEXT NULL,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0,
    stock INT NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    imagen VARCHAR(255) NULL,
    vendedor_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_productos_categoria (categoria_id),
    KEY idx_productos_titulo (titulo),
    CONSTRAINT fk_productos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_productos_vendedor FOREIGN KEY (vendedor_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS producto_plataforma (
    producto_id INT NOT NULL,
    plataforma_id INT NOT NULL,
    PRIMARY KEY (producto_id, plataforma_id),
    KEY idx_producto_plataforma_plataforma (plataforma_id),
    CONSTRAINT fk_pp_producto FOREIGN KEY (producto_id) REFERENCES productos(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_pp_plataforma FOREIGN KEY (plataforma_id) REFERENCES plataformas(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS consultas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    categoria_id INT NOT NULL,
    estado_id INT NOT NULL DEFAULT 1,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    mensaje TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_consultas_estado (estado_id),
    CONSTRAINT fk_consultas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_consultas_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_consultas_estado FOREIGN KEY (estado_id) REFERENCES estados_consulta(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    vendedor_id INT NULL,
    producto_id INT NOT NULL,
    cantidad INT UNSIGNED NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ventas_usuario (usuario_id),
    KEY idx_ventas_vendedor (vendedor_id),
    CONSTRAINT fk_ventas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_ventas_vendedor FOREIGN KEY (vendedor_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_ventas_producto FOREIGN KEY (producto_id) REFERENCES productos(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Roles y estados requeridos por el registro, el panel y el formulario de consultas.
INSERT IGNORE INTO roles (nombre, descripcion) VALUES
    ('admin', 'Administrador del sitio'),
    ('cliente', 'Cliente comprador');

UPDATE usuarios u
LEFT JOIN roles rol_actual ON rol_actual.id = u.rol_id
JOIN roles rol_admin ON rol_admin.nombre = 'admin'
JOIN roles rol_cliente ON rol_cliente.nombre = 'cliente'
SET u.rol_id = CASE WHEN rol_actual.nombre = 'admin' THEN rol_admin.id ELSE rol_cliente.id END;

DELETE FROM roles WHERE nombre NOT IN ('admin', 'cliente');

INSERT IGNORE INTO estados_consulta (nombre) VALUES
    ('Pendiente'),
    ('Respondida');
