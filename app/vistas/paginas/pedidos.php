<?php
declare(strict_types=1);

/**
 * Día 13 — Vista: pedidos (server-rendered, MVC simple).
 * El registro descuenta el stock dentro de una transacción (PedidoModelo:
 * beginTransaction / commit / rollBack), por lo que pedido + detalle + stock
 * son inseparables. El histórico se consulta con LEFT JOIN a productos para
 * que un producto desactivado (activo = 0) siga apareciendo: demostración de
 * borrado lógico que no rompe la integridad referencial.
 */

require_once __DIR__ . '/../../../app/seguridad/guardia.php';
require_once __DIR__ . '/../../../app/seguridad/csrf.php';
require_once __DIR__ . '/../../../app/seguridad/aviso.php';
require_once __DIR__ . '/../../../app/config/conexion.php';
require_once __DIR__ . '/../../../app/modelos/ProductoModelo.php';
require_once __DIR__ . '/../../../app/modelos/ClienteModelo.php';
require_once __DIR__ . '/../../../app/modelos/PedidoModelo.php';

if (!puede('admin', 'vendedor')) {
    http_response_code(403);
    exit('403 — No tiene permiso para esta operación.');
}

$bdDisponible = false;
$productos = [];
$clientes = [];
$historial = [];
$aviso = consumirAviso();

try {
    $pdo = Conexion::obtener();
    $bdDisponible = true;
    $productos = (new ProductoModelo($pdo))->listar('', 1, 1000, 'nombre', 'asc');
    $clientes = (new ClienteModelo($pdo))->listar('', 1, 1000, 'nombre', 'asc');
    $historial = (new PedidoModelo($pdo))->historial(10);
} catch (PDOException $e) {
    $bdDisponible = false;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pedidos — ISoT</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/tokens.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/estilos.css">
</head>
<body class="panel">
    <?php require __DIR__ . '/../parciales/cabecera.php'; ?>
    <?php require __DIR__ . '/../parciales/menu.php'; ?>

    <main class="contenido">
        <h1>Pedidos</h1>

        <?php if ($aviso): ?>
            <p class="alerta alerta--<?= htmlspecialchars($aviso['tipo'], ENT_QUOTES, 'UTF-8') ?>" role="status">
                <strong><?= $aviso['tipo'] === 'exito' ? 'Éxito' : 'Error' ?>:</strong>
                <?= htmlspecialchars($aviso['texto'], ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <section class="formulario">
            <h2>Registrar pedido</h2>
            <form action="<?= BASE_URL ?>app/rutas/registrar-pedido.php" method="post" id="form-pedido">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">

                <fieldset>
                    <legend>Cliente</legend>
                    <label for="cliente_id">Cliente</label>
                    <select id="cliente_id" name="cliente_id" required aria-describedby="error-cliente">
                        <option value="">Seleccione un cliente</option>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?= (int) $cliente['id'] ?>"><?= htmlspecialchars($cliente['nombre'] . ' — ' . $cliente['documento'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mensaje-error" id="error-cliente" aria-live="polite"></p>
                </fieldset>

                <fieldset id="lineas-pedido">
                    <legend>Líneas del pedido</legend>
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                        <div class="linea-pedido">
                            <label for="producto_<?= $i ?>">Producto <?= $i ?></label>
                            <select id="producto_<?= $i ?>" name="producto_id[]">
                                <option value="">—</option>
                                <?php foreach ($productos as $producto): ?>
                                    <option value="<?= (int) $producto['id'] ?>"><?= htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="cantidad_<?= $i ?>">Cantidad</label>
                            <input type="number" id="cantidad_<?= $i ?>" name="cantidad[]" min="1" step="1" value="" placeholder="0">
                        </div>
                    <?php endfor; ?>
                </fieldset>

                <button type="submit">Registrar pedido</button>
            </form>
        </section>

        <section class="listado">
            <h2>Histórico de pedidos</h2>
            <p class="fuente-datos">
                Fuente: MySQL vía PDO
                <span class="etiqueta-estado <?= $bdDisponible ? 'exito' : '' ?>">
                    <?= $bdDisponible ? 'conectado' : 'sin conexión' ?>
                </span>
            </p>

            <?php if ($historial): ?>
                <?php $pedido = new PedidoModelo(Conexion::obtener()); ?>
                <table id="tabla-pedidos">
                    <caption>Últimos 10 pedidos</caption>
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Fecha</th>
                            <th scope="col">Cliente</th>
                            <th scope="col">Total</th>
                            <th scope="col">Estado</th>
                            <th scope="col">Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historial as $ped): ?>
                            <tr>
                                <th scope="row" data-label="#"><?= (int) $ped['id'] ?></th>
                                <td data-label="Fecha"><?= htmlspecialchars(date('Y-m-d H:i', strtotime($ped['fecha'])), ENT_QUOTES, 'UTF-8') ?></td>
                                <td data-label="Cliente"><?= htmlspecialchars($ped['cliente'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td data-label="Total"><?= '$ ' . number_format((float) $ped['total'], 0, ',', '.') ?></td>
                                <td data-label="Estado">
                                    <span class="etiqueta-estado estado-<?= htmlspecialchars($ped['estado'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($ped['estado'], ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td data-label="Detalle">
                                    <?php foreach ($pedido->detalle((int) $ped['id']) as $det): ?>
                                        <div class="fila-detalle">
                                            <span class="<?= (int) $det['activo'] === 0 ? 'desactivado' : '' ?>">
                                                <?= htmlspecialchars($det['producto'], ENT_QUOTES, 'UTF-8') ?>
                                                <?= (int) $det['activo'] === 0 ? ' <small>(desactivado)</small>' : '' ?>
                                            </span>
                                            × <?= (int) $det['cantidad'] ?>
                                            ($ <?= number_format((float) $det['precio_unitario'], 0, ',', '.') ?>)
                                        </div>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="nota-historico">
                    Nota: un producto desactivado (borrado lógico) sigue visible en los pedidos que ya se vendieron; la consulta usa
                    LEFT JOIN y el precio se conserva en <code>detalle_pedidos.precio_unitario</code>.
                </p>
            <?php else: ?>
                <p>Sin pedidos registrados.</p>
            <?php endif; ?>
        </section>
    </main>

    <?php require __DIR__ . '/../parciales/pie.php'; ?>
</body>
</html>