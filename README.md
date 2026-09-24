# ISoT — Sistema de Punto de Venta y Gestión de Inventario

Plan de mejoramiento de 15 días · Desarrollo web full-stack · SENA
**Trimestre 3 — Ficha 3428392 — Carlos Javier Figuera Vegas**

Aplicación PHP + MySQL con arquitectura simple de MVC, seguridad en el servidor
(autenticación con huella de navegador, expiración de sesión, CSRF, control de
roles) y un tablero de gestión alimentado por vistas SQL y gráficos Chart.js.

---

## Credenciales de prueba

| Rol | Correo | Contraseña |
|-----|--------|------------|
| Administrador | `carlos.figuera@isot.co` | `Admin2026*` |
| Vendedor | `vendedor@isot.co` | `Vendedor2026*` |
| Consultor | `consultor@isot.co` | `Consultor2026*` |

## Instalación

1. Copiar `app/config/credenciales.example.php` a `app/config/credenciales.php`
   y ajustar host/puerto/BD/usuario/clave (el archivo real NO se sube al
   repositorio; está en `.gitignore`).
2. Crear la base de datos `isot` e importar en orden:
   ```sql
   sql/estructura.sql   -- tablas
   sql/datos.sql        -- datos semilla (usuarios, categorías, productos, clientes, pedidos)
   sql/vistas.sql       -- vistas del tablero y de los reportes
   ```
   `sql/reset-semilla.sql` trunca y repuebla con `datos.sql` para volver al inicio.
3. Servir la carpeta del proyecto (XAMPP/Laragon o PHP integrado):
   ```bash
   php -S 127.0.0.1:8010 -t .
   ```
4. Para la exportación a PDF se usa **Dompdf**, instalado por Composer:
   ```bash
   composer install
   ```
   (Genera `vendor/`, que no se versiona).

## Estructura de carpetas

```
├── api/                 # JSON del tablero y exportaciones (CSV y PDF)
├── app/
│   ├── config/          # app.php (datos del software, rutas públicas) y conexión PDO
│   ├── controladores/   # lógica pura: salir, logout (y diagnósticos)
│   ├── modelos/         # acceso a datos (Producto, Cliente, Pedido, Reporte)
│   ├── rutas/           # endpoints POST (PRG)
│   ├── seguridad/       # guardia de acceso, CSRF, sesión, avisos
│   └── vistas/
│       ├── paginas/     # vistas de los módulos (login, tablero, CRUD, reportes)
│       ├── parciales/   # cabecera, menú, pie y encabezado del tablero
│       └── reportes/    # HTML reutilizable de los reportes formales
├── maquetas/            # prototipos HTML estáticos (incluye movil/)
├── assets/img/          # logo en SVG (pantalla) y PNG (PDF)
├── css/                 # tokens, estilos del panel y reporte (impresión)
├── js/                  # graficos.js, reportes.js y vendor/Chart.js
├── sql/                 # estructura, datos, vistas y reset de semilla
└── CAPTURAS/, ACTIVIDADES/, BITACORA/   # evidencias del plan
```

> Reorden MVC/2026: las páginas públicas ahora viven en `app/vistas/paginas/`
> (`/app/vistas/paginas/…php`), la lógica sin HTML en `app/controladores/` y
> los prototipos HTML estáticos en `maquetas/`. Los enlaces del menú, los
> formularios y las redirecciones usan las constantes `RUTA_PAGINAS` y
> `RUTA_CONTROLADORES` de `app/config/app.php` (`urlPagina()` / `urlControlador()`).

## Reportes (Día 15)

- Tres reportes formales basados en vistas, con encabezado reutilizable
  (logo, filtros, fecha y usuario) y fila de totales.
- Impresión limpia con `@media print` (oculta el menú, fuerza colores y evita
  cortes de filas) y exportación a **PDF** (Dompdf) o **CSV** (fputcsv + BOM).
- El gráfico se incrusta en el PDF con `canvas.toDataURL()`.