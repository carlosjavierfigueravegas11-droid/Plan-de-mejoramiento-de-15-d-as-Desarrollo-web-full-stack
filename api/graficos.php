<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/seguridad/guardia.php';
require_once __DIR__ . '/../app/config/conexion.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = Conexion::obtener();

// Deja un resumen calculado una sola vez: últimos X días o todo el histórico.
$rango = isset($_GET['rango']) ? (int) $_GET['rango'] : 30;
$rango = in_array($rango, [7, 30, 90], true) ? $rango : 30;
$limite = date('Y-m-d', strtotime("-{$rango} days"));

$meses = $pdo->query(
    "SELECT periodo, cantidad_pedidos, total_vendido
     FROM v_ventas_mes ORDER BY periodo ASC"
)->fetchAll();

$categorias = $pdo->query(
    "SELECT categoria, total_vendido, unidades
     FROM v_ventas_categoria WHERE total_vendido > 0
     ORDER BY total_vendido DESC"
)->fetchAll();

$porDiaMes = $pdo->query(
    "SELECT dia, cantidad FROM v_pedidos_por_dia ORDER BY dia ASC"
)->fetchAll();

$serie = $pdo->prepare(
    "SELECT fecha, cantidad_pedidos, total_vendido
     FROM v_ventas_por_dia WHERE fecha >= :limite ORDER BY fecha ASC"
);
$serie->execute([':limite' => $limite]);
$serie = $serie->fetchAll();

$stockCritico = $pdo->query(
    "SELECT id, nombre, categoria, stock FROM v_stock_critico ORDER BY stock ASC"
)->fetchAll();

$recientes = $pdo->query(
    "SELECT pedido_id, cliente, fecha, estado, total
     FROM v_pedidos_recientes LIMIT 8"
)->fetchAll();

$resumenStmt = $pdo->prepare(
    "SELECT
        (SELECT COUNT(*) FROM productos WHERE activo = 1) AS productos_activos,
        (SELECT COUNT(*) FROM pedidos
            WHERE estado <> 'cancelado' AND fecha >= :l1) AS pedidos_rango,
        (SELECT COALESCE(SUM(dp.cantidad * dp.precio_unitario), 0)
            FROM detalle_pedidos dp
            INNER JOIN pedidos p ON p.id = dp.pedido_id
            WHERE p.estado <> 'cancelado' AND p.fecha >= :l2) AS ventas_rango,
        (SELECT COUNT(*) FROM productos WHERE activo = 1 AND stock < 5) AS stock_critico
    "
);
$resumenStmt->execute([':l1' => $limite, ':l2' => $limite]);
$resumen = $resumenStmt->fetch();

echo json_encode([
    'generado' => date('H:i:s'),
    'rango'    => $rango,
    'resumen'  => [
        'productos' => (int) $resumen['productos_activos'],
        'pedidos'   => (int) $resumen['pedidos_rango'],
        'ventas'    => (float) $resumen['ventas_rango'],
        'stock'     => (int) $resumen['stock_critico'],
    ],
    'ventasMes' => [
        'etiquetas' => array_column($meses, 'periodo'),
        'pedidos'   => array_map('intval', array_column($meses, 'cantidad_pedidos')),
        'valores'   => array_map('floatval', array_column($meses, 'total_vendido')),
    ],
    'categorias' => [
        'etiquetas' => array_column($categorias, 'categoria'),
        'valores'   => array_map('floatval', array_column($categorias, 'total_vendido')),
        'unidades'  => array_map('intval', array_column($categorias, 'unidades')),
    ],
    'pedidosPorDia' => [
        'etiquetas' => array_map('strval', array_column($porDiaMes, 'dia')),
        'valores'   => array_map('intval', array_column($porDiaMes, 'cantidad')),
    ],
    'serieVentas' => [
        'etiquetas' => array_column($serie, 'fecha'),
        'pedidos'   => array_map('intval', array_column($serie, 'cantidad_pedidos')),
        'valores'   => array_map('floatval', array_column($serie, 'total_vendido')),
    ],
    'stockCritico' => $stockCritico,
    'recientes'   => $recientes,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);