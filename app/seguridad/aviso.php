<?php
declare(strict_types=1);

/**
 * Día 13 — Mensajes efímeros (flash) en sesión.
 * Patrón POST / Redirect / GET: el controlador guarda $_SESSION['aviso'],
 * redirige con 303 y la vista lo consume UNA vez borrándolo. Recargar con F5
 * no vuelve a mostrar el mensaje ni a reenviar el formulario.
 */

function guardarAviso(string $tipo, string $texto): void
{
    $_SESSION['aviso'] = ['tipo' => $tipo, 'texto' => $texto];
}

/** Lee el aviso pendiente y lo borra en el mismo acceso (mensaje de un solo uso). */
function consumirAviso(): ?array
{
    if (empty($_SESSION['aviso']) || !is_array($_SESSION['aviso'])) {
        return null;
    }
    $aviso = $_SESSION['aviso'];
    unset($_SESSION['aviso']);

    return $aviso;
}

/** Redirección 303 (See Other) para cerrar el ciclo POST / Redirect / GET. */
function redirigirA(string $destino): never
{
    header('Location: ' . BASE_URL . $destino, true, 303);
    exit;
}