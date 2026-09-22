<?php
declare(strict_types=1);

/**
 * Día 15 — Cuerpo de reporte reutilizable (pantalla y PDF usan ESTE mismo HTML).
 * Recibe $datosReporte (resultado de ReporteModelo) y $reporte (clave).
 * $tipoGrafico: 'canvas' (pantalla, lo dibuja Chart.js) o 'img' (PDF, incrusta
 * la imagen base64 que llega en $graficoDataUrl desde canvas.toDataURL()).
 */

function formatoPeso(int|float $valor): string
{
    return '$ ' . number_format((float) $valor, 0, ',', '.');
}

$columnas = $datosReporte['columnas'];
$filas = $datosReporte['filas'];
$totales = $datosReporte['totales'];
$tieneGrafico = ($datosReporte['grafico']['valores'] ?? []) !== [];
?>
<?php if ($tieneGrafico): ?>
    <div class="reporte-grafico">
        <?php if ($tipoGrafico === 'img'): ?>
            <img src="<?= $graficoDataUrl ?>" alt="Gráfico del reporte" class="reporte-grafico-imagen">
        <?php else: ?>
            <canvas id="grafico-reporte" role="img"
                    aria-label="Gráfico del reporte"
                    data-tipo="<?= htmlspecialchars((string) ($datosReporte['grafico']['tipo'] ?? 'bar'), ENT_QUOTES, 'UTF-8') ?>"
                    data-ejes="<?= ($datosReporte['grafico']['horizontal'] ?? false) ? 'horizontal' : '' ?>"
                    data-etiquetas='<?= json_encode($datosReporte['grafico']['etiquetas'] ?? [], JSON_UNESCAPED_UNICODE) ?>'
                    data-valores='<?= json_encode($datosReporte['grafico']['valores'] ?? [], JSON_UNESCAPED_UNICODE) ?>'></canvas>
        <?php endif; ?>
    </div>
<?php else: ?>
    <p class="reporte-vacio">Sin datos para los filtros seleccionados.</p>
<?php endif; ?>

<div class="tabla-reporte">
    <table>
        <caption>Detalle de <?= htmlspecialchars((string) $datosReporte['titulo'], ENT_QUOTES, 'UTF-8') ?></caption>
        <thead>
            <tr>
                <?php foreach ($columnas as $col): ?>
                    <th scope="col"><?= htmlspecialchars((string) $col, ENT_QUOTES, 'UTF-8') ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($filas as $fila): ?>
                <tr>
                    <?php foreach ($fila as $clave => $valor): ?>
                        <?php $numerico = is_numeric($valor) && strpos((string) $valor, '.') !== false && !in_array($clave, ['id', 'stock', 'pedidos'], true); ?>
                        <?php if ($numerico): ?>
                            <td><?= formatoPeso((float) $valor) ?></td>
                        <?php else: ?>
                            <td><?= htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8') ?></td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <?php if ($modoReporte === 'ventas-categoria'): ?>
                <tr>
                    <th scope="row">TOTALES</th>
                    <td><?= number_format((int) $totales['unidades'], 0, ',', '.') ?></td>
                    <td><?= formatoPeso((float) $totales['total']) ?></td>
                </tr>
            <?php elseif ($modoReporte === 'stock-critico'): ?>
                <tr>
                    <th scope="row" colspan="3">TOTALES</th>
                    <td><?= (int) $totales['productos'] ?> productos · <?= (int) $totales['piezas'] ?> piezas</td>
                </tr>
            <?php else: ?>
                <tr>
                    <th scope="row" colspan="2">TOTALES</th>
                    <td><?= (int) $totales['pedidos'] ?></td>
                    <td><?= formatoPeso((float) $totales['total']) ?></td>
                </tr>
            <?php endif; ?>
        </tfoot>
    </table>
</div>