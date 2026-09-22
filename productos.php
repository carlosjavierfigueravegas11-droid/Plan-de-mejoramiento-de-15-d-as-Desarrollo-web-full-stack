<?php
declare(strict_types=1);

require_once __DIR__ . '/app/seguridad/guardia.php';
require_once __DIR__ . '/app/config/conexion.php';

$bdDisponible = false;
try {
    Conexion::obtener();
    $bdDisponible = true;
} catch (PDOException $_) {
    $bdDisponible = false;
}

$puedeEditar = puede('admin', 'vendedor');    // crear y editar productos
$puedeEliminar = puede('admin');              // eliminar registros
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Productos — ISoT</title>
    <link rel="stylesheet" href="css/tokens.css">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body class="panel" <?= $puedeEditar ? 'data-puede-editar="1"' : 'data-puede-editar="0"' ?> <?= $puedeEliminar ? 'data-puede-eliminar="1"' : 'data-puede-eliminar="0"' ?>>
    <header class="encabezado barra">
        <div class="encabezado-marca">
            <button type="button" class="boton-menu" id="boton-menu" aria-controls="menu-lateral" aria-expanded="false">Menú</button>
            <img src="assets/img/logo.svg" alt="Logo de ISoT" width="64">
            <p class="nombre-sitio">ISoT — Panel de gestión</p>
        </div>
        <div class="usuario">
            <span><?= htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($usuario['rol'], ENT_QUOTES, 'UTF-8') ?></span>
            <a href="salir.php">Cerrar sesión</a>
        </div>
    </header>

    <aside class="menu" id="menu-lateral">
        <nav aria-label="Menú principal">
            <ul>
                <li><a href="dashboard.php">Tablero</a></li>
                <li><a href="productos.php" class="activo" aria-current="page">Productos</a></li>
                <?php if ($puedeEditar): ?>
                    <li><a href="categorias.html">Categorías</a></li>
                    <li><a href="clientes.html">Clientes</a></li>
                    <li><a href="pedidos.html">Pedidos</a></li>
                <?php endif; ?>
                <li><a href="reportes.html">Reportes</a></li>
                <?php if ($puedeEliminar): ?>
                    <li><a href="usuarios.php">Usuarios</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </aside>

    <main class="contenido">
        <h1>Productos</h1>

        <section class="listado">
            <h2>Listado de productos</h2>
            <p class="fuente-datos">
                Fuente: MySQL vía PDO
                <span class="etiqueta-estado <?= $bdDisponible ? 'estado-ok' : 'estado-error' ?>">
                    <?= $bdDisponible ? 'conectado' : 'sin conexión' ?>
                </span>
            </p>
            <label for="buscador">Buscar producto</label>
            <input type="search" id="buscador" name="buscador" class="buscador" placeholder="Filtrar por nombre o categoría">
            <table id="tabla-productos">
                <caption>Productos registrados en el inventario</caption>
                <thead>
                    <tr>
                        <th scope="col">Nombre</th>
                        <th scope="col">Categoría</th>
                        <th scope="col">Precio</th>
                        <th scope="col">Stock</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </section>

        <?php if ($puedeEditar): ?>
        <section class="formulario">
            <h2>Registro de producto</h2>
            <form action="productos.php" method="post" id="form-producto" novalidate>
                <fieldset>
                    <legend>Datos del producto</legend>

                    <label for="nombre">Nombre del producto</label>
                    <input type="text" id="nombre" name="nombre" required minlength="3" aria-describedby="error-nombre">
                    <p class="mensaje-error" id="error-nombre" aria-live="polite"></p>

                    <label for="categoria">Categoría</label>
                    <select id="categoria" name="categoria" required aria-describedby="error-categoria">
                        <option value="">Seleccione una categoría</option>
                        <option value="Periféricos">Periféricos</option>
                        <option value="Pantallas">Pantallas</option>
                        <option value="Almacenamiento">Almacenamiento</option>
                        <option value="Redes">Redes</option>
                        <option value="Audio">Audio</option>
                    </select>
                    <p class="mensaje-error" id="error-categoria" aria-live="polite"></p>

                    <label for="precio">Precio</label>
                    <input type="number" id="precio" name="precio" required min="0.01" step="0.01" aria-describedby="error-precio">
                    <p class="mensaje-error" id="error-precio" aria-live="polite"></p>

                    <label for="stock">Stock</label>
                    <input type="number" id="stock" name="stock" required min="0" step="1" aria-describedby="error-stock">
                    <p class="mensaje-error" id="error-stock" aria-live="polite"></p>
                </fieldset>
                <button type="submit">Guardar producto</button>
            </form>
        </section>
        <?php endif; ?>
    </main>

    <footer class="pie">
        <p>ISoT — Sistema de gestión. © 2026</p>
    </footer>

    <script src="js/datos-prueba.js"></script>
    <script src="js/app.js"></script>
</body>
</html>