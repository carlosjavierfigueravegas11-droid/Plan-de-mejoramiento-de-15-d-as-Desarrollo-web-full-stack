<?php
declare(strict_types=1);

/**
 * Día 10 — Operaciones sobre usuarios con sentencias preparadas.
 * El hash se genera con password_hash (PASSWORD_DEFAULT) y jamás se guarda la contraseña.
 */

function buscarPorCorreo(PDO $pdo, string $correo): ?array
{
    $sql = "SELECT id, nombre, correo, clave_hash, rol, activo, bloqueado_hasta
            FROM usuarios
            WHERE correo = :correo
            LIMIT 1";

    $st = $pdo->prepare($sql);
    $st->execute([':correo' => $correo]);

    $fila = $st->fetch();
    return $fila === false ? null : $fila;
}

function registrarUsuario(PDO $pdo, string $nombre, string $correo, string $clave, string $rol = 'consultor'): void
{
    $hash = password_hash($clave, PASSWORD_DEFAULT);

    $st = $pdo->prepare(
        "INSERT INTO usuarios (nombre, correo, clave_hash, rol)
         VALUES (:nombre, :correo, :hash, :rol)"
    );
    $st->execute([
        ':nombre' => $nombre,
        ':correo' => $correo,
        ':hash'   => $hash,
        ':rol'    => $rol,
    ]);
}

function existeCorreo(PDO $pdo, string $correo): bool
{
    $st = $pdo->prepare("SELECT 1 FROM usuarios WHERE correo = :correo LIMIT 1");
    $st->execute([':correo' => $correo]);
    return $st->fetch() !== false;
}