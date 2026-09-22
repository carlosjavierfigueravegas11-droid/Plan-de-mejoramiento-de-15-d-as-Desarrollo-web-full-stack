<?php
declare(strict_types=1);

/**
 * Día 13 — Controlador: guardar (crear o actualizar) un cliente.
 * Validación en servidor: nombre obligatorio, documento y correo con formato
 * básico y unicidad respetada por la base de datos (UNIQUE). El controlador
 * traduce el error 23000 a un mensaje legible: "documento o correo ya existen".
 */

require_once __DIR__ . '/../seguridad/guardia.php';
require_once __DIR__ . '/../seguridad/csrf.php';
require_once __DIR__ . '/../seguridad/aviso.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/ClienteModelo.php';

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
$documento = trim((string) ($_POST['documento'] ?? ''));
$correo = trim((string) ($_POST['correo'] ?? ''));
$telefono = trim((string) ($_POST['telefono'] ?? ''));
$id = (int) ($_POST['id'] ?? 0);

if (mb_strlen($nombre) < 3) {
    $errores[] = 'El nombre debe tener al menos 3 caracteres.';
}
if ($documento === '') {
    $errores[] = 'El documento es obligatorio.';
}
if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $errores[] = 'El correo debe ser una dirección válida.';
}

if ($errores) {
    guardarAviso('error', implode(' ', $errores));
    redirigirA('clientes.php');
}

$modelo = new ClienteModelo(Conexion::obtener());

try {
    if ($id > 0) {
        $ok = $modelo->actualizar($id, $nombre, $documento, $correo, $telefono);
    } else {
        $id = $modelo->crear($nombre, $documento, $correo, $telefono);
        $ok = $id !== false;
    }

    if ($ok === false) {
        guardarAviso('error', 'El documento o el correo ya están registrados. Usa datos únicos.');
    } else {
        guardarAviso('exito', 'Cliente guardado correctamente.');
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    guardarAviso('error', 'No fue posible guardar el cliente.');
}

redirigirA('clientes.php');