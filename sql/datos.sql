-- Día 9 — Datos de prueba de ISoT
-- Mínimos exigidos: 20 productos, 10 clientes, 15 pedidos + su detalle.
USE isot;

-- Categorías
INSERT INTO categorias (nombre) VALUES
('Periféricos'), ('Pantallas'), ('Almacenamiento'), ('Audio'), ('Redes');

-- 22 productos
INSERT INTO productos (id, categoria_id, nombre, precio, stock) VALUES
(1,  1, 'Teclado mecánico RGB',      120000.00,  4),
(2,  1, 'Mouse inalámbrico',         65000.00,  32),
(3,  2, 'Monitor 24" Full HD',       680000.00,  7),
(4,  3, 'SSD NVMe 512 GB',           240000.00, 30),
(5,  4, 'Audífonos Bluetooth',       150000.00, 12),
(6,  1, 'Cámara web 1080p',          180000.00,  9),
(7,  5, 'Router WiFi 6',             320000.00,  6),
(8,  5, 'Switch 8 puertos',          140000.00,  3),
(9,  4, 'Auriculares con cable',      45000.00, 20),
(10, 2, 'Monitor 27" 2K',           1150000.00,  0),
(11, 3, 'Disco duro 1 TB',           190000.00,  4),
(12, 1, 'Cable HDMI 2 m',             25000.00, 60),
(13, 1, 'Teclado básico',             40000.00, 15),
(14, 3, 'SSD SATA 240 GB',           130000.00,  8),
(15, 5, 'UPS 650 VA',                380000.00,  2),
(16, 4, 'Micrófono condensador',     290000.00,  5),
(17, 1, 'Mousepad gamer XL',          35000.00, 22),
(18, 2, 'Monitor 32" QHD',          1500000.00,  3),
(19, 3, 'Memoria USB 64 GB',          45000.00, 40),
(20, 5, 'Access Point WiFi 6',        260000.00,  5),
(21, 4, 'Parlante Bluetooth',        110000.00, 11),
(22, 1, 'Webcam 4K',                 350000.00,  4);

-- 12 clientes
INSERT INTO clientes (id, nombre, correo, telefono) VALUES
(1,  'Laura Gómez',   'laura.gomez@gmail.com',   '3001234567'),
(2,  'Andrés Pérez',  'andres.perez@gmail.com',  '3007654321'),
(3,  'María Torres',  'maria.torres@gmail.com',  '3011112233'),
(4,  'Carlos Ruiz',   'carlos.ruiz@gmail.com',   '3023334455'),
(5,  'Lina Castro',   'lina.castro@gmail.com',   '3035556677'),
(6,  'Pedro Mora',    'pedro.mora@gmail.com',    '3047778899'),
(7,  'Diana Ríos',    'diana.rios@gmail.com',    '3059990011'),
(8,  'Jorge Salazar', 'jorge.salazar@gmail.com', '3061112233'),
(9,  'Paola Núñez',   'paola.nunez@gmail.com',   '3073334455'),
(10, 'Sergio Vela',   'sergio.vela@gmail.com',   '3085556677'),
(11, 'Ana Beltrán',   'ana.beltran@gmail.com',   '3097778899'),
(12, 'Tomás Herrera', 'tomas.herrera@gmail.com', '3119990011');

-- 18 pedidos
INSERT INTO pedidos (id, cliente_id, fecha, estado) VALUES
(1,  1, '2026-09-01', 'entregado'),
(2,  2, '2026-09-02', 'entregado'),
(3,  3, '2026-09-03', 'enviado'),
(4,  4, '2026-09-04', 'cancelado'),
(5,  2, '2026-09-05', 'entregado'),
(6,  5, '2026-09-05', 'enviado'),
(7,  6, '2026-09-06', 'entregado'),
(8,  7, '2026-09-07', 'pendiente'),
(9,  8, '2026-09-08', 'entregado'),
(10, 9, '2026-09-09', 'enviado'),
(11, 10,'2026-09-10', 'entregado'),
(12, 1, '2026-09-11', 'entregado'),
(13, 11,'2026-09-12', 'pendiente'),
(14, 12,'2026-09-13', 'enviado'),
(15, 3, '2026-09-14', 'entregado'),
(16, 5, '2026-09-15', 'entregado'),
(17, 6, '2026-09-16', 'enviado'),
(18, 8, '2026-09-17', 'pendiente');

-- Detalle de los pedidos (líneas de cada orden)
INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio_unitario) VALUES
(1, 10, 1, 1150000.00),
(1, 2,  2, 65000.00),
(1, 12, 3, 25000.00),
(2, 3,  1, 680000.00),
(2, 9,  4, 45000.00),
(3, 17, 1, 35000.00),
(4, 4,  1, 240000.00),
(5, 1,  1, 120000.00),
(6, 15, 1, 380000.00),
(7, 8,  1, 140000.00),
(8, 16, 1, 290000.00),
(9, 11, 2, 190000.00),
(10, 14, 1, 130000.00),
(11, 19, 2, 45000.00),
(12, 18, 1, 1500000.00),
(13, 5,  1, 150000.00),
(14, 20, 1, 260000.00),
(15, 13, 2, 40000.00),
(16, 3,  1, 680000.00),
(17, 6,  1, 180000.00),
(18, 21, 1, 110000.00);

-- Usuarios de prueba (nunca se guarda la contraseña; solo el hash password_hash).
-- admin:   carlos.figuera@isot.co / Admin2026*
-- vendedor: vendedor@isot.co      / Vendedor2026*
-- consultor: consultor@isot.co    / Consultor2026*
INSERT INTO usuarios (id, nombre, correo, clave_hash, rol, activo, creado_en) VALUES
(1, 'Carlos Figuera', 'carlos.figuera@isot.co',
 '$2y$10$.oM0RDyc2azE1YYwRefP5O.tR9u59nTNeQDF3cGfNEAqtZQ7Vio6y', 'admin', 1, NOW()),
(2, 'Laura Vendedora', 'vendedor@isot.co',
 '$2y$10$.r4sodyrVJU1rXtrGqPKf.SXqvMhWwdvrTVPBZU6HJIU7dzgqcyUO', 'vendedor', 1, NOW()),
(3, 'Pedro Consultor', 'consultor@isot.co',
 '$2y$10$PDxvd9ODleeSveF/t9SED.Ls0egWoEsa1os7Lm2FSrjfY1W5l4b/i', 'consultor', 1, NOW());