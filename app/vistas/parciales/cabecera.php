<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
?>
<header class="encabezado barra">
    <div class="encabezado-marca">
        <button type="button" class="boton-menu" id="boton-menu"
                aria-controls="menu-lateral" aria-expanded="false">Menú</button>
        <img src="<?= BASE_URL ?>assets/img/logo.svg" alt="Logo de ISoT" width="64">
        <p class="nombre-sitio"><?= NOMBRE_SITIO ?></p>
    </div>
    <div class="usuario">
        <span><?= htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($usuario['rol'], ENT_QUOTES, 'UTF-8') ?></span>
        <a href="<?= BASE_URL ?>salir.php">Cerrar sesión</a>
    </div>
</header>