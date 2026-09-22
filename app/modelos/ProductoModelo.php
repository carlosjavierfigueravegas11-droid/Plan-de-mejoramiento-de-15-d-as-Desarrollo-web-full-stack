<?php
declare(strict_types=1);

/**
 * Día 9-10 — CRUD de productos con sentencias preparadas.
 * crear/actualizar siempre usan PDO::prepare(); eliminar es un borrado lógico (activo=0)
 * para conservar el historial en pedidos. Jamás se concatenan variables en el SQL.
 */

function listarProductos(PDO $pdo): array
{
    $sql = "SELECT p.id, p.nombre, p.precio, p.stock, c.nombre AS categoria
            FROM productos p
            INNER JOIN categorias c ON c.id = p.categoria_id
            WHERE p.activo = 1
            ORDER BY p.nombre";
    return $pdo->query($sql)->fetchAll();
}

function buscarProductos(PDO $pdo, string $texto, int $limite = 50): array
{
    $sql = "SELECT p.id, p.nombre, p.precio, p.stock, c.nombre AS categoria
            FROM productos p
            INNER JOIN categorias c ON c.id = p.categoria_id
            WHERE p.activo = 1 AND p.nombre LIKE :texto
            ORDER BY p.nombre
            LIMIT :limite";

    $st = $pdo->prepare($sql);
    $st->bindValue(':texto', '%' . $texto . '%', PDO::PARAM_STR);
    $st->bindValue(':limite', $limite, PDO::PARAM_INT);
    $st->execute();

    return $st->fetchAll();
}

function obtenerCategoriaId(PDO $pdo, string $nombre): ?int
{
    $st = $pdo->prepare("SELECT id FROM categorias WHERE nombre = :nombre LIMIT 1");
    $st->execute([':nombre' => $nombre]);
    $fila = $st->fetch();
    return $fila === false ? null : (int) $fila['id'];
}

function crearProducto(PDO $pdo, string $nombre, string $categoria, float $precio, int $stock): int
{
    $categoriaId = obtenerCategoriaId($pdo, $categoria);
    if ($categoriaId === null) {
        throw new DomainException('Categoría no válida.');
    }

    $st = $pdo->prepare(
        "INSERT INTO productos (categoria_id, nombre, precio, stock, activo)
         VALUES (:categoria_id, :nombre, :precio, :stock, 1)"
    );
    $st->execute([
        ':categoria_id' => $categoriaId,
        ':nombre'       => $nombre,
        ':precio'       => $precio,
        ':stock'        => $stock,
    ]);
    return (int) $pdo->lastInsertId();
}

function actualizarProducto(PDO $pdo, int $id, string $nombre, string $categoria, float $precio, int $stock): bool
{
    $categoriaId = obtenerCategoriaId($pdo, $categoria);
    if ($categoriaId === null) {
        throw new DomainException('Categoría no válida.');
    }

    $st = $pdo->prepare(
        "UPDATE productos
         SET categoria_id = :categoria_id, nombre = :nombre, precio = :precio, stock = :stock
         WHERE id = :id AND activo = 1"
    );
    $st->execute([
        ':categoria_id' => $categoriaId,
        ':nombre'       => $nombre,
        ':precio'       => $precio,
        ':stock'        => $stock,
        ':id'           => $id,
    ]);
    return $st->rowCount() > 0;
}

function eliminarProducto(PDO $pdo, int $id): bool
{
    $st = $pdo->prepare("UPDATE productos SET activo = 0 WHERE id = :id");
    $st->execute([':id' => $id]);
    return $st->rowCount() > 0;
}