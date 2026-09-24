<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../seguridad/sesion.php';

cerrarSesion();

header('Location: ' . urlPagina('login.php') . '?m=cerrada');
exit;