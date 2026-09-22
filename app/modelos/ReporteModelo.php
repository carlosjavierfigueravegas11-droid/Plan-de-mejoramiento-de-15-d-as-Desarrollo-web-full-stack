<?php
declare(strict_types=1);

/**
 * Día 15 — Modelo de reportes (patrón MVC simple).
 * Cada reporte se alimenta de una VISTA creada en sql/vistas.sql (v_reportes_*),
 * así todo el SQL de negocio vive en la base de datos. Las consultas de aquí
 * solo PARAMETRIZAN por rango de fechas o por categoría; no repiten fórmulas.
 */

final class ReporteModelo
{
    public function __construct(private PDO $pdo)
    {
    }

    /** Reporte 1 — Ventas por categoría (v_reportes_ventas_categoria) + totales. */
    public function ventasPorCategoria(?string $desde, ?string $hasta): array
    {
        $st = $this->pdo->prepare(
            "SELECT categoria,
                    COALESCE(SUM(unidades), 0) AS unidades,
                    COALESCE(SUM(subtotal), 0) AS total
             FROM v_reportes_ventas_categoria
             WHERE (:p_desde = '' OR fecha >= :p_desde2)
               AND (:p_hasta = '' OR fecha <= :p_hasta2)
             GROUP BY categoria
             ORDER BY total DESC"
        );
        $st->execute([
            ':p_desde'  => (string) $desde,
            ':p_desde2' => (string) $desde,
            ':p_hasta'  => (string) $hasta,
            ':p_hasta2' => (string) $hasta,
        ]);
        $filas = $st->fetchAll();

        $unidades = 0;
        $total = 0.0;
        foreach ($filas as $fila) {
            $unidades += (int) $fila['unidades'];
            $total += (float) $fila['total'];
        }

        return [
            'titulo'   => 'Reporte de ventas por categoría',
            'ruta'     => 'ventas-categoria',
            'columnas' => ['Categoría', 'Unidades vendidas', 'Total vendido'],
            'filas'    => $filas,
            'totales'  => ['unidades' => $unidades, 'total' => $total],
            'grafico'  => [
                'tipo'   => 'doughnut',
                'etiquetas' => array_column($filas, 'categoria'),
                'valores'   => array_map(fn (array $f) => (float) $f['total'], $filas),
            ],
        ];
    }

    /** Reporte 2 — Inventario con stock crítico (v_stock_critico) + totales. */
    public function stockCritico(?string $categoria): array
    {
        $st = $this->pdo->prepare(
            "SELECT id, nombre, categoria, stock
             FROM v_stock_critico
             WHERE (:p_cat = '' OR categoria = :p_cat2)
             ORDER BY stock ASC, nombre ASC"
        );
        $st->execute([':p_cat' => (string) $categoria, ':p_cat2' => (string) $categoria]);
        $filas = $st->fetchAll();

        $productos = count($filas);
        $piezas = 0;
        foreach ($filas as $fila) {
            $piezas += (int) $fila['stock'];
        }

        // Barras horizontales: cuántas piezas faltan para llegar al punto de reposición (5).
        $grafico = [
            'tipo'   => 'bar',
            'etiquetas' => array_column($filas, 'nombre'),
            'valores'   => array_map(function (array $f): float {
                return max(0, 5 - (int) $f['stock']);
            }, $filas),
            'horizontal' => true,
        ];

        return [
            'titulo'   => 'Reporte de inventario con stock crítico',
            'ruta'     => 'stock-critico',
            'columnas' => ['Código', 'Producto', 'Categoría', 'Stock actual'],
            'filas'    => $filas,
            'totales'  => ['productos' => $productos, 'piezas' => $piezas],
            'grafico'  => $grafico,
        ];
    }

    /** Reporte 3 — Pedidos por cliente (v_reportes_pedidos_cliente) + totales. */
    public function pedidosPorCliente(?string $desde, ?string $hasta): array
    {
        $st = $this->pdo->prepare(
            "SELECT cliente, documento, COUNT(*) AS pedidos,
                    COALESCE(SUM(total), 0) AS total
             FROM v_reportes_pedidos_cliente
             WHERE (:p_desde = '' OR fecha >= :p_desde2)
               AND (:p_hasta = '' OR fecha <= :p_hasta2)
             GROUP BY cliente, documento
             ORDER BY total DESC"
        );
        $st->execute([
            ':p_desde'  => (string) $desde,
            ':p_desde2' => (string) $desde,
            ':p_hasta'  => (string) $hasta,
            ':p_hasta2' => (string) $hasta,
        ]);
        $filas = $st->fetchAll();

        $pedidos = 0;
        $total = 0.0;
        foreach ($filas as $fila) {
            $pedidos += (int) $fila['pedidos'];
            $total += (float) $fila['total'];
        }

        return [
            'titulo'   => 'Reporte de pedidos por cliente',
            'ruta'     => 'pedidos-cliente',
            'columnas' => ['Cliente', 'Documento', 'Pedidos', 'Total gastado'],
            'filas'    => $filas,
            'totales'  => ['pedidos' => $pedidos, 'total' => $total],
            'grafico'  => [
                'tipo'   => 'bar',
                'etiquetas' => array_column($filas, 'cliente'),
                'valores'   => array_map(fn (array $f) => (float) $f['total'], $filas),
                'horizontal' => true,
            ],
        ];
    }

    /** Categorías activas para el filtro del reporte de stock. */
    public function categorias(): array
    {
        return $this->pdo->query(
            "SELECT id, nombre FROM categorias ORDER BY nombre"
        )->fetchAll();
    }
}