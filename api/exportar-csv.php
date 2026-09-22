<?php
declare(strict_types=1);

/**
 * Día 15 — Exportación CSV de reportes con fputcsv y marcador BOM UTF-8.
 * El BOM (\xEF\xBB\xBF) hace que Excel reconozca el archivo como UTF-8 y
 * respete los acentos. Los datos salen de las mismas vistas que la pantalla.
 */

require_once __DIR__ . '/../app/seguridad/guardia.php';
require_once __DIR__ . '/../app/config/conexion.php';
require_once __DIR__ . '/../app/modelos/ReporteModelo.php';

if (!puede('admin', 'consultor')) {
    http_response_code(403);
    exit('403 — No tiene permiso.');
}

$reporte = $_GET['reporte'] ?? 'ventas-categoria';
if (!in_array($reporte, ['ventas-categoria', 'stock-critico', 'pedidos-cliente'], true)) {
    http_response_code(400);
    exit('Reporte no válido.');
}

$desde = trim((string) ($_GET['desde'] ?? ''));
$hasta = trim((string) ($_GET['hasta'] ?? ''));
$categoria = trim((string) ($_GET['categoria'] ?? ''));

$pdo = Conexion::obtener();
$modelo = new ReporteModelo($pdo);

switch ($reporte) {
    case 'stock-critico':
        $datos = $modelo->stockCritico($categoria);
        $nombre = 'inventario-stock-critico.csv';
        break;
    case 'pedidos-cliente':
        $datos = $modelo->pedidosPorCliente($desde, $hasta);
        $nombre = 'pedidos-por-cliente.csv';
        break;
    default:
        $datos = $modelo->ventasPorCategoria($desde, $hasta);
        $nombre = 'ventas-por-categoria.csv';
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $nombre . '"');
header('Cache-Control: no-store');

// Marcador BOM UTF-8 para que Excel respete los acentos.
echo "\xEF\xBB\xBF";

$salida = fopen('php://output', 'w');

// Bloque 1: identidad del reporte (mismas constantes centralizadas de APP).
fputcsv($salida, [SOFTWARE_NOMBRE, EMPRESA_NOMBRE, 'NIT ' . EMPRESA_NIT]);
fputcsv($salida, [$datos['titulo']]);

// Bloque de encabezado: qué contiene el CSV (clave, valor).
$filaFiltros = ['Filtros'];
if ($categoria !== '') $filaFiltros[] = 'Categoría: ' . $categoria;
if ($desde !== '' || $hasta !== '') $filaFiltros[] = 'Rango: ' . ($desde ?: 'inicio') . ' - ' . ($hasta ?: 'hoy');
if ($filaFiltros === ['Filtros']) $filaFiltros[] = 'SIN FILTROS';
fputcsv($salida, $filaFiltros);
fputcsv($salida, ['Generado', date('Y-m-d H:i:s'), 'Usuario', $_SESSION['usuario']['nombre'] ?? '']);
fputcsv($salida, []);

// Bloque 2: cabecera + datos.
fputcsv($salida, $datos['columnas']);
foreach ($datos['filas'] as $fila) {
    fputcsv($salida, array_values($fila));
}

// Bloque 3: fila de totales.
$totales = [];
foreach ($datos['totales'] as $clave => $valor) {
    $totales[] = (ucfirst(str_replace('_', ' ', (string) $clave)) . ': ' . number_format((float) $valor, 2, ',', '.'));
}
fputcsv($salida, []);
fputcsv($salida, array_merge(['TOTALES'], $totales));

// Bloque 4: nota de confidencialidad.
fputcsv($salida, []);
fputcsv($salida, ['Confidencialidad: documento de uso interno de ' . EMPRESA_NOMBRE]);

fclose($salida);
exit;