<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once dirname(__DIR__) . '/config/conexion.php';
require_once dirname(__DIR__) . '/modelos/ProductoModelo.php';

try {
    $pdo = Conexion::obtener();
    $metodo = $_SERVER['REQUEST_METHOD'];

    // LEER (listar o buscar)
    if ($metodo === 'GET') {
        $q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
        $productos = $q === ''
            ? listarProductos($pdo)
            : buscarProductos($pdo, $q);
        echo json_encode($productos, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $cuerpo = $metodo === 'POST' || $metodo === 'PUT'
        ? json_decode(file_get_contents('php://input'), true)
        : [];
    if (!is_array($cuerpo)) {
        http_response_code(400);
        echo json_encode(['error' => 'Cuerpo JSON no válido.']);
        exit;
    }

    $nombre = trim((string) ($cuerpo['nombre'] ?? ''));
    $categoria = trim((string) ($cuerpo['categoria'] ?? ''));
    $precio = (float) ($cuerpo['precio'] ?? 0);
    $stock = (int) ($cuerpo['stock'] ?? -1);

    if (($metodo === 'POST' || $metodo === 'PUT')
        && ($nombre === '' || $categoria === '' || !($precio > 0) || $stock < 0)) {
        http_response_code(422);
        echo json_encode(['error' => 'Datos inválidos: nombre, categoría, precio > 0 y stock >= 0.']);
        exit;
    }

    // CREAR
    if ($metodo === 'POST') {
        $id = crearProducto($pdo, $nombre, $categoria, $precio, $stock);
        http_response_code(201);
        echo json_encode(['id' => $id, 'mensaje' => 'Producto creado.']);
        exit;
    }

    // ACTUALIZAR (PUT sobre productos.php?id=N)
    if ($metodo === 'PUT') {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Falta el id del producto.']);
            exit;
        }
        $ok = actualizarProducto($pdo, $id, $nombre, $categoria, $precio, $stock);
        if (!$ok) {
            http_response_code(404);
            echo json_encode(['error' => 'Producto no encontrado.']);
            exit;
        }
        echo json_encode(['id' => $id, 'mensaje' => 'Producto actualizado.']);
        exit;
    }

    // ELIMINAR (DELETE sobre productos.php?id=N) — borrado lógico
    if ($metodo === 'DELETE') {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Falta el id del producto.']);
            exit;
        }
        $ok = eliminarProducto($pdo, $id);
        if (!$ok) {
            http_response_code(404);
            echo json_encode(['error' => 'Producto no encontrado.']);
            exit;
        }
        echo json_encode(['id' => $id, 'mensaje' => 'Producto eliminado (borrado lógico).']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
} catch (DomainException $e) {
    http_response_code(422);
    echo json_encode(['error' => $e->getMessage()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo consultar la base de datos']);
}