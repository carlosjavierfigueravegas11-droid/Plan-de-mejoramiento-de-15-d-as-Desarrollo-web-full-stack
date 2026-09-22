<?php
declare(strict_types=1);

/**
 * Día 13 — Vista: listado de productos.
 * Servidor MySQL :: Página 3 de 3 — Mostrando 22 de 22
 * Patrón MVC simple: esta vista solo presenta lo que le entregó el modelo.
 * Soporta búsqueda (?q=), paginación de diez registros (?pagina=) y orden por
 * columna (?orden=nombre|categoria|precio|stock&dir=asc|desc) usando lista
 * blanca. Botones de fila con data-id (día 8) y acciones por POST (nunca GET).
 */

require_once __DIR__ . '/app/seguridad/guardia.php';
require_once __DIR__ . '/app/seguridad/csrf.php';
require_once __DIR__ . '/app/seguridad/aviso.php';
require_once __DIR__ . '/app/config/conexion.php';
require_once __DIR__ . '/app/modelos/ProductoModelo.php';

$puedeEditar = puede('admin', 'vendedor');   // crear y editar productos
$puedeEliminar = puede('admin');             // borrar (lógico) productos

$q = trim((string) ($_GET['q'] ?? ''));
$orden = strtolower((string) ($_GET['orden'] ?? 'nombre'));
$direccion = strtoupper((string) ($_GET['dir'] ?? 'asc'));
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));
$porPagina = 10;

$columnasValidas = ['nombre', 'categoria', 'precio', 'stock', 'id'];
if (!in_array($orden, $columnasValidas, true)) {
    $orden = 'nombre';
}
if (!in_array($direccion, ['ASC', 'DESC'], true)) {
    $direccion = 'ASC';
}

$bdDisponible = false;
$productos = [];
$total = 0;
$totalPaginas = 1;
$aviso = consumirAviso();
$editando = null;

try {
    $pdo = Conexion::obtener();
    $bdDisponible = true;
    $modelo = new ProductoModelo($pdo);
    $productos = $modelo->listar($q, $pagina, $porPagina, $orden, $direccion);
    $total = $modelo->contar($q);
    $totalPaginas = max(1, (int) ceil($total / $porPagina));
    if ($pagina > $totalPaginas) {
        $pagina = $totalPaginas;
        $productos = $modelo->listar($q, $pagina, $porPagina, $orden, $direccion);
    }
    $categorias = $modelo->categorias();

    // Edición por el servidor (?editar=N) para que funciones sin JavaScript.
    $idEditar = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT) ?: 0;
    if ($idEditar > 0 && $puedeEditar) {
        $editando = $modelo->obtener($idEditar);
    }
} catch (PDOException $e) {
    $bdDisponible = false;
    $categorias = [];
}

$enlaceBase = 'productos.php?q=' . urlencode($q) . '&pagina=' . $pagina;
$columnaSiguiente = $direccion === 'ASC' ? 'desc' : 'asc';

function thOrdenable(string $etiqueta, string $campo, string $orden, string $direccion, string $enlaceBase): string
{
    $activa = $orden === $campo;
    $columnaSiguiente = $direccion === 'ASC' ? 'desc' : 'asc';
    $flecha = '';
    if ($activa) {
        $flecha = $direccion === 'ASC' ? ' ↑' : ' ↓';
    }
    $href = 'productos.php?q=' . urlencode($_GET['q'] ?? '') . '&pagina=1&orden=' . $campo . '&dir=' . $columnaSiguiente;

    return '<th scope="col"><a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8') . $flecha . '</a></th>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Productos — ISoT</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/tokens.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/estilos.css">
</head>
<body class="panel" data-tabla-servidor>
    <?php require __DIR__ . '/app/vistas/parciales/cabecera.php'; ?>
    <?php require __DIR__ . '/app/vistas/parciales/menu.php'; ?>

    <main class="contenido">
        <h1>Productos</h1>

        <?php if ($aviso): ?>
            <p class="alerta alerta--<?= htmlspecialchars($aviso['tipo'], ENT_QUOTES, 'UTF-8') ?>" role="status">
                <strong><?= $aviso['tipo'] === 'exito' ? 'Éxito' : 'Error' ?>:</strong>
                <?= htmlspecialchars($aviso['texto'], ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <section class="listado">
            <h2>Listado de productos</h2>
            <p class="fuente-datos">
                Fuente: MySQL vía PDO
                <span class="etiqueta-estado <?= $bdDisponible ? 'exito' : '' ?>">
                    <?= $bdDisponible ? 'conectado' : 'sin conexión' ?>
                </span>
                · Página <?= $pagina ?> de <?= $totalPaginas ?> · <?= htmlspecialchars((string) $total, ENT_QUOTES, 'UTF-8') ?> producto(s)
            </p>

            <form method="get" action="productos.php" class="buscador" role="search">
                <label for="buscador" class="oculto">Buscar producto</label>
                <input type="search" id="buscador" name="q" class="buscador" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="Filtrar por nombre o categoría">
            </form>

            <?php if ($bdDisponible && $total === 0): ?>
                <p>Sin resultados para la búsqueda «<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>».</p>
            <?php elseif ($bdDisponible): ?>
                <table id="tabla-productos">
                    <caption>Productos registrados en el inventario</caption>
                    <thead>
                        <tr>
                            <?= thOrdenable('Nombre', 'nombre', $orden, $direccion, $enlaceBase) ?>
                            <?= thOrdenable('Categoría', 'categoria', $orden, $direccion, $enlaceBase) ?>
                            <?= thOrdenable('Precio', 'precio', $orden, $direccion, $enlaceBase) ?>
                            <?= thOrdenable('Stock', 'stock', $orden, $direccion, $enlaceBase) ?>
                            <?php if ($puedeEditar || $puedeEliminar): ?>
                                <th scope="col">Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $producto): ?>
                            <tr>
                                <th scope="row" data-label="Nombre"><?= htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8') ?></th>
                                <td data-label="Categoría"><?= htmlspecialchars($producto['categoria'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td data-label="Precio"><?= '$ ' . number_format((float) $producto['precio'], 0, ',', '.') ?></td>
                                <td data-label="Stock"><?= (int) $producto['stock'] ?></td>
                                <?php if ($puedeEditar || $puedeEliminar): ?>
                                    <td data-label="Acciones" class="acciones">
                                        <?php if ($puedeEditar): ?>
                                            <button type="button" class="boton-mini" data-accion="editar" data-id="<?= (int) $producto['id'] ?>">Editar</button>
                                        <?php endif; ?>
                                        <?php if ($puedeEliminar): ?>
                                            <form method="post" action="<?= BASE_URL ?>app/rutas/eliminar-producto.php" class="accion-fila"
                                                  data-eliminar-form="<?= (int) $producto['id'] ?>" onsubmit="return confirm('¿Dar de baja este producto?');">
                                                <input type="hidden" name="csrf" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="id" value="<?= (int) $producto['id'] ?>">
                                                <button type="submit" class="boton-mini boton-peligro" data-accion="eliminar" data-id="<?= (int) $producto['id'] ?>">Eliminar</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($totalPaginas > 1): ?>
                    <nav class="paginador" aria-label="Paginación">
                        <?php if ($pagina > 1): ?>
                            <a href="productos.php?q=<?= urlencode($q) ?>&pagina=<?= $pagina - 1 ?>&orden=<?= $orden ?>&dir=<?= $direccion ?>">← Anterior</a>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                            <?php if ($i === $pagina): ?>
                                <span class="paginador-actual" aria-current="page"><?= $i ?></span>
                            <?php else: ?>
                                <a href="productos.php?q=<?= urlencode($q) ?>&pagina=<?= $i ?>&orden=<?= $orden ?>&dir=<?= $direccion ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <?php if ($pagina < $totalPaginas): ?>
                            <a href="productos.php?q=<?= urlencode($q) ?>&pagina=<?= $pagina + 1 ?>&orden=<?= $orden ?>&dir=<?= $direccion ?>">Siguiente →</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <?php if ($puedeEditar): ?>
        <section class="formulario">
            <h2><?= $editando ? 'Editar producto' : 'Registro de producto' ?></h2>
            <form action="<?= BASE_URL ?>app/rutas/guardar-producto.php" method="post" id="form-producto" novalidate>
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id" id="id" value="<?= $editando ? (int) $editando['id'] : '' ?>">
                <fieldset>
                    <legend>Datos del producto</legend>

                    <label for="nombre">Nombre del producto</label>
                    <input type="text" id="nombre" name="nombre" required minlength="3"
                           value="<?= $editando ? htmlspecialchars($editando['nombre'], ENT_QUOTES, 'UTF-8') : '' ?>"
                           aria-describedby="error-nombre">
                    <p class="mensaje-error" id="error-nombre" aria-live="polite"></p>

                    <label for="categoria">Categoría</label>
                    <select id="categoria" name="categoria_id" required aria-describedby="error-categoria">
                        <option value="">Seleccione una categoría</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= (int) $categoria['id'] ?>"
                                <?= $editando && (int) $editando['categoria_id'] === (int) $categoria['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($categoria['nombre'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mensaje-error" id="error-categoria" aria-live="polite"></p>

                    <label for="precio">Precio</label>
                    <input type="number" id="precio" name="precio" required min="0.01" step="0.01"
                           value="<?= $editando ? htmlspecialchars((string) $editando['precio'], ENT_QUOTES, 'UTF-8') : '' ?>"
                           aria-describedby="error-precio">
                    <p class="mensaje-error" id="error-precio" aria-live="polite"></p>

                    <label for="stock">Stock</label>
                    <input type="number" id="stock" name="stock" required min="0" step="1"
                           value="<?= $editando ? (int) $editando['stock'] : '' ?>"
                           aria-describedby="error-stock">
                    <p class="mensaje-error" id="error-stock" aria-live="polite"></p>
                </fieldset>
                <button type="submit"><?= $editando ? 'Guardar cambios' : 'Guardar producto' ?></button>
                <?php if ($editando): ?>
                    <p class="nota-edicion"><a href="productos.php">Cancelar edición</a></p>
                <?php endif; ?>
            </form>
        </section>
        <?php endif; ?>
    </main>

    <?php require __DIR__ . '/app/vistas/parciales/pie.php'; ?>
    <script src="<?= BASE_URL ?>js/app.js"></script>
    <script src="<?= BASE_URL ?>js/editar-tabla.js"></script>
</body>
</html>