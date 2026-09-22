<?php
declare(strict_types=1);

/**
 * Día 15 — Encabezado reutilizable de reportes formales.
 * Incluye logo, nombre del software, título, filtros aplicados, fecha/hora de
 * generación y usuario que lo generó. Usa los datos centralizados de APP.
 *
 * Variables esperadas:
 *   $reporte = [
 *     'titulo'    => string,
 *     'subtitulo' => string,
 *     'filtros'   => array<string,string>,
 *   ]
 */

require_once __DIR__ . '/../../config/app.php';

$fechaGeneracion = date('d/m/Y \a \l\a\s H:i');
encabezadoReporte(
    $reporte['titulo'] ?? 'Reporte',
    $reporte['subtitulo'] ?? SOFTWARE_DESCRIPCION,
    $reporte['filtros'] ?? [],
    $fechaGeneracion
);