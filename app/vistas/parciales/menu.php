<?php
declare(strict_types=1);

/**
 * Día 12 — Menú lateral generado desde configuración.
 * Cada entrada declara los roles que pueden verla; además, el servidor lo verifica
 * con puede() al renderizar. El elemento activo se marca comparando el archivo actual.
 */

require_once __DIR__ . '/../../config/app.php';

$paginaActual = basename($_SERVER['PHP_SELF']);

$opcionesMenu = [
    ['archivo' => 'dashboard.php', 'texto' => 'Tablero',    'roles' => ['admin', 'vendedor', 'consultor']],
    ['archivo' => 'productos.php', 'texto' => 'Productos',  'roles' => ['admin', 'vendedor', 'consultor']],
    ['archivo' => 'categorias.html', 'texto' => 'Categorías', 'roles' => ['admin', 'vendedor']],
    ['archivo' => 'clientes.php', 'texto' => 'Clientes',   'roles' => ['admin', 'vendedor']],
    ['archivo' => 'pedidos.php', 'texto' => 'Pedidos',     'roles' => ['admin', 'vendedor']],
    ['archivo' => 'reportes.php', 'texto' => 'Reportes',   'roles' => ['admin', 'consultor']],
    ['archivo' => 'usuarios.php', 'texto' => 'Usuarios',    'roles' => ['admin']],
];
?>
<aside class="menu" id="menu-lateral">
    <nav aria-label="Menú principal">
        <ul>
            <?php foreach ($opcionesMenu as $opcion): ?>
                <?php if (!puede(...$opcion['roles'])) continue; ?>
                <?php $activo = ($paginaActual === $opcion['archivo']); ?>
                <li>
                    <a href="<?= BASE_URL . $opcion['archivo'] ?>"
                       class="<?= $activo ? 'activo' : '' ?>"
                       <?= $activo ? 'aria-current="page"' : '' ?>>
                        <?= htmlspecialchars($opcion['texto'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</aside>