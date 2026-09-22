<?php
declare(strict_types=1);
?>
<h1>Hola, <?= htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8') ?></h1>
<p class="mensaje-exito" role="status">
    Sesión iniciada correctamente. Rol: <?= htmlspecialchars($usuario['rol'], ENT_QUOTES, 'UTF-8') ?>.
</p>