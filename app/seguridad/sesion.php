<?php
declare(strict_types=1);

/**
 * Día 11 — Sesión segura y su ciclo de vida.
 * Cookie endurecida (HttpOnly, SameSite=Strict, use_strict_mode), regeneración del ID
 * al autenticar, huella del navegador, expiración por inactividad y por duración máxima.
 */

const INACTIVIDAD_MAX = 1800; // 30 minutos
const SESION_MAX = 28800;     // 8 horas

function iniciarSesionSegura(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;

    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,                       // cookie de sesión (expira al cerrar el navegador)
        'path'     => '/',
        'httponly' => true,                    // inaccesible desde JavaScript
        'secure'   => !empty($_SERVER['HTTPS']),
        'samesite' => 'Strict',
    ]);
    session_name('ISOT_SESS');
    session_start();
}

function usuarioActual(): ?array
{
    return isset($_SESSION['usuario']) && is_array($_SESSION['usuario'])
        ? $_SESSION['usuario']
        : null;
}

/**
 * Abre la sesión tras autenticar correctamente.
 * Regenera el ID inmediatamente: evita la fijación de sesión.
 */
function abrirSesion(array $u): void
{
    iniciarSesionSegura();

    session_regenerate_id(true);

    $_SESSION['usuario'] = [
        'id'     => (int) $u['id'],
        'nombre' => $u['nombre'],
        'correo' => $u['correo'],
        'rol'    => $u['rol'],
    ];
    $_SESSION['inicio'] = time();
    $_SESSION['ultima_actividad'] = time();
    $_SESSION['huella'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
}

/**
 * Cierra la sesión por completo: vacía el arreglo, caduca la cookie y destruye.
 * No basta con session_destroy(); también se debe invalidar la cookie del navegador.
 */
function cerrarSesion(): void
{
    iniciarSesionSegura();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $p['path'],
            $p['domain'],
            $p['secure'],
            $p['httponly']
        );
    }

    session_destroy();
}

/**
 * Renueva el ID al cambiar de privilegios (por si un rol sube o baja).
 */
function renovarSesion(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) return;
    session_regenerate_id(true);
}

/**
 * Declara los roles que la matriz de permisos permite. Devuelve true si el usuario
 * pertenece a alguno de ellos.
 */
function puede(string ...$roles): bool
{
    $rol = $_SESSION['usuario']['rol'] ?? '';
    return in_array($rol, $roles, true);
}