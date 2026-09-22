<?php
declare(strict_types=1);

require_once __DIR__ . '/app/seguridad/guardia.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tablero — ISoT</title>
    <link rel="stylesheet" href="css/tokens.css">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body class="panel">
    <header class="encabezado barra">
        <div class="encabezado-marca">
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
                <li><a href="dashboard.php" class="activo" aria-current="page">Tablero</a></li>
                <li><a href="productos.php">Productos</a></li>
                <?php if (puede('admin', 'vendedor')): ?>
                    <li><a href="categorias.html">Categorías</a></li>
                    <li><a href="clientes.html">Clientes</a></li>
                    <li><a href="pedidos.html">Pedidos</a></li>
                <?php endif; ?>
                <li><a href="reportes.html">Reportes</a></li>
                <?php if (puede('admin')): ?>
                    <li><a href="usuarios.php">Usuarios</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </aside>

    <main class="contenido">
        <h1>Hola, <?= htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="mensaje-exito" role="status">
            Sesión iniciada correctamente. Rol: <?= htmlspecialchars($usuario['rol'], ENT_QUOTES, 'UTF-8') ?>.
        </p>
        <p><a class="boton" href="productos.php">Ir al inventario</a></p>
    </main>
</body>
</html>