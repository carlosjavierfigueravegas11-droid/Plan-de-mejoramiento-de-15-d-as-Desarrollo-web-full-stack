<?php
declare(strict_types=1);

/**
 * Día 13 — Controlador: eliminar (borrado lógico) un producto.
 * La acción real es un POST con token CSRF; nunca un enlace GET. El producto
 * se marca activo = 0 para no romper el historial de pedidos. Los pedidos
 * antiguos siguen mostrando el producto tal como se vendió.
 */

require_once __DIR__ . '/../seguridad/guardia.php';
require_once __DIR__ . '/../seguridad/csrf.php';
require_once __DIR__ . '/../seguridad/aviso.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/ProductoModelo.php';

exigirRol('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

if (!validarCsrf($_POST['csrf'] ?? null)) {
    http_response_code(419);
    exit('Sesión expirada. Vuelve a intentarlo.');
}

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    guardarAviso('error', 'Identificador de producto no válido.');
    redirigirA('productos.php');
}

$modelo = new ProductoModelo(Conexion::obtener());

try {
    if ($modelo->desactivar($id)) {
        guardarAviso('exito', 'Producto desactivado. Sigue en el histórico de pedidos.');
    } else {
        guardarAviso('error', 'El producto no existe o ya está desactivado.');
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    guardarAviso('error', 'No fue posible desactivar el producto.');
}

redirigirA('productos.php');