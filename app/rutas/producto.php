<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once dirname(__DIR__) . '/config/conexion.php';
require_once dirname(__DIR__) . '/seguridad/sesion.php';
require_once dirname(__DIR__) . '/modelos/ProductoModelo.php';

iniciarSesionSegura();

if (usuarioActual() === null) {
    http_response_code(401);
    echo json_encode(['error' => 'Debes iniciar sesión.']);
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Falta el id del producto.']);
    exit;
}

try {
    $fila = (new ProductoModelo(Conexion::obtener()))->obtener($id);
    if ($fila === null) {
        http_response_code(404);
        echo json_encode(['error' => 'Producto no encontrado.']);
        exit;
    }
    echo json_encode($fila, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo consultar la base de datos']);
}