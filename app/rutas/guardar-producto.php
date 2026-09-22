<?php
declare(strict_types=1);

/**
 * Día 13 — Controlador: guardar (crear o actualizar) un producto.
 * Patrón POST / Redirect / GET: al terminar se redirige con 303 a productos.php
 * con el aviso efímero en sesión. Si el usuario recarga, el navegador hace un
 * GET y no se duplica el registro. La validación vive aquí, en el servidor:
 * funciona aunque JavaScript esté desactivado.
 */

require_once __DIR__ . '/../seguridad/guardia.php';
require_once __DIR__ . '/../seguridad/csrf.php';
require_once __DIR__ . '/../seguridad/aviso.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/ProductoModelo.php';

exigirRol('admin', 'vendedor');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

if (!validarCsrf($_POST['csrf'] ?? null)) {
    http_response_code(419);
    exit('Sesión expirada. Vuelve a intentarlo.');
}

$errores = [];

$nombre = trim((string) ($_POST['nombre'] ?? ''));
$precio = filter_input(INPUT_POST, 'precio', FILTER_VALIDATE_FLOAT);
$stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
$categoriaId = filter_input(INPUT_POST, 'categoria_id', FILTER_VALIDATE_INT);
$id = (int) ($_POST['id'] ?? 0);

if (mb_strlen($nombre) < 3) {
    $errores[] = 'El nombre debe tener al menos 3 caracteres.';
}
if ($precio === false || $precio <= 0) {
    $errores[] = 'El precio debe ser mayor que cero.';
}
if ($stock === false || $stock < 0) {
    $errores[] = 'El stock no puede ser negativo.';
}
if (!$categoriaId || $categoriaId <= 0) {
    $errores[] = 'Seleccione una categoría.';
}

if ($errores) {
    guardarAviso('error', implode(' ', $errores));
    redirigirA('productos.php');
}

$modelo = new ProductoModelo(Conexion::obtener());

try {
    if ($id > 0) {
        $modelo->actualizar($id, $categoriaId, $nombre, (float) $precio, (int) $stock);
        guardarAviso('exito', 'Producto actualizado correctamente.');
    } else {
        $modelo->crear($categoriaId, $nombre, (float) $precio, (int) $stock);
        guardarAviso('exito', 'Producto creado correctamente.');
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    guardarAviso('error', 'No fue posible guardar el producto.');
}

redirigirA('productos.php');