<?php
declare(strict_types=1);

/**
 * Día 13 — Modelo de Clientes (patrón MVC simple).
 * Todo el acceso a datos vive aquí; el controlador toma las decisiones y la
 * vista solo presenta. El documento y el correo son únicos: las excepciones
 * de duplicado se detectan en PDO (código 23000) y se traducen a mensajes
 * entendibles por el usuario.
 */

final class ClienteModelo
{
    private const COLUMNAS_ORDEN = ['nombre', 'documento', 'correo', 'telefono', 'id'];

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
        $filtro = $this->columnaOrden($orden);
        $dir = strtoupper($direccion) === 'DESC' ? 'DESC' : 'ASC';

        $sql = "SELECT id, nombre, documento, correo, telefono
                FROM clientes
                WHERE nombre LIKE :busqueda
                   OR documento LIKE :busqueda2
                   OR correo LIKE :busqueda3
                ORDER BY {$filtro} {$dir}
                LIMIT :porPagina OFFSET :offset";

        $st = $this->pdo->prepare($sql);
        $st->bindValue(':busqueda', '%' . $busqueda . '%', PDO::PARAM_STR);
        $st->bindValue(':busqueda2', '%' . $busqueda . '%', PDO::PARAM_STR);
        $st->bindValue(':busqueda3', '%' . $busqueda . '%', PDO::PARAM_STR);
        $st->bindValue(':porPagina', $porPagina, PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, PDO::PARAM_INT);
        $st->execute();

        return $st->fetchAll();
    }

    public function contar(string $busqueda = ''): int
    {
        $st = $this->pdo->prepare(
            "SELECT COUNT(*) FROM clientes
             WHERE nombre LIKE :busqueda OR documento LIKE :busqueda2 OR correo LIKE :busqueda3"
        );
        $st->bindValue(':busqueda', '%' . $busqueda . '%', PDO::PARAM_STR);
        $st->bindValue(':busqueda2', '%' . $busqueda . '%', PDO::PARAM_STR);
        $st->bindValue(':busqueda3', '%' . $busqueda . '%', PDO::PARAM_STR);
        $st->execute();

        return (int) $st->fetchColumn();
    }

    public function obtener(int $id): ?array
    {
        $st = $this->pdo->prepare("SELECT id, nombre, documento, correo, telefono FROM clientes WHERE id = :id");
        $st->execute([':id' => $id]);
        $fila = $st->fetch();

        return $fila === false ? null : $fila;
    }

    /**
     * INSERT. Devuelve false cuando el documento o el correo ya existen
     * (violación de las restricciones UNIQUE, error 23000 de MySQL).
     */
    public function crear(string $nombre, string $documento, string $correo, ?string $telefono): int|false
    {
        try {
            $st = $this->pdo->prepare(
                "INSERT INTO clientes (nombre, documento, correo, telefono)
                 VALUES (:nombre, :documento, :correo, :telefono)"
            );
            $st->execute([
                ':nombre'    => $nombre,
                ':documento' => $documento,
                ':correo'    => $correo,
                ':telefono'  => $telefono === '' ? null : $telefono,
            ]);

            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return false;
            }
            throw $e;
        }
    }

    public function actualizar(int $id, string $nombre, string $documento, string $correo, ?string $telefono): bool
    {
        try {
            $st = $this->pdo->prepare(
                "UPDATE clientes
                 SET nombre = :nombre, documento = :documento, correo = :correo, telefono = :telefono
                 WHERE id = :id"
            );
            $st->execute([
                ':nombre'    => $nombre,
                ':documento' => $documento,
                ':correo'    => $correo,
                ':telefono'  => $telefono === '' ? null : $telefono,
                ':id'        => $id,
            ]);

            return $st->rowCount() > 0;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return false;
            }
            throw $e;
        }
    }

    /**
     * Borrado físico solo cuando no existen pedidos asociados. Si el cliente
     * tiene historial, ON DELETE RESTRICT lo protege: se devuelve false sin
     * romper la integridad referencial.
     */
    public function eliminar(int $id): bool
    {
        try {
            $st = $this->pdo->prepare("DELETE FROM clientes WHERE id = :id");
            $st->execute([':id' => $id]);

            return $st->rowCount() > 0;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return false;
            }
            throw $e;
        }
    }

    private function columnaOrden(string $orden): string
    {
        return in_array(strtolower($orden), self::COLUMNAS_ORDEN, true)
            ? strtolower($orden)
            : 'nombre';
    }
}