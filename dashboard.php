<?php
declare(strict_types=1);

session_start();

$nombre = isset($_SESSION['usuario_nombre']) ? (string) $_SESSION['usuario_nombre'] : null;
$rol = isset($_SESSION['usuario_rol']) ? (string) $_SESSION['usuario_rol'] : null;
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
            <span><?= $nombre !== null ? htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') : 'Sin sesión' ?></span>
            <?php if ($nombre !== null): ?>
                <a href="logout.php">Cerrar sesión</a>
            <?php else: ?>
                <a href="login.php">Iniciar sesión</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="contenido">
        <?php if ($nombre !== null): ?>
            <h1>Hola, <?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="mensaje-exito" role="status">
                Sesión iniciada correctamente. Rol: <?= htmlspecialchars($rol ?? '', ENT_QUOTES, 'UTF-8') ?>.
            </p>
            <p><a class="boton" href="productos.php">Ir al inventario</a></p>
        <?php else: ?>
            <h1>Acceso restringido</h1>
            <p>Debes iniciar sesión para ver el tablero.</p>
            <p><a class="boton" href="login.php">Iniciar sesión</a></p>
        <?php endif; ?>
    </main>
</body>
</html>