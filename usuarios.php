<?php
declare(strict_types=1);

require_once __DIR__ . '/app/seguridad/guardia.php';
require_once __DIR__ . '/app/config/conexion.php';

exigirRol('admin'); // solo el administrador llega aquí

$usuarios = [];
try {
    $pdo = Conexion::obtener();
    $usuarios = $pdo->query(
        "SELECT id, nombre, correo, rol, activo, bloqueado_hasta FROM usuarios ORDER BY id"
    )->fetchAll();
} catch (PDOException $e) {
    $usuarios = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Usuarios — ISoT</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/tokens.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/estilos.css">
</head>
<body class="panel">
    <?php require __DIR__ . '/app/vistas/parciales/cabecera.php'; ?>
    <?php require __DIR__ . '/app/vistas/parciales/menu.php'; ?>

    <main class="contenido">
        <h1>Gestión de usuarios</h1>
        <p>Área exclusiva del administrador. La prueba de rol bloquea a consultores y vendedores (403).</p>

        <section class="listado">
            <h2>Usuarios registrados</h2>
            <table id="tabla-usuarios">
                <caption>Cuentas de acceso al panel (jamás se muestra la contraseña; solo el hash).</caption>
                <thead>
                    <tr>
                        <th scope="col">Nombre</th>
                        <th scope="col">Correo</th>
                        <th scope="col">Rol</th>
                        <th scope="col">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <th scope="row"><?= htmlspecialchars($u['nombre'], ENT_QUOTES, 'UTF-8') ?></th>
                            <td><?= htmlspecialchars($u['correo'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($u['rol'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php if ((int) $u['activo'] === 0): ?>
                                    <span class="etiqueta-estado estado-error">inactivo</span>
                                <?php elseif ($u['bloqueado_hasta'] !== null && $u['bloqueado_hasta'] > date('Y-m-d H:i:s')): ?>
                                    <span class="etiqueta-estado estado-error">bloqueado</span>
                                <?php else: ?>
                                    <span class="etiqueta-estado estado-ok">activo</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>

    <?php require __DIR__ . '/app/vistas/parciales/pie.php'; ?>
</body>
</html>