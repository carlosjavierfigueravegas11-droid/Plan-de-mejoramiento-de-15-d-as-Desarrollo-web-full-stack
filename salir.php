<?php
declare(strict_types=1);

require_once __DIR__ . '/app/seguridad/sesion.php';

cerrarSesion();

header('Location: login.php?m=cerrada');
exit;