<?php
declare(strict_types=1);

/**
 * Día 10 — Protección CSRF.
 * Token aleatorio por sesión (random_bytes), incrustado en cada formulario
 * y verificado con hash_equals (comparación en tiempo constante).
 */

function tokenCsrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function validarCsrf(?string $enviado): bool
{
    return is_string($enviado)
        && !empty($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $enviado);
}