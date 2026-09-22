<?php
declare(strict_types=1);

/**
 * Día 15 — Pie reutilizable de reportes formales.
 * Nota de confidencialidad + paginación. La paginación la completa CSS
 * (@page / .reporte-pie) para no duplicar contenido en cada página.
 */

require_once __DIR__ . '/../../config/app.php';

pieReporte($reporte['confidencial'] ?? '');