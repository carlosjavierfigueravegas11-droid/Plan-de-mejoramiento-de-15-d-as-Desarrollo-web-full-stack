<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../app/config/app.php';
require_once __DIR__ . '/../../../app/config/conexion.php';
require_once __DIR__ . '/../../../app/modelos/UsuarioModelo.php';
require_once __DIR__ . '/../../../app/seguridad/sesion.php';
require_once __DIR__ . '/../../../app/seguridad/csrf.php';
require_once __DIR__ . '/../../../app/seguridad/intentos.php';

iniciarSesionSegura();

$error = '';
$m = isset($_GET['m']) ? (string) $_GET['m'] : '';

if ($m === 'requiere_ingreso')    $error = 'Debes iniciar sesión para ver esa página.';
if ($m === 'sesion_invalida')     $error = 'Tu sesión se cerró por cambio en el navegador.';
if ($m === 'sesion_expirada')     $error = 'Tu sesión expiró por inactividad. Inicia sesión de nuevo.';
if ($m === 'cerrada')             $error = 'Sesión cerrada correctamente.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCsrf($_POST['csrf'] ?? null)) {
        http_response_code(419);
        exit('Solicitud no válida. Recargue el formulario.');
    }

    $correo = trim((string) ($_POST['correo'] ?? ''));
    $clave = (string) ($_POST['clave'] ?? '');

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($clave) < 8) {
        $error = 'Correo o contraseña incorrectos.'; // mensaje genérico
    } else {
        $pdo = Conexion::obtener();

        if (cuentaBloqueada($pdo, $correo)) {
            $error = 'Cuenta bloqueada temporalmente. Intente más tarde.';
        } else {
            $u = buscarPorCorreo($pdo, $correo);

            if ($u && (int) $u['activo'] === 1 && password_verify($clave, $u['clave_hash'])) {
                if (password_needs_rehash($u['clave_hash'], PASSWORD_DEFAULT)) {
                    $nuevo = password_hash($clave, PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE usuarios SET clave_hash = :h WHERE id = :id")
                        ->execute([':h' => $nuevo, ':id' => $u['id']]);
                }

                registrarIntento($pdo, $correo, true);

                abrirSesion($u); // regenera el ID: evita fijación de sesión

                header('Location: ' . urlPagina('dashboard.php'));
                exit;
            } else {
                registrarIntento($pdo, $correo, false);

                if (contarIntentosFallidos($pdo, $correo) >= MAX_INTENTOS && $u !== null) {
                    bloquearCuenta($pdo, (int) $u['id']);
                    $error = 'Cuenta bloqueada temporalmente. Intente más tarde.';
                } else {
                    $error = 'Correo o contraseña incorrectos.'; // idéntico para ambos casos
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingreso — ISoT</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/tokens.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/estilos.css">
</head>
<body>
    <main class="pantalla-ingreso">
        <section class="marca">
            <img src="<?= BASE_URL ?>assets/img/logo.svg" alt="Logo de ISoT" width="200">
            <h1>Panel de gestión</h1>
            <p>Administra tu inventario, tus ventas y tus reportes en un solo lugar.</p>
        </section>

        <section class="formulario">
            <h2>Iniciar sesión</h2>
            <?php if ($error !== ''): ?>
                <p class="mensaje-error" id="error-login" role="alert" aria-live="polite">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>
            <form method="post" action="<?= urlPagina('login.php') ?>" novalidate>
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">

                <label for="correo">Correo electrónico</label>
                <input type="email" id="correo" name="correo" required autocomplete="username">

                <label for="clave">Contraseña</label>
                <input type="password" id="clave" name="clave" required autocomplete="current-password" minlength="8">

                <button type="submit">Iniciar sesión</button>
            </form>
            <p class="nota"><a href="<?= urlPagina('registro.php') ?>">Registrar acceso</a></p>
        </section>
    </main>
</body>
</html>