// Tablero en vivo (días 12 y 14): consume api/graficos.php y dibuja con Chart.js.
// Contadores animados, sparklines, selector de rango, refresco periódico y
// micro-interacciones. Todas las animaciones respetan prefers-reduced-motion.
(function () {
  'use strict';

  var REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var PALETA = ['#F7C52A', '#15506B', '#1E6B26', '#945200', '#7A5400', '#9B1C31'];
  var rangoActivo = 30;
  var temporizadorVivo = null;
  var graficos = {};

  var formatoPeso = new Intl.NumberFormat('es-CO', {
    style: 'currency', currency: 'COP', minimumFractionDigits: 0, maximumFractionDigits: 0,
  });
  var SUFIJO_PESO = { group: '.', decimal: ',' };

  function destruir(id) {
    if (graficos[id]) { graficos[id].destroy(); delete graficos[id]; }
  }

  async function cargarDatos() {
    var base = (window.BASE_URL || '/');
    var resp = await fetch(base + 'api/graficos.php?rango=' + rangoActivo, { credentials: 'same-origin' });
    if (!resp.ok) throw new Error('API de gráficos no disponible (' + resp.status + ')');
    return resp.json();
  }

  // ---------- Contadores animados (count-up) ----------
  function animarContador(el, destino, esMoneda) {
    var enTexto = el.textContent;
    var inicioTxt = esMoneda ? enTexto.replace(/[^\d]/g, '') : enTexto;
    var desde = parseInt(inicioTxt, 10) || 0;
    if (REDUCED) { el.textContent = esMoneda ? formatoPeso.format(destino) : String(destino); return; }
    var duracion = 800;
    var t0 = null;
    function paso(t) {
      if (t0 === null) t0 = t;
      var p = Math.min((t - t0) / duracion, 1);
      var piso = 1 - Math.pow(1 - p, 3); // ease-out cúbico
      var actual = Math.round(desde + (destino - desde) * piso);
      el.textContent = esMoneda ? formatoPeso.format(actual) : String(actual);
      if (p < 1) requestAnimationFrame(paso);
    }
    requestAnimationFrame(paso);
  }

  function pintarContadores(datos) {
    var r = datos.resumen;
    animarContador(document.querySelector('[data-conteo="productos"]'), r.productos, false);
    animarContador(document.querySelector('[data-conteo="pedidos"]'), r.pedidos, false);
    animarContador(document.querySelector('[data-conteo="ventas"]'), r.ventas, true);
    animarContador(document.querySelector('[data-conteo="stock"]'), r.stock, false);
    var meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    var hoy = new Date();
    document.getElementById('resumen-titulo').textContent =
      'Resumen · últimos ' + rangoActivo + ' días (' + meses[hoy.getMonth()] + ')';
  }

  // ---------- Sparklines en las tarjetas ----------
  function dibujarSpark(destino, serie, color) {
    var caja = document.querySelector('[data-spark="' + destino + '"]');
    if (!caja || !serie.length) return;
    var ancho = 140, alto = 32;
    var canvas = document.createElement('canvas');
    canvas.width = ancho; canvas.height = alto;
    caja.appendChild(canvas);
    var ctx = canvas.getContext('2d');
    var max = Math.max.apply(null, serie);
    var min = Math.min.apply(null, serie);
    var rangoV = max - min || 1;
    var pasoX = ancho / Math.max(serie.length - 1, 1);
    ctx.lineWidth = 2;
    ctx.strokeStyle = color;
    ctx.beginPath();
    serie.forEach(function (v, i) {
      var x = i * pasoX;
      var y = alto - 4 - ((v - min) / rangoV) * (alto - 10);
      i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
    });
    ctx.stroke();
  }

  function pintarSparks(datos) {
    var pedidosSerie = datos.serieVentas.pedidos;
    var ventasSerie = datos.serieVentas.valores;
    dibujarSpark('pedidos', pedidosSerie, PALETA[1]);
    dibujarSpark('ventas', ventasSerie, PALETA[3]);
  }

  // ---------- Gráficos ----------
  function graficoBarrasVentas(canvas, datos) {
    destruir('ventas');
    graficos.ventas = new Chart(canvas, {
      type: 'bar',
      data: {
        labels: datos.ventasMes.etiquetas.map(function (p) {
          var partes = p.split('-');
          var meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
          return meses[parseInt(partes[1], 10) - 1] + ' ' + partes[0].slice(2);
        }),
        datasets: [{
          label: 'Ventas',
          data: datos.ventasMes.valores,
          backgroundColor: PALETA[1],
          borderRadius: 6,
        }],
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        animation: REDUCED ? false : { duration: 900 },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: 'rgba(26,26,26,0.92)', titleColor: '#FAF9F4',
            callbacks: { label: function (ctx) { return 'Ventas: ' + formatoPeso.format(ctx.parsed.y); } },
          },
        },
        scales: {
          y: { ticks: { callback: function (v) { return '$' + (v / 1e6).toFixed(1) + 'M'; } }, grid: { color: 'rgba(0,0,0,0.06)' } },
          x: { grid: { display: false } },
        },
      },
    });
  }

  function graficoDonaCategorias(canvas, datos) {
    destruir('categorias');
    var total = datos.categorias.valores.reduce(function (a, b) { return a + b; }, 0);
    graficos.categorias = new Chart(canvas, {
      type: 'doughnut',
      data: {
        labels: datos.categorias.etiquetas.map(function (nombre, i) {
          var pct = total > 0 ? Math.round((datos.categorias.valores[i] / total) * 100) : 0;
          return nombre + ' · ' + pct + ' %';
        }),
        datasets: [{
          data: datos.categorias.valores,
          backgroundColor: PALETA,
          borderColor: '#FFFFFF', borderWidth: 2,
        }],
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        cutout: '62%',
        animation: REDUCED ? false : { animateRotate: true, duration: 1100 },
        plugins: {
          tooltip: {
            backgroundColor: 'rgba(26,26,26,0.92)', titleColor: '#FAF9F4',
            callbacks: { label: function (ctx) { return ctx.label + ': ' + formatoPeso.format(ctx.parsed); } },
          },
        },
      },
    });
  }

  function graficoLineaPedidos(canvas, datos) {
    destruir('pedidos');
    graficos.pedidos = new Chart(canvas, {
      type: 'line',
      data: {
        labels: datos.pedidosPorDia.etiquetas.map(function (d) { return 'Día ' + d; }),
        datasets: [{
          label: 'Pedidos',
          data: datos.pedidosPorDia.valores,
          borderColor: PALETA[0],
          backgroundColor: 'rgba(247,197,42,0.25)',
          fill: true, tension: 0.35, pointRadius: 4, pointBackgroundColor: PALETA[2],
        }],
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        animation: REDUCED ? false : { duration: 900 },
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(0,0,0,0.06)' } },
          x: { grid: { display: false } },
        },
      },
    });
  }

  function graficoAreaVentas(canvas, datos) {
    destruir('area');
    var serie = datos.serieVentas;
    if (!serie.etiquetas.length) {
      graficos.area = null;
      return;
    }
    var grad = canvas.getContext('2d').createLinearGradient(0, 0, 0, 300);
    grad.addColorStop(0, 'rgba(30,107,38,0.35)');
    grad.addColorStop(1, 'rgba(30,107,38,0.02)');
    graficos.area = new Chart(canvas, {
      type: 'line',
      data: {
        labels: serie.etiquetas.map(function (f) {
          var partes = f.split('-');
          return parseInt(partes[2], 10) + '/' + parseInt(partes[1], 10);
        }),
        datasets: [{
          label: 'Ventas',
          data: serie.valores,
          borderColor: PALETA[2],
          backgroundColor: grad,
          fill: true, tension: 0.35, borderWidth: 2.5,
          pointRadius: 0, pointHoverRadius: 5,
        }],
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        animation: REDUCED ? false : { duration: 1100 },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: 'rgba(26,26,26,0.92)', titleColor: '#FAF9F4',
            callbacks: { label: function (ctx) { return 'Ventas: ' + formatoPeso.format(ctx.parsed.y); } },
          },
        },
        scales: {
          y: { ticks: { callback: function (v) { return '$' + (v / 1e5).toFixed(1) + 'K'; } }, grid: { color: 'rgba(0,0,0,0.06)' } },
          x: { grid: { display: false } },
        },
      },
    });
  }

  // ---------- Tablas ----------
  var ETIQUETAS_ESTADO = { pendiente: 'Pendiente', enviado: 'Enviado', entregado: 'Entregado', cancelado: 'Cancelado' };

  function pintarRecientes(datos) {
    var caja = document.getElementById('pedidos-recientes');
    if (!caja) return;
    if (!datos.recientes.length) {
      caja.innerHTML = '<p>Sin pedidos en el periodo.</p>';
      return;
    }
    var filas = datos.recientes.map(function (p) {
      var etiqueta = ETIQUETAS_ESTADO[p.estado] || p.estado;
      return '<tr><td class="reciente-cliente">' + p.cliente + '</td>' +
        '<td class="reciente-fecha">' + p.fecha + '</td>' +
        '<td><span class="badge badge--' + p.estado + '">' + etiqueta + '</span></td>' +
        '<td class="reciente-total">' + formatoPeso.format(Number(p.total)) + '</td></tr>';
    }).join('');
    caja.innerHTML =
      '<table><caption>Últimos pedidos</caption>' +
      '<thead><tr><th>Cliente</th><th>Fecha</th><th>Estado</th><th>Total</th></tr></thead>' +
      '<tbody>' + filas + '</tbody></table>';
  }

  function pintarMejoresClientes(datos) {
    var caja = document.getElementById('mejores-clientes-lista');
    if (!caja) return;
    if (!datos.mejoresClientes || !datos.mejoresClientes.length) {
      caja.innerHTML = '<p>Sin ventas en el periodo.</p>';
      return;
    }
    var filas = datos.mejoresClientes.map(function (cl, i) {
      return '<tr><td class="reciente-puesto">' + (i + 1) + '</td>' +
        '<td>' + cl.cliente + '</td>' +
        '<td class="reciente-num">' + cl.cantidad_pedidos + '</td>' +
        '<td class="reciente-total">' + formatoPeso.format(Number(cl.total_comprado)) + '</td></tr>';
    }).join('');
    caja.innerHTML =
      '<h3>Clientes con mayor compra</h3>' +
      '<table><caption>Mejores clientes por compra acumulada</caption>' +
      '<thead><tr><th>#</th><th>Cliente</th><th>Pedidos</th><th>Total acumulado</th></tr></thead>' +
      '<tbody>' + filas + '</tbody></table>';
  }

  function pintarStock(datos) {
    var caja = document.getElementById('stock-critico-lista');
    if (!caja) return;
    if (!datos.stockCritico.length) {
      caja.innerHTML = '<p>Sin productos bajo el punto de reposición.</p>';
      return;
    }
    var filas = datos.stockCritico.map(function (p) {
      return '<tr><td>' + p.nombre + '</td><td>' + p.categoria + '</td><td class="stock-bajo">' + p.stock + '</td></tr>';
    }).join('');
    caja.innerHTML =
      '<table><caption>Reponer stock</caption>' +
      '<thead><tr><th>Producto</th><th>Categoría</th><th>Stock</th></tr></thead>' +
      '<tbody>' + filas + '</tbody></table>';
  }

  // ---------- Sello de hora y refresco ----------
  function pintarSello(datos) {
    var sello = document.getElementById('sello-hora');
    if (sello) sello.textContent = datos.generado + ' · rango de ' + rangoActivo + ' días';
  }

  async function dibujarTodo(datos) {
    var cVentas = document.getElementById('grafico-ventas');
    var cCategorias = document.getElementById('grafico-categorias');
    var cPedidos = document.getElementById('grafico-pedidos');
    var cArea = document.getElementById('grafico-area');
    if (cVentas) graficoBarrasVentas(cVentas, datos);
    if (cCategorias) graficoDonaCategorias(cCategorias, datos);
    if (cPedidos) graficoLineaPedidos(cPedidos, datos);
    if (cArea) graficoAreaVentas(cArea, datos);
    pintarRecientes(datos);
    pintarMejoresClientes(datos);
    pintarStock(datos);
    pintarSello(datos);
    pintarContadores(datos);
    pintarSparks(datos);
  }

  async function refrescar() {
    var estado = document.getElementById('graficos-estado');
    try {
      var datos = await cargarDatos();
      await dibujarTodo(datos);
      if (estado) estado.textContent = 'ok';
    } catch (err) {
      if (estado) estado.innerHTML = '<p class="mensaje mensaje--error">No se pudieron cargar los gráficos.</p><p data-error="' + String(err && err.message) + '"></p>';
    }
  }

  function activarSelector() {
    var selector = document.getElementById('selector-rango');
    if (!selector) return;
    selector.addEventListener('click', function (ev) {
      var boton = ev.target.closest('button[data-rango]');
      if (!boton || parseInt(boton.dataset.rango, 10) === rangoActivo) return;
      rangoActivo = parseInt(boton.dataset.rango, 10);
      Array.prototype.forEach.call(selector.querySelectorAll('button'), function (b) {
        b.classList.remove('activo');
        b.setAttribute('aria-pressed', String(b === boton));
      });
      boton.classList.add('activo');
      refrescar();
    });
  }

  function activarRefresco() {
    var boton = document.getElementById('boton-refrescar');
    if (boton) boton.addEventListener('click', refrescar);
    if (!REDUCED) {
      temporizadorVivo = setInterval(refrescar, 30000); // simula datos en vivo
    }
  }

  function iniciar() {
    activarSelector();
    activarRefresco();
    refrescar();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciar);
  } else {
    iniciar();
  }
})();