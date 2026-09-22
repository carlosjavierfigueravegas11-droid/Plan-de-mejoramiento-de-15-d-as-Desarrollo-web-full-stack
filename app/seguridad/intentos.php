<?php
declare(strict_types=1);

/**
 * Día 10 — Limitación de intentos de acceso.
 * Tras cinco fallos en quince minutos la cuenta queda bloqueada temporalmente.
 * El mensaje que se muestra es genérico para no permitir enumerar cuentas.
 */

const MAX_INTENTOS = 5;
const VENTANA_MINUTOS = 15;

function registrarIntento(PDO $pdo, string $correo, bool $exitoso): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $st = $pdo->prepare(
        "INSERT INTO intentos_acceso (correo, exitoso, ip) VALUES (:correo, :exitoso, :ip)"
    );
    $st->execute([
        ':correo' => $correo,
        ':exitoso' => $exitoso ? 1 : 0,
        ':ip' => $ip,
    ]);
}

function contarIntentosFallidos(PDO $pdo, string $correo): int
{
    $st = $pdo->prepare(
        "SELECT COUNT(*) AS total
         FROM intentos_acceso
         WHERE correo = :correo
           AND exitoso = 0
           AND intentado_en >= (NOW() - INTERVAL :minutos MINUTE)"
    );
    $st->bindValue(':correo', $correo, PDO::PARAM_STR);
    $st->bindValue(':minutos', VENTANA_MINUTOS, PDO::PARAM_INT);
    $st->execute();
    return (int) $st->fetch()['total'];
}

function bloquearCuenta(PDO $pdo, int $usuarioId): void
{
    $st = $pdo->prepare(
        "UPDATE usuarios SET bloqueado_hasta = (NOW() + INTERVAL :minutos MINUTE) WHERE id = :id"
    );
    $st->bindValue(':minutos', VENTANA_MINUTOS, PDO::PARAM_INT);
    $st->bindValue(':id', $usuarioId, PDO::PARAM_INT);
    $st->execute();
}

function cuentaBloqueada(PDO $pdo, string $correo): bool
{
    $st = $pdo->prepare(
        "SELECT bloqueado_hasta FROM usuarios
         WHERE correo = :correo
           AND bloqueado_hasta IS NOT NULL
           AND bloqueado_hasta > NOW()
         LIMIT 1"
    );
    $st->execute([':correo' => $correo]);
    return $st->fetch() !== false;
}