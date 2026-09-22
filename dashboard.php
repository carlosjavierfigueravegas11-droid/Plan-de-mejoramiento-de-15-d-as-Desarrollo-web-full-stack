<?php
declare(strict_types=1);

include_once __DIR__ . '/app/config/app.php';

require_once __DIR__ . '/app/seguridad/guardia.php';
require_once __DIR__ . '/app/config/conexion.php';

$pdo = Conexion::obtener();

// Indicadores iniciales (los anima js/graficos.js al cargar; se refrescan con el rango).
$sql = "SELECT
    (SELECT COUNT(*) FROM productos WHERE activo = 1) AS productos_activos,
    (SELECT COUNT(*) FROM pedidos
        WHERE estado <> 'cancelado'
          AND YEAR(fecha) = YEAR(CURDATE())
          AND MONTH(fecha) = MONTH(CURDATE())) AS pedidos_mes,
    (SELECT COALESCE(SUM(dp.cantidad * dp.precio_unitario), 0)
        FROM detalle_pedidos dp
        INNER JOIN pedidos p ON p.id = dp.pedido_id
        WHERE p.estado <> 'cancelado'
          AND YEAR(p.fecha) = YEAR(CURDATE())
          AND MONTH(p.fecha) = MONTH(CURDATE())) AS ventas_mes,
    (SELECT COUNT(*) FROM productos WHERE activo = 1 AND stock < 5) AS stock_critico";
$indicadores = $pdo->query($sql)->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tablero — ISoT</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/tokens.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/estilos.css">
</head>
<body class="panel">
    <?php require __DIR__ . '/app/vistas/parciales/cabecera.php'; ?>
    <?php require __DIR__ . '/app/vistas/parciales/menu.php'; ?>

    <main class="contenido">
        <?php require __DIR__ . '/app/vistas/parciales/titulo.php'; ?>

        <section class="cabecera-tablero" aria-label="Controles del tablero">
            <div>
                <h2 class="titulo-tablero">Tablero en vivo</h2>
                <p class="estado-vivo">
                    <span class="punto-vivo" aria-hidden="true"></span>
                    Actualizado <time id="sello-hora">–</time>
                    <button type="button" class="enlace-refrescar" id="boton-refrescar">Refrescar</button>
                </p>
            </div>
            <div class="rango" id="selector-rango" role="group" aria-label="Periodo del resumen">
                <button type="button" data-rango="7" aria-pressed="false">7 días</button>
                <button type="button" data-rango="30" aria-pressed="true" class="activo">30 días</button>
                <button type="button" data-rango="90" aria-pressed="false">90 días</button>
            </div>
        </section>

        <section class="indicadores" aria-label="Indicadores clave">
            <h2 class="sr-only-resumen" id="resumen-titulo">Resumen · últimos 30 días</h2>

            <article class="indicador indicador--exito">
                <h3>Productos activos</h3>
                <p data-conteo="productos"><?= (int) $indicadores['productos_activos'] ?></p>
                <div class="spark" data-spark="productos" aria-hidden="true"></div>
            </article>

            <article class="indicador indicador--informativo">
                <h3>Pedidos del periodo</h3>
                <p data-conteo="pedidos"><?= (int) $indicadores['pedidos_mes'] ?></p>
                <div class="spark" data-spark="pedidos" aria-hidden="true"></div>
            </article>

            <article class="indicador indicador--advertencia">
                <h3>Ventas del periodo</h3>
                <p data-conteo="ventas"><?= '$ ' . number_format((float) $indicadores['ventas_mes'], 0, ',', '.') ?></p>
                <div class="spark" data-spark="ventas" aria-hidden="true"></div>
            </article>

            <article class="indicador indicador--exito">
                <h3>Stock crítico</h3>
                <p data-conteo="stock"><?= (int) $indicadores['stock_critico'] ?></p>
                <div class="spark" data-spark="stock" aria-hidden="true"></div>
            </article>
        </section>

        <section class="graficos" aria-labelledby="titulo-graficos">
            <h2 id="titulo-graficos">Comportamiento del negocio</h2>

            <article class="grafico grafico--ancho">
                <h3>Ventas por mes</h3>
                <div class="grafico__lienzo" role="img" aria-label="Gráfico de barras con las ventas de cada mes">
                    <canvas id="grafico-ventas" aria-label="Ventas por mes"></canvas>
                </div>
            </article>

            <article class="grafico">
                <h3>Participación por categoría</h3>
                <div class="grafico__lienzo" role="img" aria-label="Gráfico de dona con la participación de ventas por categoría">
                    <canvas id="grafico-categorias" aria-label="Participación por categoría"></canvas>
                </div>
            </article>

            <article class="grafico">
                <h3>Pedidos por día (mes en curso)</h3>
                <div class="grafico__lienzo" role="img" aria-label="Gráfico de línea con los pedidos por día del mes">
                    <canvas id="grafico-pedidos" aria-label="Pedidos por día"></canvas>
                </div>
            </article>

            <article class="grafico">
                <h3>Ventas del periodo</h3>
                <div class="grafico__lienzo" role="img" aria-label="Gráfico de área con las ventas de los últimos días">
                    <canvas id="grafico-area" aria-label="Ventas del periodo"></canvas>
                </div>
            </article>

            <article class="grafico grafico--ancho">
                <h3>Actividad reciente</h3>
                <div id="pedidos-recientes"></div>
            </article>

            <article class="grafico grafico--ancho" id="mejores-clientes-lista"></article>

            <article class="grafico grafico--ancho" id="stock-critico-lista"></article>
        </section>

        <p><a class="boton" href="<?= BASE_URL ?>productos.php">Ir al inventario</a></p>
    </main>

    <?php require __DIR__ . '/app/vistas/parciales/pie.php'; ?>
    <span id="graficos-estado" aria-hidden="true"></span>
    <script src="<?= BASE_URL ?>js/vendor/chart.umd.min.js" defer></script>
    <script src="<?= BASE_URL ?>js/graficos.js" defer></script>
    <script src="<?= BASE_URL ?>js/app.js" defer></script>
</body>
</html>