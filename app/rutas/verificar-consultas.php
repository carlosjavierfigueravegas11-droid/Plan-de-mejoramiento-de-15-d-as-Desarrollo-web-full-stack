<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/conexion.php';

header('Content-Type: text/html; charset=utf-8');
$pdo = Conexion::obtener();

$consultas = [
    'Consulta 1 — JOIN entre Productos y Categorías' => "
        SELECT p.id, p.nombre, c.nombre AS categoria, p.precio, p.stock
        FROM productos p
        INNER JOIN categorias c ON p.categoria_id = c.id
        ORDER BY p.nombre ASC;",
    'Consulta 2 — Agrupamiento con GROUP BY y SUM' => "
        SELECT c.nombre AS categoria, COUNT(p.id) AS total_productos, SUM(p.stock) AS unidades_totales
        FROM categorias c
        LEFT JOIN productos p ON c.id = p.categoria_id
        GROUP BY c.id, c.nombre;",
    'Consulta 3 — Totalización de Pedidos con SUM y JOIN múltiple' => "
        SELECT ped.id AS pedido_id, cl.nombre AS cliente, ped.fecha, ped.estado,
               SUM(dp.cantidad * dp.precio_unitario) AS total_calculado
        FROM pedidos ped
        INNER JOIN clientes cl ON ped.cliente_id = cl.id
        INNER JOIN detalle_pedidos dp ON ped.id = dp.pedido_id
        GROUP BY ped.id, cl.nombre, ped.fecha, ped.estado;",
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verificación SQL — ISoT (Día 9)</title>
    <link rel="stylesheet" href="../css/tokens.css">
    <link rel="stylesheet" href="../css/estilos.css">
    <style>
        body { padding: 1.5rem; }
        pre {
            background: #1A1A1A; color: #FFF3C4;
            padding: 1rem; border-radius: 8px; overflow-x: auto;
            font-size: 0.85rem; line-height: 1.4;
        }
        .resultado { margin: 1rem 0 2rem; }
        table { min-width: 100%; }
    </style>
</head>
<body>
    <h1>Verificación de consultas — base de datos <code>isot</code></h1>
    <?php foreach ($consultas as $titulo => $sql): $filas = $pdo->query($sql)->fetchAll(); ?>
        <section class="resultado">
            <h2><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h2>
            <pre><?= htmlspecialchars($sql, ENT_QUOTES, 'UTF-8') ?></pre>
            <?php if ($filas): $columnas = array_keys($filas[0]); ?>
                <table>
                    <caption><?= count($filas) ?> fila(s)</caption>
                    <thead>
                        <tr><?php foreach ($columnas as $col): ?><th scope="col"><?= htmlspecialchars($col, ENT_QUOTES, 'UTF-8') ?></th><?php endforeach; ?></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filas as $fila): ?>
                        <tr><?php foreach ($columnas as $col): ?><td><?= htmlspecialchars((string) $fila[$col], ENT_QUOTES, 'UTF-8') ?></td><?php endforeach; ?></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Sin resultados.</p>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</body>
</html>