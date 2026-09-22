<?php
declare(strict_types=1);

/**
 * Día 15 — Vista: Reportes formales con identidad institucional.
 * Tres reportes parametrizados que consumen vistas (v_reportes_*):
 *   1. ventas-categoria  (dona)
 *   2. stock-critico     (barras)
 *   3. pedidos-cliente   (barras)
 * Mismo HTML para pantalla y PDF. Impresión por @media print (css/reporte.css),
 * exportación a CSV (api/exportar-csv.php) y a PDF (api/exportar-pdf.php).
 */

require_once __DIR__ . '/app/seguridad/guardia.php';
require_once __DIR__ . '/app/seguridad/csrf.php';
require_once __DIR__ . '/app/config/conexion.php';
require_once __DIR__ . '/app/modelos/ReporteModelo.php';

if (!puede('admin', 'consultor')) {
    http_response_code(403);
    exit('403 — No tiene permiso para esta operación.');
}

/* ------- filtros ------- */
$modoReporte = htmlspecialchars((string) ($_GET['reporte'] ?? 'ventas-categoria'), ENT_QUOTES, 'UTF-8');
$valoresPermitidos = ['ventas-categoria', 'stock-critico', 'pedidos-cliente'];
if (!in_array($modoReporte, $valoresPermitidos, true)) {
    $modoReporte = 'ventas-categoria';
}

$desde = trim((string) ($_GET['desde'] ?? ''));
$hasta = trim((string) ($_GET['hasta'] ?? ''));
$categoria = trim((string) ($_GET['categoria'] ?? ''));
$filtroAplicado = trim((string) ($_GET['filtro'] ?? ''));

$bdDisponible = false;
$datosReporte = null;
try {
    $pdo = Conexion::obtener();
    $bdDisponible = true;
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
    $reporte = [
        'titulo'    => $datosReporte['titulo'],
        'subtitulo' => SOFTWARE_DESCRIPCION,
        'filtros'   => $filtros,
        'campos'    => $datosReporte['ruta'],
    ];
} catch (PDOException $e) {
    $bdDisponible = false;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reportes — ISoT</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/tokens.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/estilos.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/reporte.css">
    <script src="<?= BASE_URL ?>js/vendor/chart.umd.min.js" defer></script>
</head>
<body class="panel">
    <?php require __DIR__ . '/app/vistas/parciales/cabecera.php'; ?>
    <?php require __DIR__ . '/app/vistas/parciales/menu.php'; ?>

    <main class="contenido">
        <h1>Reportes</h1>

        <!-- selector de reporte + filtros -->
        <section class="formulario reporte-toolbar" aria-label="Configurar reporte">
            <form method="get" action="<?= BASE_URL ?>reportes.php" id="form-reportes">
                <input type="hidden" name="filtro" value="1">
                <div class="linea-pedido">
                    <label for="reporte">Reporte</label>
                    <select id="reporte" name="reporte">
                        <?php foreach ([
                            'ventas-categoria' => 'Ventas por categoría',
                            'stock-critico'    => 'Inventario con stock crítico',
                            'pedidos-cliente'  => 'Pedidos por cliente',
                        ] as $valor => $texto): ?>
                            <option value="<?= $valor ?>" <?= $modoReporte === $valor ? 'selected' : '' ?>><?= htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>

                    <?php if ($modoReporte !== 'stock-critico'): ?>
                        <label for="desde">Desde</label>
                        <input type="date" id="desde" name="desde" value="<?= htmlspecialchars($desde, ENT_QUOTES, 'UTF-8') ?>">
                        <label for="hasta">Hasta</label>
                        <input type="date" id="hasta" name="hasta" value="<?= htmlspecialchars($hasta, ENT_QUOTES, 'UTF-8') ?>">
                    <?php else: ?>
                        <label for="categoria">Categoría</label>
                        <select id="categoria" name="categoria">
                            <option value="">Todas</option>
                            <?php if ($bdDisponible): ?>
                                <?php foreach ($modelo->categorias() as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['nombre'], ENT_QUOTES, 'UTF-8') ?>" <?= $categoria === $cat['nombre'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    <?php endif; ?>

                    <button type="submit">Aplicar filtros</button>
                </div>
            </form>
        </section>

        <section class="listado reporte" id="area-reporte" data-reporte="<?= $modoReporte ?>">
            <?php if (!$bdDisponible): ?>
                <p class="alerta alerta--error" role="status">Sin conexión con la base de datos.</p>
            <?php elseif ($datosReporte !== null): ?>
                <?php require __DIR__ . '/app/vistas/reportes/encabezado.php'; ?>

                <?php
                $tipoGrafico = 'canvas';
                $graficoDataUrl = '';
                require __DIR__ . '/app/vistas/reportes/cuerpo.php';
                ?>

                <!-- exportaciones: PDF (mismo HTML, gráfico incrustado) y CSV -->
                <div class="reporte-acciones no-print">
                    <a class="boton" href="<?= BASE_URL ?>api/exportar-csv.php?reporte=<?= $modoReporte ?>&desde=<?= urlencode($desde) ?>&hasta=<?= urlencode($hasta) ?>&categoria=<?= urlencode($categoria) ?>">Descargar CSV</a>
                    <button type="button" class="boton" id="btn-pdf">Exportar a PDF</button>
                    <button type="button" class="boton" id="btn-imprimir" onclick="window.print()">Imprimir</button>
                </div>

                <?php require __DIR__ . '/app/vistas/reportes/pie.php'; ?>
            <?php endif; ?>
        </section>
    </main>

    <form id="form-pdf" method="post" action="<?= BASE_URL ?>api/exportar-pdf.php" hidden>
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="reporte" value="<?= $modoReporte ?>">
        <input type="hidden" name="desde" value="<?= htmlspecialchars($desde, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="hasta" value="<?= htmlspecialchars($hasta, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="categoria" value="<?= htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="grafico" value="">
    </form>

    <?php require __DIR__ . '/app/vistas/parciales/pie.php'; ?>
    <script src="<?= BASE_URL ?>js/reportes.js" defer></script>
</body>
</html>