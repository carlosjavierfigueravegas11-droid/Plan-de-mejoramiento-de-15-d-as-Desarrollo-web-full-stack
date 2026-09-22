-- Día 9 — Consultas de verificación de la base de datos relacional de ISoT
USE isot;

-- Consulta 1 (JOIN entre Productos y Categorías)
SELECT p.id, p.nombre, c.nombre AS categoria, p.precio, p.stock
FROM productos p
INNER JOIN categorias c ON p.categoria_id = c.id
ORDER BY p.nombre ASC;

-- Consulta 2 (Agrupamiento con GROUP BY y SUM)
SELECT c.nombre AS categoria, COUNT(p.id) AS total_productos, SUM(p.stock) AS unidades_totales
FROM categorias c
LEFT JOIN productos p ON c.id = p.categoria_id
GROUP BY c.id, c.nombre;

-- Consulta 3 (Totalización de Pedidos con SUM y JOIN múltiple)
SELECT ped.id AS pedido_id, cl.nombre AS cliente, ped.fecha, ped.estado,
       SUM(dp.cantidad * dp.precio_unitario) AS total_calculado
FROM pedidos ped
INNER JOIN clientes cl ON ped.cliente_id = cl.id
INNER JOIN detalle_pedidos dp ON ped.id = dp.pedido_id
GROUP BY ped.id, cl.nombre, ped.fecha, ped.estado;