<?php
declare(strict_types=1);

/**
 * Día 13 — Controlador: eliminar un cliente (POST + CSRF, nunca GET).
 * Un cliente con pedidos asociados no puede borrarse: la base de datos lo
 * protege con ON DELETE RESTRICT y este controlador lo traduce a un mensaje
 * claro, manteniendo la integridad referencial del historial.
 */

require_once __DIR__ . '/../seguridad/guardia.php';
require_once __DIR__ . '/../seguridad/csrf.php';
require_once __DIR__ . '/../seguridad/aviso.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/ClienteModelo.php';
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

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    guardarAviso('error', 'Identificador de cliente no válido.');
    redirigirA('clientes.php');
}

$pdo = Conexion::obtener();

try {
    $pedidos = new PedidoModelo($pdo);
    if ($pedidos->tienePedidos($id)) {
        guardarAviso('error', 'No se puede eliminar: el cliente tiene pedidos asociados (integridad referencial).');
        redirigirA('clientes.php');
    }

    $cliente = new ClienteModelo($pdo);
    if ($cliente->eliminar($id)) {
        guardarAviso('exito', 'Cliente eliminado correctamente.');
    } else {
        guardarAviso('error', 'El cliente no existe.');
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    guardarAviso('error', 'No fue posible eliminar el cliente.');
}

redirigirA('clientes.php');