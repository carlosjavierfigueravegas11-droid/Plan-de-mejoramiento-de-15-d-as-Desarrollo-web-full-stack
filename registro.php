<?php
declare(strict_types=1);

require_once __DIR__ . '/app/config/conexion.php';
require_once __DIR__ . '/app/modelos/UsuarioModelo.php';
require_once __DIR__ . '/app/seguridad/csrf.php';

session_start();

$error = '';
$exito = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCsrf($_POST['csrf'] ?? null)) {
        http_response_code(419);
        exit('Solicitud no válida. Recargue el formulario.');
    }

    $nombre = trim((string) ($_POST['nombre'] ?? ''));
    $correo = trim((string) ($_POST['correo'] ?? ''));
    $clave = (string) ($_POST['clave'] ?? '');
    $rol = in_array($_POST['rol'] ?? '', ['admin', 'vendedor', 'consultor'], true)
        ? (string) $_POST['rol'] : 'consultor';

    if (mb_strlen($nombre) < 3) {
        $error = 'El nombre debe tener al menos 3 caracteres.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'Correo electrónico no válido.';
    } elseif (strlen($clave) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } else {
        try {
            $pdo = Conexion::obtener();
            if (existeCorreo($pdo, $correo)) {
                $error = 'Ese correo ya está registrado.';
            } else {
                registrarUsuario($pdo, $nombre, $correo, $clave, $rol);
                $exito = true;
            }
        } catch (PDOException $e) {
            $error = 'No se pudo completar el registro. Intente más tarde.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro — ISoT</title>
    <link rel="stylesheet" href="css/tokens.css">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <main class="pantalla-ingreso">
        <section class="marca">
            <img src="assets/img/logo.svg" alt="Logo de ISoT" width="200">
            <h1>Panel de gestión</h1>
            <p>Crea un acceso para el equipo del programa.</p>
        </section>

        <section class="formulario">
            <h2>Registrar acceso</h2>
            <?php if ($error !== ''): ?>
                <p class="mensaje-error" id="error-registro" role="alert" aria-live="polite">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>
            <?php if ($exito): ?>
                <p class="mensaje-exito" role="status" id="exito-registro">
                    Registro creado correctamente para
                    <?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>.
                </p>
            <?php endif; ?>
            <form method="post" action="registro.php" novalidate>
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">

                <label for="nombre">Nombre completo</label>
                <input type="text" id="nombre" name="nombre" required minlength="3"
                       value="<?= htmlspecialchars($nombre ?? '', ENT_QUOTES, 'UTF-8') ?>">

                <label for="correo">Correo electrónico</label>
                <input type="email" id="correo" name="correo" required autocomplete="username"
                       value="<?= htmlspecialchars($correo ?? '', ENT_QUOTES, 'UTF-8') ?>">

                <label for="clave">Contraseña (mínimo 8 caracteres)</label>
                <input type="password" id="clave" name="clave" required minlength="8" autocomplete="new-password">

                <label for="rol">Rol</label>
                <select id="rol" name="rol">
                    <option value="consultor">Consultor</option>
                    <option value="vendedor">Vendedor</option>
                    <option value="admin">Administrador</option>
                </select>

                <button type="submit">Registrar</button>
            </form>
            <p class="nota"><a href="login.php">Ya tengo acceso</a></p>
        </section>
    </main>
</body>
</html>