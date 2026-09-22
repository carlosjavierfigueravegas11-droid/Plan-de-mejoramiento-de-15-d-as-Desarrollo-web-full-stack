<?php
declare(strict_types=1);

/**
 * Día 15 — Exportación a PDF con Dompdf (instalado por Composer).
 * Genera el MISMO HTML que se ve en pantalla (app/vistas/reportes/cuerpo.php)
 * y embebe:
 *   - el logo en PNG base64 (LOGO_PNG de APP, incrustado sin red);
 *   - el gráfico de Chart.js como imagen, porque canvas.toDataURL() convierte
 *     el canvas en una data:image/png incrustable dentro del HTML remitido.
 * Se reutiliza css/reporte.css (su @media print se ignora aquí; Dompdf usa las
 * reglas base y @page del propio motor).
 */

require_once __DIR__ . '/../app/seguridad/guardia.php';
require_once __DIR__ . '/../app/seguridad/csrf.php';
require_once __DIR__ . '/../app/config/conexion.php';
require_once __DIR__ . '/../app/modelos/ReporteModelo.php';

use Dompdf\Dompdf;

if (!puede('admin', 'consultor')) {
    http_response_code(403);
    exit('403 — No tiene permiso.');
}

/* Solo método POST con token CSRF (el gráfico viaja en el cuerpo). */
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validarCsrf((string) ($_POST['csrf'] ?? ''))) {
    http_response_code(400);
    exit('Solicitud de exportación inválida.');
}

$modoReporte = (string) ($_POST['reporte'] ?? 'ventas-categoria');
$reporteConfig = [
    'ventas-categoria' => ['titulo' => 'Reporte de ventas por categoría', 'archivo' => 'ventas-por-categoria'],
    'stock-critico'    => ['titulo' => 'Reporte de inventario con stock crítico', 'archivo' => 'inventario-stock-critico'],
    'pedidos-cliente'  => ['titulo' => 'Reporte de pedidos por cliente', 'archivo' => 'pedidos-por-cliente'],
];
if (!isset($reporteConfig[$modoReporte])) {
    http_response_code(400);
    exit('Reporte no válido.');
}
$nombreArchivo = $reporteConfig[$modoReporte]['archivo'];

$desde = trim((string) ($_POST['desde'] ?? ''));
$hasta = trim((string) ($_POST['hasta'] ?? ''));
$categoria = trim((string) ($_POST['categoria'] ?? ''));

/* El gráfico llega como data:image/png;base64,.... Validar prefijo únicamente:
 * el resto solo puede ser otro asset del mismo reporte (no se concatena nada). */
$graficoRaw = (string) ($_POST['grafico'] ?? '');
$graficoDataUrl = '';
if ($graficoRaw !== '') {
    if (preg_match('#^data:image/png;base64,[A-Za-z0-9+/=]+$#', $graficoRaw)) {
        $graficoDataUrl = $graficoRaw;
    } else {
        http_response_code(400);
        exit('Imagen de gráfico no válida.');
    }
}

$pdo = Conexion::obtener();
$modelo = new ReporteModelo($pdo);

switch ($modoReporte) {
    case 'stock-critico':
        $datosReporte = $modelo->stockCritico($categoria);
        break;
    case 'pedidos-cliente':
        $datosReporte = $modelo->pedidosPorCliente($desde, $hasta);
        break;
    default:
        $datosReporte = $modelo->ventasPorCategoria($desde, $hasta);
}

$filtros = [];
if ($desde !== '' || $hasta !== '') {
    $filtros['Rango'] = ($desde !== '' ? $desde : 'inicio') . ' — ' . ($hasta !== '' ? $hasta : 'hoy');
}
if ($categoria !== '') {
    $filtros['Categoría'] = $categoria;
}

/* $modoReporte es la clave esperada por cuerpo.php para pintar la fila de totales. */
$modoReporte = $datosReporte['ruta'];

/* ---------- Montamos el mismo HTML de la pantalla ---------- */
ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($reporteConfig[$modoReporte]['titulo'], ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/reporte.css">
</head>
<body>
    <main class="contenido reporte">
        <?php
        encabezadoReporte($datosReporte['titulo'], SOFTWARE_DESCRIPCION, $filtros, date('d/m/Y \a \l\a\s H:i'));

        $tipoGrafico = 'img';
        require __DIR__ . '/../app/vistas/reportes/cuerpo.php';

        pieReporte();
        ?>
    </main>
</body>
</html>
<?php $html = ob_get_clean();

/* css/reporte.css contenido en el HTML para que Dompdf lo procese sin red. */
$css = file_get_contents(__DIR__ . '/../css/reporte.css');
$html = str_replace(
    '<link rel="stylesheet" href="' . BASE_URL . 'css/reporte.css">',
    '<style>' . $css . '</style>',
    $html
);

require_once __DIR__ . '/../vendor/autoload.php';

$dompdf = new Dompdf(['isRemoteEnabled' => false, 'isHtml5ParserEnabled' => true]);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '.pdf"');
header('Cache-Control: no-store');
echo $dompdf->output();
exit;