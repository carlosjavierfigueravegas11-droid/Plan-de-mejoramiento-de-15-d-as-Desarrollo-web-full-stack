<?php
declare(strict_types=1);

/**
 * Día 12 — Configuración de la aplicación.
 * BASE_URL: raíz pública del sistema. Con php -S sirviendo la raíz del proyecto
 * es "/", lo que permite rutas absolutas y evitar problemas al incluir parciales.
 *
 * Día 15 — Identidad institucional centralizada en APP.
 * Datos únicos del software/empresa para los reportes formales: encabezado,
 * pie de confidencialidad y exportaciones (PDF y CSV) usan ESTAS constantes,
 * nunca valores repetidos dentro de cada vista.
 */

define('BASE_URL', '/');
define('NOMBRE_SITIO', 'ISoT — Panel de gestión');
define('ANIO_SITIO', '2026');

define('SOFTWARE_NOMBRE', 'ISoT');
define('SOFTWARE_DESCRIPCION', 'Sistema de Punto de Venta y Gestión de Inventario');
define('SOFTWARE_VERSION', '1.0');
define('EMPRESA_NOMBRE', 'Soluciones Informáticas ISoT S.A.S.');
define('EMPRESA_NIT', '901.418.726-3');
define('EMPRESA_DIRECCION', 'Cra 32 # 14-26, Palmira (Valle del Cauca)');
define('EMPRESA_TELEFONO', '(+57) 606 555 0142');
define('EMPRESA_CORREO', 'contacto@isot.co');
define('EMPRESA_SITIO_WEB', 'https://isot.co');

/** Ruta física (sistema de archivos) del logo: versiones para pantalla y para PDF. */
define('LOGO_SVG', __DIR__ . '/../../assets/img/logo.svg');
define('LOGO_PNG', __DIR__ . '/../../assets/img/logo.png');

/**
 * Logo en base64 para incrustar en el PDF (Dompdf no carga archivos locales
 * al servirlo por HTTP; una imagen data: png se incrusta sin red).
 * Devuelve string vacío si el archivo no existe.
 */
function logoPngBase64(): string
{
    if (!is_file(LOGO_PNG)) {
        return '';
    }
    $datos = file_get_contents(LOGO_PNG);
    return 'data:image/png;base64,' . ($datos === false ? '' : base64_encode($datos));
}

/**
 * Encabezado de reporte formal, reutilizable (Día 15).
 * params: titulo, subtitulo, filtros (array clave=>valor), fecha (string).
 * Se usa en pantalla y en el PDF (mismo HTML).
 */
function encabezadoReporte(string $titulo, string $subtitulo, array $filtros, string $fecha): void
{
    ?>
    <header class="reporte-encabezado">
        <div class="reporte-marca">
            <img src="<?= is_file(LOGO_PNG) ? logoPngBase64() : (BASE_URL . 'assets/img/logo.svg') ?>"
                 alt="Logo de ISoT" width="96" height="34">
            <div>
                <p class="reporte-software"><?= SOFTWARE_NOMBRE ?> · <?= SOFTWARE_DESCRIPCION ?></p>
                <p class="reporte-empresa"><?= EMPRESA_NOMBRE ?> — NIT <?= EMPRESA_NIT ?></p>
            </div>
        </div>
        <div class="reporte-titulo">
            <h2><?= $titulo ?></h2>
            <p><?= $subtitulo ?></p>
        </div>
        <dl class="reporte-datos">
            <div><dt>Filtros aplicados</dt><dd><?= encabezadoFiltros($filtros) ?></dd></div>
            <div><dt>Generado</dt><dd><?= $fecha ?> · <?= htmlspecialchars((string) ($_SESSION['usuario']['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string) ($_SESSION['usuario']['rol'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)</dd></div>
        </dl>
    </header>
    <?php
}

/** Representación legible de los filtros para el encabezado (y para el CSV). */
function encabezadoFiltros(array $filtros): string
{
    $partes = [];
    foreach ($filtros as $clave => $valor) {
        if ($valor === '' || $valor === null) {
            continue;
        }
        $partes[] = htmlspecialchars((string) $clave, ENT_QUOTES, 'UTF-8') . ': '
                  . htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
    }
    return $partes === [] ? 'Sin filtros' : implode(' · ', $partes);
}

/** Pie de reporte con nota de confidencialidad (Día 15). */
function pieReporte(string $notaConfidencialidad = ''): void
{
    $nota = $notaConfidencialidad !== '' ? $notaConfidencialidad
        : 'Documento generado automáticamente por ' . SOFTWARE_NOMBRE . '. '
          . 'Información de uso interno de ' . EMPRESA_NOMBRE . '. ';
    ?>
    <footer class="reporte-pie">
        <p class="reporte-confidencial"><?= htmlspecialchars($nota, ENT_QUOTES, 'UTF-8') ?></p>
        <p class="reporte-paginacion">Página <span class="pagina">1</span> de <span class="paginas">1</span> · generado el <?= date('d/m/Y H:i') ?></p>
    </footer>
    <?php
}