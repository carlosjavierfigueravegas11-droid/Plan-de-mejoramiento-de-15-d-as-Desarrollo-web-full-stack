-- Día 9 — Modelo de datos de ISoT
-- Seis tablas: categorias, productos, clientes, pedidos, detalle_pedidos, usuarios
-- MySQL 8 / MariaDB 10.4+ compatible

CREATE DATABASE IF NOT EXISTS isot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE isot;

DROP TABLE IF EXISTS detalle_pedidos;
DROP TABLE IF EXISTS pedidos;
DROP TABLE IF EXISTS clientes;
DROP TABLE IF EXISTS productos;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS usuarios;

CREATE TABLE categorias (
    id     INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(60) NOT NULL UNIQUE
) ENGINE = InnoDB;

CREATE TABLE productos (
    id          INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    categoria_id INT UNSIGNED NOT NULL,
    nombre      VARCHAR(120) NOT NULL,
    precio      DECIMAL(12,2) NOT NULL CHECK (precio >= 0),
    stock       INT NOT NULL DEFAULT 0 CHECK (stock >= 0),
    activo      TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_productos_categoria FOREIGN KEY (categoria_id)
        REFERENCES categorias (id) ON UPDATE CASCADE,
    INDEX idx_productos_nombre (nombre),
    INDEX idx_productos_categoria (categoria_id)
) ENGINE = InnoDB;

CREATE TABLE clientes (
    id      INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    nombre  VARCHAR(120) NOT NULL,
    correo  VARCHAR(120) NOT NULL UNIQUE,
    telefono VARCHAR(20),
    INDEX idx_clientes_nombre (nombre)
) ENGINE = InnoDB;

CREATE TABLE pedidos (
    id         INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    cliente_id INT UNSIGNED NOT NULL,
    fecha      DATE NOT NULL,
    estado     ENUM('pendiente', 'enviado', 'entregado', 'cancelado') NOT NULL DEFAULT 'pendiente',
    CONSTRAINT fk_pedidos_cliente FOREIGN KEY (cliente_id)
        REFERENCES clientes (id) ON UPDATE CASCADE,
    INDEX idx_pedidos_cliente (cliente_id),
    INDEX idx_pedidos_fecha (fecha)
) ENGINE = InnoDB;

CREATE TABLE detalle_pedidos (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    pedido_id       INT UNSIGNED NOT NULL,
    producto_id     INT UNSIGNED NOT NULL,
    cantidad        INT NOT NULL CHECK (cantidad > 0),
    precio_unitario DECIMAL(12,2) NOT NULL,
    CONSTRAINT fk_detalle_pedido FOREIGN KEY (pedido_id)
        REFERENCES pedidos (id) ON DELETE CASCADE,
    CONSTRAINT fk_detalle_producto FOREIGN KEY (producto_id)
        REFERENCES productos (id) ON UPDATE CASCADE,
    INDEX idx_detalle_pedido (pedido_id),
    INDEX idx_detalle_producto (producto_id)
) ENGINE = InnoDB;

CREATE TABLE usuarios (
    id         INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    nombre     VARCHAR(120) NOT NULL,
    correo     VARCHAR(120) NOT NULL UNIQUE,
    clave_hash VARCHAR(255) NOT NULL,
    rol        ENUM('admin', 'vendedor', 'consultor') NOT NULL DEFAULT 'consultor',
    INDEX idx_usuarios_correo (correo)
) ENGINE = InnoDB;