-- Reset de la base a la semilla para correr pruebas repetibles:
-- vacía las tablas que repuebla datos.sql (categorías, productos, clientes,
-- pedidos, detalle y usuarios de prueba con id fijo).
USE isot;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE detalle_pedidos;
TRUNCATE TABLE pedidos;
TRUNCATE TABLE productos;
TRUNCATE TABLE clientes;
TRUNCATE TABLE categorias;
DELETE FROM usuarios WHERE id IN (1, 2, 3);
SET FOREIGN_KEY_CHECKS = 1;