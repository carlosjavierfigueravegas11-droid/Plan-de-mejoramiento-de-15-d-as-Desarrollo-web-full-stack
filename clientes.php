<?php
declare(strict_types=1);

/**
 * Día 13 — Vista: CRUD de clientes (server-rendered, MVC simple).
 * Documento y correo únicos validados en el servidor (guardar-cliente.php).
 * Eliminar funciona por POST y respeta la integridad referencial: un cliente
 * con pedidos no se puede borrar. Paginación de diez y orden por lista blanca.
 */

require_once __DIR__ . '/app/seguridad/guardia.php';
require_once __DIR__ . '/app/seguridad/csrf.php';
require_once __DIR__ . '/app/seguridad/aviso.php';
require_once __DIR__ . '/app/config/conexion.php';
require_once __DIR__ . '/app/modelos/ClienteModelo.php';

$esUsuario = puede('admin', 'vendedor');     // crear y editar clientes
if (!$esUsuario) {
    http_response_code(403);
    exit('403 — No tiene permiso para esta operación.');
}

$q = trim((string) ($_GET['q'] ?? ''));
$orden = strtolower((string) ($_GET['orden'] ?? 'nombre'));
$direccion = strtoupper((string) ($_GET['dir'] ?? 'asc'));
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));
$porPagina = 10;

$columnasValidas = ['nombre', 'documento', 'correo', 'telefono', 'id'];
if (!in_array($orden, $columnasValidas, true)) {
    $orden = 'nombre';
}
if (!in_array($direccion, ['ASC', 'DESC'], true)) {
    $direccion = 'ASC';
}

$bdDisponible = false;
$clientes = [];
$total = 0;
$totalPaginas = 1;
$aviso = consumirAviso();
$editando = null;

try {
    $pdo = Conexion::obtener();
    $bdDisponible = true;
    $modelo = new ClienteModelo($pdo);
    $clientes = $modelo->listar($q, $pagina, $porPagina, $orden, $direccion);
    $total = $modelo->contar($q);
    $totalPaginas = max(1, (int) ceil($total / $porPagina));
    if ($pagina > $totalPaginas) {
        $pagina = $totalPaginas;
        $clientes = $modelo->listar($q, $pagina, $porPagina, $orden, $direccion);
    }

    $idEditar = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT) ?: 0;
    if ($idEditar > 0) {
        $editando = $modelo->obtener($idEditar);
    }
} catch (PDOException $e) {
    $bdDisponible = false;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Clientes — ISoT</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/tokens.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/estilos.css">
</head>
<body class="panel" data-tabla-servidor>
    <?php require __DIR__ . '/app/vistas/parciales/cabecera.php'; ?>
    <?php require __DIR__ . '/app/vistas/parciales/menu.php'; ?>

    <main class="contenido">
        <h1>Clientes</h1>

        <?php if ($aviso): ?>
            <p class="alerta alerta--<?= htmlspecialchars($aviso['tipo'], ENT_QUOTES, 'UTF-8') ?>" role="status">
                <strong><?= $aviso['tipo'] === 'exito' ? 'Éxito' : 'Error' ?>:</strong>
                <?= htmlspecialchars($aviso['texto'], ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <section class="listado">
            <h2>Listado de clientes</h2>
            <p class="fuente-datos">
                Fuente: MySQL vía PDO
                <span class="etiqueta-estado <?= $bdDisponible ? 'exito' : '' ?>">
                    <?= $bdDisponible ? 'conectado' : 'sin conexión' ?>
                </span>
                · Página <?= $pagina ?> de <?= $totalPaginas ?> · <?= htmlspecialchars((string) $total, ENT_QUOTES, 'UTF-8') ?> cliente(s)
            </p>

            <form method="get" action="clientes.php" class="buscador" role="search">
                <label for="buscador" class="oculto">Buscar cliente</label>
                <input type="search" id="buscador" name="q" class="buscador" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="Filtrar por nombre, documento o correo">
            </form>

            <?php if ($bdDisponible && $total === 0): ?>
                <p>Sin resultados para la búsqueda «<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>».</p>
            <?php elseif ($bdDisponible): ?>
                <table id="tabla-clientes">
                    <caption>Clientes registrados</caption>
                    <thead>
                        <tr>
                            <?php foreach (['nombre' => 'Nombre', 'documento' => 'Documento', 'correo' => 'Correo', 'telefono' => 'Teléfono'] as $campo => $etiqueta): ?>
                                <th scope="col">
                                    <?php $activo = $orden === $campo; $dirSig = $orden === $campo && $direccion === 'ASC' ? 'desc' : 'asc'; ?>
                                    <a href="clientes.php?q=<?= urlencode($q) ?>&pagina=1&orden=<?= $campo ?>&dir=<?= $dirSig ?>">
                                        <?= $etiqueta ?><?= $activo ? ($direccion === 'ASC' ? ' ↑' : ' ↓') : '' ?>
                                    </a>
                                </th>
                            <?php endforeach; ?>
                            <th scope="col">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <th scope="row" data-label="Nombre"><?= htmlspecialchars($cliente['nombre'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td data-label="Documento"><?= htmlspecialchars($cliente['documento'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td data-label="Correo"><?= htmlspecialchars($cliente['correo'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td data-label="Teléfono"><?= htmlspecialchars((string) ($cliente['telefono'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td data-label="Acciones" class="acciones">
                                    <button type="button" class="boton-mini" data-accion="editar" data-id="<?= (int) $cliente['id'] ?>">Editar</button>
                                    <form method="post" action="<?= BASE_URL ?>app/rutas/eliminar-cliente.php" class="accion-fila"
                                          onsubmit="return confirm('¿Eliminar este cliente? Solo si no tiene pedidos.');">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="id" value="<?= (int) $cliente['id'] ?>">
                                        <button type="submit" class="boton-mini boton-peligro" data-accion="eliminar" data-id="<?= (int) $cliente['id'] ?>">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($totalPaginas > 1): ?>
                    <nav class="paginador" aria-label="Paginación">
                        <?php if ($pagina > 1): ?>
                            <a href="clientes.php?q=<?= urlencode($q) ?>&pagina=<?= $pagina - 1 ?>&orden=<?= $orden ?>&dir=<?= $direccion ?>">← Anterior</a>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                            <?php if ($i === $pagina): ?>
                                <span class="paginador-actual" aria-current="page"><?= $i ?></span>
                            <?php else: ?>
                                <a href="clientes.php?q=<?= urlencode($q) ?>&pagina=<?= $i ?>&orden=<?= $orden ?>&dir=<?= $direccion ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <?php if ($pagina < $totalPaginas): ?>
                            <a href="clientes.php?q=<?= urlencode($q) ?>&pagina=<?= $pagina + 1 ?>&orden=<?= $orden ?>&dir=<?= $direccion ?>">Siguiente →</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <section class="formulario">
            <h2><?= $editando ? 'Editar cliente' : 'Registro de cliente' ?></h2>
            <form action="<?= BASE_URL ?>app/rutas/guardar-cliente.php" method="post" id="form-cliente" novalidate>
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id" id="id" value="<?= $editando ? (int) $editando['id'] : '' ?>">
                <fieldset>
                    <legend>Datos del cliente</legend>

                    <label for="nombre">Nombre completo</label>
                    <input type="text" id="nombre" name="nombre" required minlength="3"
                           value="<?= $editando ? htmlspecialchars($editando['nombre'], ENT_QUOTES, 'UTF-8') : '' ?>"
                           aria-describedby="error-nombre">
                    <p class="mensaje-error" id="error-nombre" aria-live="polite"></p>

                    <label for="documento">Número de documento (único)</label>
                    <input type="text" id="documento" name="documento" required
                           value="<?= $editando ? htmlspecialchars($editando['documento'], ENT_QUOTES, 'UTF-8') : '' ?>"
                           aria-describedby="error-documento">
                    <p class="mensaje-error" id="error-documento" aria-live="polite"></p>

                    <label for="correo">Correo electrónico (único)</label>
                    <input type="email" id="correo" name="correo" required
                           value="<?= $editando ? htmlspecialchars($editando['correo'], ENT_QUOTES, 'UTF-8') : '' ?>"
                           aria-describedby="error-correo">
                    <p class="mensaje-error" id="error-correo" aria-live="polite"></p>

                    <label for="telefono">Teléfono</label>
                    <input type="tel" id="telefono" name="telefono"
                           value="<?= $editando ? htmlspecialchars((string) ($editando['telefono'] ?? ''), ENT_QUOTES, 'UTF-8') : '' ?>"
                           aria-describedby="error-telefono">
                    <p class="mensaje-error" id="error-telefono" aria-live="polite"></p>
                </fieldset>
                <button type="submit"><?= $editando ? 'Guardar cambios' : 'Guardar cliente' ?></button>
                <?php if ($editando): ?>
                    <p class="nota-edicion"><a href="clientes.php">Cancelar edición</a></p>
                <?php endif; ?>
            </form>
        </section>
    </main>

    <?php require __DIR__ . '/app/vistas/parciales/pie.php'; ?>
    <script src="<?= BASE_URL ?>js/app.js"></script>
</body>
</html>