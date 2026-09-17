const moneda = new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 });

const masCaro = productos.reduce((mayor, p) => (p.precio > mayor.precio ? p : mayor));
console.log("1. Producto más caro:", masCaro.nombre, moneda.format(masCaro.precio));

const unidadesPorCategoria = productos.reduce((acumulado, p) => {
  acumulado[p.categoria] = (acumulado[p.categoria] ?? 0) + p.stock;
  return acumulado;
}, {});
console.log("2. Total de unidades por categoría:", unidadesPorCategoria);

const stockBajo = productos.filter((p) => p.stock < 5);
console.log("3. Productos con stock menor a cinco:");
console.table(stockBajo.map((p) => ({ nombre: p.nombre, categoria: p.categoria, stock: p.stock, precio: moneda.format(p.precio) })));

const promedioPrecio = productos.reduce((suma, p) => suma + p.precio, 0) / productos.length;
console.log("4. Promedio de precio:", moneda.format(Math.round(promedioPrecio)));

const disponibles = productos.filter((p) => p.stock > 0);
const valorInventario = disponibles
  .map((p) => p.precio * p.stock)
  .reduce((suma, valor) => suma + valor, 0);
console.log("Bono — valor del inventario disponible:", moneda.format(valorInventario));

console.log("Productos de prueba:");
console.table(productos);
console.log("Pedidos de prueba:");
console.table(pedidos);