<?php
declare(strict_types=1);

/**
 * Día 13 — Controlador: registrar un pedido con su detalle.
 * Se valida en servidor: cliente existente, al menos una línea, producto
 * válido, cantidad positiva y stock suficiente. Todo se hace dentro de una
 * transacción (registrar()) que también descuenta el stock; si algo falla,
 * se revierte por completo y se informa con el aviso efímero.
 */

require_once __DIR__ . '/../seguridad/guardia.php';
require_once __DIR__ . '/../seguridad/csrf.php';
require_once __DIR__ . '/../seguridad/aviso.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/PedidoModelo.php';

exigirRol('admin', 'vendedor');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

if (!validarCsrf($_POST['csrf'] ?? null)) {
    http_response_code(419);
    exit('Sesión expirada. Vuelve a intentarlo.');
}

$clienteId = filter_input(INPUT_POST, 'cliente_id', FILTER_VALIDATE_INT);
$productosIds = $_POST['producto_id'] ?? [];
$cantidades = $_POST['cantidad'] ?? [];

if (!$clienteId || $clienteId <= 0) {
    guardarAviso('error', 'Seleccione un cliente.');
    redirigirA('pedidos.php');
}

// Arma las líneas del detalle a partir de los arreglos repetidos del formulario.
$items = [];
if (is_array($productosIds) && is_array($cantidades)) {
    foreach ((array) $productosIds as $i => $productoId) {
        $productoId = (int) $productoId;
        $cantidad = isset($cantidades[$i]) ? filter_var($cantidades[$i], FILTER_VALIDATE_INT) : false;

        if ($productoId > 0 && $cantidad !== false && $cantidad > 0) {
            $items[] = ['producto_id' => $productoId, 'cantidad' => $cantidad];
        }
    }
}

if (!$items) {
    guardarAviso('error', 'Agregue al menos un producto con cantidad válida.');
    redirigirA('pedidos.php');
}

try {
    $pedido = new PedidoModelo(Conexion::obtener());
    $id = $pedido->registrar($clienteId, $items);
    guardarAviso('exito', 'Pedido #' . $id . ' registrado; el stock se descontó en la misma transacción.');
} catch (PDOException $e) {
    error_log($e->getMessage());
    if (strpos($e->getMessage(), 'FOREIGN KEY') !== false) {
        guardarAviso('error', 'Uno de los productos seleccionados no es válido.');
    } elseif (strpos($e->getMessage(), 'stock') === false) {
        guardarAviso('error', 'No fue posible registrar el pedido (transacción revertida).');
    } else {
        guardarAviso('error', 'Stock insuficiente para completar el pedido.');
    }
}

redirigirA('pedidos.php');