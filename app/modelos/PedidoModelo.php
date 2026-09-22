<?php
declare(strict_types=1);

/**
 * Día 13 — Modelo de Pedidos (patrón MVC simple).
 * registrar() trabaja dentro de una transacción: si falla cualquier paso
 * (INSERT del pedido, INSERT del detalle o el descuento de stock), se hace
 * rollBack y no queda nada a medias: "o todo, o nada".
 * El total de cada pedido no se almacena en la tabla pedidos; se calcula
 * con SUM(cantidad * precio_unitario) sobre detalle_pedidos tal como hacen
 * las vistas del día 14.
 */

final class PedidoModelo
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Registra un pedido y su detalle descontando stock, todo dentro de una
     * transacción. $items es una lista de ['producto_id' => int, 'cantidad' => int].
     * Devuelve el id del pedido creado. Lanza PDOException si algo falla.
     */
    public function registrar(int $clienteId, array $items): int
    {
        $this->pdo->beginTransaction();

        try {
            $stPedido = $this->pdo->prepare(
                "INSERT INTO pedidos (cliente_id, fecha, estado)
                 VALUES (:cliente, NOW(), 'pendiente')"
            );
            $stPedido->execute([':cliente' => $clienteId]);
            $pedidoId = (int) $this->pdo->lastInsertId();

            // Precio unitario que se venderá: se congela al momento del pedido.
            $stPrecio = $this->pdo->prepare("SELECT precio FROM productos WHERE id = :id FOR UPDATE");
            $stDetalle = $this->pdo->prepare(
                "INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio_unitario)
                 VALUES (:pedido, :producto, :cantidad, :precio)"
            );
            $stStock = $this->pdo->prepare(
                "UPDATE productos SET stock = stock - :cantidad WHERE id = :id"
            );

            foreach ($items as $item) {
                $productoId = (int) $item['producto_id'];
                $cantidad = (int) $item['cantidad'];

                $stPrecio->execute([':id' => $productoId]);
                $precio = (float) ($stPrecio->fetchColumn());
                if ($precio <= 0) {
                    throw new PDOException('Producto no encontrado o sin precio.');
                }

                $stDetalle->execute([
                    ':pedido'   => $pedidoId,
                    ':producto' => $productoId,
                    ':cantidad' => $cantidad,
                    ':precio'   => $precio,
                ]);
                $stStock->execute([':cantidad' => $cantidad, ':id' => $productoId]);
            }

            $this->pdo->commit();

            return $pedidoId;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /** Pedidos recientes con cliente y total calculado (sin cancelados). */
    public function historial(int $limite = 15): array
    {
        $st = $this->pdo->query(
            "SELECT p.id, p.fecha, p.estado, c.nombre AS cliente,
                    SUM(dp.cantidad * dp.precio_unitario) AS total
             FROM pedidos p
             INNER JOIN clientes c ON c.id = p.cliente_id
             INNER JOIN detalle_pedidos dp ON dp.pedido_id = p.id
             WHERE p.estado <> 'cancelado'
             GROUP BY p.id, p.fecha, p.estado, c.nombre
             ORDER BY p.fecha DESC, p.id DESC
             LIMIT " . (int) $limite
        );

        return $st->fetchAll();
    }

    /**
     * Detalle de un pedido. Se une a productos por el lado izquierdo para que
     * un producto desactivado (activo = 0) siga apareciendo en el histórico,
     * demostrando que el borrado lógico no rompe la integridad referencial.
     */
    public function detalle(int $pedidoId): array
    {
        $st = $this->pdo->prepare(
            "SELECT dp.producto_id, pr.nombre AS producto, pr.activo,
                    dp.cantidad, dp.precio_unitario,
                    dp.cantidad * dp.precio_unitario AS subtotal
             FROM detalle_pedidos dp
             LEFT JOIN productos pr ON pr.id = dp.producto_id
             WHERE dp.pedido_id = :pedido
             ORDER BY pr.nombre"
        );
        $st->execute([':pedido' => $pedidoId]);

        return $st->fetchAll();
    }

    public function tienePedidos(int $clienteId): bool
    {
        $st = $this->pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE cliente_id = :cliente");
        $st->execute([':cliente' => $clienteId]);

        return (int) $st->fetchColumn() > 0;
    }
}