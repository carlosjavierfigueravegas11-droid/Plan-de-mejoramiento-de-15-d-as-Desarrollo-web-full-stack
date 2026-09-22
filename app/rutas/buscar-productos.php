<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once dirname(__DIR__) . '/config/conexion.php';
require_once dirname(__DIR__) . '/modelos/ProductoModelo.php';

$texto = isset($_GET['q']) ? (string) $_GET['q'] : '';
$texto = trim($texto);

try {
    $productos = (new ProductoModelo(Conexion::obtener()))->listar($texto, 1, 1000);
    echo json_encode($productos, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo consultar la base de datos']);
}