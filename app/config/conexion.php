<?php
declare(strict_types=1);

/**
 * Día 9 — Conexión reutilizable con PDO.
 * Patrón de fichero único: se llama una vez y se comparte en todo el proyecto.
 * La configuración real llega de credenciales.php, que NO se sube al repositorio
 * (ver .gitignore). credenciales.example.php muestra la forma.
 */
final class Conexion
{
    private static ?PDO $pdo = null;

    public static function obtener(): PDO
    {
        if (self::$pdo === null) {
            /** @var array{host:string,puerto:int,bd:string,usuario:string,clave:string} $cfg */
            $cfg = require __DIR__ . '/credenciales.php';
            $dsn = "mysql:host={$cfg['host']};port={$cfg['puerto']};dbname={$cfg['bd']};charset=utf8mb4";

            self::$pdo = new PDO($dsn, $cfg['usuario'], $cfg['clave'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$pdo;
    }
}