<?php
declare(strict_types=1);

/**
 * Día 9 — Consulta de usuario con sentencia preparada.
 * Ejemplo de la figura: la entrada (correo) siempre es dato, nunca instrucción.
 */

function buscarPorCorreo(PDO $pdo, string $correo): ?array
{
    $sql = "SELECT id, nombre, correo, clave_hash, rol
            FROM usuarios
            WHERE correo = :correo
            LIMIT 1";

    $st = $pdo->prepare($sql);
    $st->execute([':correo' => $correo]);

    $fila = $st->fetch();
    return $fila === false ? null : $fila;
}