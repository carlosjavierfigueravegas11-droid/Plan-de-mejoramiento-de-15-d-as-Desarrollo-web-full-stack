-- Vistas para el tablero dinámico (agregaciones consolidadas).
-- Regla de negocio centralizada en la base de datos, no en el PHP.

-- Ventas consolidadas por mes (barras): periodo YYYY-MM, pedidos no cancelados.
CREATE OR REPLACE VIEW v_ventas_mes AS
SELECT
    DATE_FORMAT(p.fecha, '%Y-%m') AS periodo,
    COUNT(DISTINCT p.id)          AS cantidad_pedidos,
    COALESCE(SUM(dp.cantidad), 0) AS unidades,
    COALESCE(SUM(dp.cantidad * dp.precio_unitario), 0) AS total_vendido
FROM pedidos p
INNER JOIN detalle_pedidos dp ON dp.pedido_id = p.id
WHERE p.estado <> 'cancelado'
GROUP BY DATE_FORMAT(p.fecha, '%Y-%m');

-- Participación por categoría (dona): LEFT JOIN para no perder categorías sin ventas.
CREATE OR REPLACE VIEW v_ventas_categoria AS
SELECT
    c.id   AS categoria_id,
    c.nombre AS categoria,
    COALESCE(SUM(dp.cantidad), 0) AS unidades,
    COALESCE(SUM(dp.cantidad * dp.precio_unitario), 0) AS total_vendido
FROM categorias c
LEFT JOIN productos pr ON pr.categoria_id = c.id
LEFT JOIN detalle_pedidos dp ON dp.producto_id = pr.id
LEFT JOIN pedidos p ON p.id = dp.pedido_id AND p.estado <> 'cancelado'
GROUP BY c.id, c.nombre;

-- Pedidos por día del mes en curso (tendencia de periodo corto).
CREATE OR REPLACE VIEW v_pedidos_por_dia AS
SELECT
    DAY(p.fecha) AS dia,
    COUNT(*)     AS cantidad
FROM pedidos p
WHERE p.estado <> 'cancelado'
  AND YEAR(p.fecha) = YEAR(CURDATE())
  AND MONTH(p.fecha) = MONTH(CURDATE())
GROUP BY DAY(p.fecha);

-- Pedidos por día (serie completa de fechas para sparklines y área según rango).
CREATE OR REPLACE VIEW v_ventas_por_dia AS
SELECT
    p.fecha AS fecha,
    COUNT(DISTINCT p.id) AS cantidad_pedidos,
    COALESCE(SUM(dp.cantidad * dp.precio_unitario), 0) AS total_vendido
FROM pedidos p
INNER JOIN detalle_pedidos dp ON dp.pedido_id = p.id
WHERE p.estado <> 'cancelado'
GROUP BY p.fecha;

-- Últimos pedidos con cliente y total (para la actividad reciente del tablero).
CREATE OR REPLACE VIEW v_pedidos_recientes AS
SELECT
    p.id AS pedido_id,
    c.nombre AS cliente,
    DATE_FORMAT(p.fecha, '%d/%m') AS fecha,
    p.estado,
    COALESCE(SUM(dp.cantidad * dp.precio_unitario), 0) AS total
FROM pedidos p
INNER JOIN clientes c ON c.id = p.cliente_id
LEFT JOIN detalle_pedidos dp ON dp.pedido_id = p.id
WHERE p.estado <> 'cancelado'
GROUP BY p.id, c.nombre, p.fecha, p.estado
ORDER BY p.fecha DESC, p.id DESC;

-- Productos bajo el punto de reposición (criterio del negocio: menos de 5 unidades).
CREATE OR REPLACE VIEW v_stock_critico AS
SELECT pr.id, c.id AS categoria_id, pr.nombre, c.nombre AS categoria, pr.stock
FROM productos pr
INNER JOIN categorias c ON c.id = pr.categoria_id
WHERE pr.activo = 1 AND pr.stock < 5;

-- Clientes con mayor compra acumulada (ranking de mejor cliente en pesos).
-- LEFT JOIN para que también se listeen los clientes que aún no compran.
CREATE OR REPLACE VIEW v_clientes_mayor_compra AS
SELECT
    c.id          AS cliente_id,
    c.nombre      AS cliente,
    c.documento   AS documento,
    COUNT(DISTINCT p.id) AS cantidad_pedidos,
    COALESCE(SUM(dp.cantidad * dp.precio_unitario), 0) AS total_comprado
FROM clientes c
LEFT JOIN pedidos p ON p.cliente_id = c.id AND p.estado <> 'cancelado'
LEFT JOIN detalle_pedidos dp ON dp.pedido_id = p.id
GROUP BY c.id, c.nombre, c.documento;

-- =====================================================================
-- VISTAS PARA REPORTES (Día 15): detalle con fecha para filtrar por rango.
-- El reporte agrega sobre estas vistas; el SQL de negocio queda en la BD.
-- =====================================================================

-- Líneas de venta con categoría (reporte "ventas por categoría").
-- A diferencia de v_ventas_categoria (todo el histórico), esta conserva la
-- fecha para filtrar por rango y luego reagrupar en el reporte.
CREATE OR REPLACE VIEW v_reportes_ventas_categoria AS
SELECT
    p.fecha               AS fecha,
    c.id                  AS categoria_id,
    c.nombre              AS categoria,
    dp.producto_id        AS producto_id,
    pr.nombre             AS producto,
    dp.cantidad           AS unidades,
    dp.cantidad * dp.precio_unitario AS subtotal
FROM pedidos p
INNER JOIN detalle_pedidos dp ON dp.pedido_id = p.id
INNER JOIN productos pr ON pr.id = dp.producto_id
INNER JOIN categorias c ON c.id = pr.categoria_id
WHERE p.estado <> 'cancelado';

-- Pedidos con cliente y total (reporte "pedidos por cliente").
CREATE OR REPLACE VIEW v_reportes_pedidos_cliente AS
SELECT
    p.id       AS pedido_id,
    p.fecha    AS fecha,
    c.id       AS cliente_id,
    c.nombre   AS cliente,
    c.documento AS documento,
    p.estado,
    COALESCE(SUM(dp.cantidad * dp.precio_unitario), 0) AS total
FROM pedidos p
INNER JOIN clientes c ON c.id = p.cliente_id
LEFT JOIN detalle_pedidos dp ON dp.pedido_id = p.id
WHERE p.estado <> 'cancelado'
GROUP BY p.id, p.fecha, c.id, c.nombre, c.documento, p.estado;