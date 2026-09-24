<?php
declare(strict_types=1);

/**
 * Día 11 — Guardián de acceso. Se incluye al inicio de toda página privada.
 * 1) ¿Hay sesión?  2) ¿Es el mismo navegador (huella)?  3) ¿Expiró por inactividad o duración?
 * Además define exigirRol()/puede() para la autorización por rol (matriz del plan).
 * Ocultar un enlace en el menú NO es seguridad: la restricción se verifica aquí, en el servidor.
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/sesion.php';
iniciarSesionSegura();

/** ¿Es una API JSON? Entonces sin sesión se responde 401, no se redirige a login.php. */
$esApi = isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], '/api/') === 0;

function redirigirAPorFallo(string $mensaje): void
{
    global $esApi;
    if ($esApi) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $mensaje]);
        exit;
    }
    header('Location: ' . urlPagina('login.php') . '?m=' . $mensaje);
    exit;
}

/* 1. ¿Hay sesión? */
if (usuarioActual() === null) {
    redirigirAPorFallo('requiere_ingreso');
}

/* 2. ¿Es el mismo navegador? (huella del cliente) */
$huella = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
if (($_SESSION['huella'] ?? '') !== $huella) {
    cerrarSesion();
    redirigirAPorFallo('sesion_invalida');
}

/* 3. Inactividad y duración máxima */
$ahora = time();
$inactividad = (int) ($_SESSION['ultima_actividad'] ?? $ahora);
$inicio = (int) ($_SESSION['inicio'] ?? $ahora);
if ($ahora - $inactividad > INACTIVIDAD_MAX || $ahora - $inicio > SESION_MAX) {
    cerrarSesion();
    redirigirAPorFallo('sesion_expirada');
}
$_SESSION['ultima_actividad'] = $ahora;

$usuario = usuarioActual();

/**
 * Autorización por rol. Detiene la respuesta con 403 si el rol no está permitido.
 */
function exigirRol(string ...$roles): void
{
    $rol = $_SESSION['usuario']['rol'] ?? '';
    if (!in_array($rol, $roles, true)) {
        http_response_code(403);
        exit('403 — No tiene permiso para esta operación.');
    }
}