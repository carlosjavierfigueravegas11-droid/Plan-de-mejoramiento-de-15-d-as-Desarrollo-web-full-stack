<?php
declare(strict_types=1);

require_once __DIR__ . '/app/config/conexion.php';
require_once __DIR__ . '/app/modelos/UsuarioModelo.php';

$error = null;
$usuario = null;
$correo = $_POST['correo'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clave = $_POST['clave'] ?? '';

    try {
        $pdo = Conexion::obtener();
        $usuario = buscarPorCorreo($pdo, $correo);

        if ($usuario === null || !password_verify($clave, $usuario['clave_hash'])) {
            $usuario = null;
            $error = 'Credenciales incorrectas.';
        }
    } catch (PDOException $e) {
        $error = 'No se pudo conectar con la base de datos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingreso — ISoT</title>
    <link rel="stylesheet" href="css/tokens.css">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <?php if ($usuario !== null): ?>
    <main class="pantalla-ingreso">
        <section class="formulario resultado-ok">
            <h1>Sesión iniciada</h1>
            <p>Bienvenido, <?= htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8') ?>.</p>
            <p>Consulta de usuario realizada con sentencia preparada (PDO).</p>
            <a class="boton" href="productos.php">Ir al panel</a>
        </section>
    </main>
    <?php else: ?>
    <main class="pantalla-ingreso">
        <section class="marca">
            <img src="assets/img/logo.svg" alt="Logo de ISoT" width="200">
            <h1>Panel de gestión</h1>
            <p>Administra tu inventario, tus ventas y tus reportes en un solo lugar.</p>
        </section>

        <section class="formulario">
            <h2>Iniciar sesión</h2>
            <?php if ($error !== null): ?>
                <p class="mensaje-error" id="error-login" aria-live="polite"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <form action="login.php" method="post" novalidate>
                <label for="correo">Correo electrónico</label>
                <input type="email" id="correo" name="correo" required autocomplete="username"
                       value="<?= htmlspecialchars($correo, ENT_QUOTES, 'UTF-8') ?>">

                <label for="clave">Contraseña</label>
                <input type="password" id="clave" name="clave" required autocomplete="current-password" minlength="8">

                <button type="submit">Iniciar sesión</button>
            </form>
            <p class="nota">Acceso restringido al equipo del programa.</p>
        </section>
    </main>
    <?php endif; ?>
</body>
</html>