<?php
declare(strict_types=1);

/**
 * Día 11 — Guardián de acceso. Se incluye al inicio de toda página privada.
 * 1) ¿Hay sesión?  2) ¿Es el mismo navegador (huella)?  3) ¿Expiró por inactividad o duración?
 * Además define exigirRol()/puede() para la autorización por rol (matriz del plan).
 * Ocultar un enlace en el menú NO es seguridad: la restricción se verifica aquí, en el servidor.
 */

require_once __DIR__ . '/sesion.php';
iniciarSesionSegura();

/* 1. ¿Hay sesión? */
if (usuarioActual() === null) {
    header('Location: login.php?m=requiere_ingreso');
    exit;
}

/* 2. ¿Es el mismo navegador? (huella del cliente) */
$huella = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
if (($_SESSION['huella'] ?? '') !== $huella) {
    cerrarSesion();
    header('Location: login.php?m=sesion_invalida');
    exit;
}

/* 3. Inactividad y duración máxima */
$ahora = time();
$inactividad = (int) ($_SESSION['ultima_actividad'] ?? $ahora);
$inicio = (int) ($_SESSION['inicio'] ?? $ahora);
if ($ahora - $inactividad > INACTIVIDAD_MAX || $ahora - $inicio > SESION_MAX) {
    cerrarSesion();
    header('Location: login.php?m=sesion_expirada');
    exit;
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