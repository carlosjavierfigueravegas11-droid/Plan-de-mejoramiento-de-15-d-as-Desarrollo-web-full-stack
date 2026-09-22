<?php
declare(strict_types=1);

/**
 * Día 9 — Consultas a productos con sentencias preparadas.
 * Los datos (parámetros) viajan EN la instrucción en el caso malo;
 * con PREPARE el servidor separa instrucción de datos siempre.
 */

function listarProductos(PDO $pdo): array
{
    $sql = "SELECT p.id, p.nombre, p.precio, p.stock, c.nombre AS categoria
            FROM productos p
            INNER JOIN categorias c ON c.id = p.categoria_id
            ORDER BY p.nombre";
    return $pdo->query($sql)->fetchAll();
}

function buscarProductos(PDO $pdo, string $texto, int $limite = 50): array
{
    $sql = "SELECT p.id, p.nombre, p.precio, p.stock, c.nombre AS categoria
            FROM productos p
            INNER JOIN categorias c ON c.id = p.categoria_id
            WHERE p.nombre LIKE :texto
            ORDER BY p.nombre
            LIMIT :limite";

    $st = $pdo->prepare($sql);
    $st->bindValue(':texto', '%' . $texto . '%', PDO::PARAM_STR);
    $st->bindValue(':limite', $limite, PDO::PARAM_INT);
    $st->execute();

    return $st->fetchAll();
}