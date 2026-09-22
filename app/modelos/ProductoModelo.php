<?php
declare(strict_types=1);

/**
 * Día 13 — Modelo de Productos (capa modelo del patrón MVC simple).
 * Cada operación de persistencia tiene su método: crear (INSERT), leer
 * (SELECT con JOIN y LIMIT/OFFSET), actualizar (UPDATE) y borrado lógico
 * (UPDATE activo = 0 para conservar el historial en pedidos).
 * El orden de las columnas llega por lista blanca (COLUMNAS_ORDEN) y la
 * dirección solo admite ASC/DESC: jamás se interpola un valor del usuario
 * directamente en el SQL.
 *
 * La tabla tiene 22 productos activos; la parte visible usa paginación de
 * diez registros por página (porPagina), con LIMIT :porPagina OFFSET :offset.
 */

final class ProductoModelo
{
    private const COLUMNAS_ORDEN = ['nombre', 'categoria', 'precio', 'stock', 'id'];

    public function __construct(private PDO $pdo)
    {
    }

    public function listar(
        string $busqueda = '',
        int $pagina = 1,
        int $porPagina = 10,
        string $orden = 'nombre',
        string $direccion = 'ASC'
    ): array {
        $offset = max(0, ($pagina - 1) * $porPagina);
        $filtro = strtolower($orden) === 'categoria' ? 'c.nombre' : 'p.' . $this->columnaOrden($orden);
        $dir = strtoupper($direccion) === 'DESC' ? 'DESC' : 'ASC';

        $sql = "SELECT p.id, p.nombre, p.precio, p.stock, c.nombre AS categoria, c.id AS categoria_id
                FROM productos p
                INNER JOIN categorias c ON c.id = p.categoria_id
                WHERE p.activo = 1
                  AND (p.nombre LIKE :busqueda OR c.nombre LIKE :busqueda2)
                ORDER BY {$filtro} {$dir}, p.nombre
                LIMIT :porPagina OFFSET :offset";

        $st = $this->pdo->prepare($sql);
        $st->bindValue(':busqueda', '%' . $busqueda . '%', PDO::PARAM_STR);
        $st->bindValue(':busqueda2', '%' . $busqueda . '%', PDO::PARAM_STR);
        $st->bindValue(':porPagina', $porPagina, PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, PDO::PARAM_INT);
        $st->execute();

        return $st->fetchAll();
    }

    public function contar(string $busqueda = ''): int
    {
        $st = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM productos p
             INNER JOIN categorias c ON c.id = p.categoria_id
             WHERE p.activo = 1
               AND (p.nombre LIKE :busqueda OR c.nombre LIKE :busqueda2)"
        );
        $st->bindValue(':busqueda', '%' . $busqueda . '%', PDO::PARAM_STR);
        $st->bindValue(':busqueda2', '%' . $busqueda . '%', PDO::PARAM_STR);
        $st->execute();

        return (int) $st->fetchColumn();
    }

    public function obtener(int $id): ?array
    {
        $st = $this->pdo->prepare("SELECT id, nombre, categoria_id, precio, stock FROM productos WHERE id = :id");
        $st->execute([':id' => $id]);
        $fila = $st->fetch();

        return $fila === false ? null : $fila;
    }

    public function crear(int $categoriaId, string $nombre, float $precio, int $stock): int
    {
        $st = $this->pdo->prepare(
            "INSERT INTO productos (categoria_id, nombre, precio, stock, activo)
             VALUES (:categoria_id, :nombre, :precio, :stock, 1)"
        );
        $st->execute([
            ':categoria_id' => $categoriaId,
            ':nombre'       => $nombre,
            ':precio'       => $precio,
            ':stock'        => $stock,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function actualizar(int $id, int $categoriaId, string $nombre, float $precio, int $stock): bool
    {
        $st = $this->pdo->prepare(
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

    /** Borrado lógico: marca activo = 0 para no romper el historial de ventas. */
    public function desactivar(int $id): bool
    {
        $st = $this->pdo->prepare("UPDATE productos SET activo = 0 WHERE id = :id");
        $st->execute([':id' => $id]);

        return $st->rowCount() > 0;
    }

    public function categorias(): array
    {
        return $this->pdo->query("SELECT id, nombre FROM categorias ORDER BY nombre")->fetchAll();
    }

    private function columnaOrden(string $orden): string
    {
        return in_array(strtolower($orden), self::COLUMNAS_ORDEN, true)
            ? strtolower($orden)
            : 'nombre';
    }
}