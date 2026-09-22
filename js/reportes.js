/* Día 15 — Reportes: dibuja el gráfico del reporte con Chart.js y exporta a PDF
 * incrustando el gráfico con canvas.toDataURL() (igual HTML pantalla/PDF). */
(function () {
  'use strict';

  function formatoPeso() {
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 });
  }

  function dibujarGrafico() {
    var canvas = document.getElementById('grafico-reporte');
    if (!canvas) return;
    var etiquetas = JSON.parse(canvas.dataset.etiquetas || '[]');
    var valores = JSON.parse(canvas.dataset.valores || '[]');
    var tipo = canvas.dataset.tipo || 'bar';
    var horizontal = canvas.dataset.ejes === 'horizontal';

    var paleta = ['#F7C52A', '#1A1A1A', '#3A5BA0', '#0E7C66', '#B03A5B', '#8A6D3B'];
    var grafico = new Chart(canvas, {
      type: tipo,
      data: {
        labels: etiquetas,
        datasets: [{
          data: valores,
          backgroundColor: paleta,
          borderColor: '#FFFFFF',
          borderWidth: 2
        }]
      },
      options: {
        maintainAspectRatio: false,
        layout: { padding: 8 },
        plugins: {
          legend: { display: tipo === 'doughnut', position: 'bottom' },
          tooltip: {
            callbacks: {
              label: function (ctx) {
                var etiqueta = ctx.label || '';
                var valor = ctx.parsed.y !== undefined ? ctx.parsed.y : (ctx.parsed || 0);
                var texto = etiqueta + ': ' + formatoPeso().format(valor);
                if (tipo === 'doughnut') {
                  var total = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                  var pct = total ? Math.round((valor / total) * 100) : 0;
                  texto += ' (' + pct + '%)';
                }
                return texto;
              }
            }
          }
        },
        scales: (tipo === 'doughnut') ? {} : {
          x: horizontal ? { ticks: { color: '#444' } } : {
            title: { display: true, text: tipo === 'bar' && !horizontal ? 'Valor ($)' : '' },
            ticks: { callback: function (v) { return typeof v === 'number' ? formatoPeso().format(v) : v; } }
          },
          y: horizontal
            ? { title: { display: true, text: 'Valor ($)' }, ticks: { callback: function (v) { return formatoPeso().format(v); } } }
            : { title: { display: true, text: 'Categorías' } }
        }
      }
    });

    /* canvas.toDataURL(): convierte el gráfico de Chart.js en una imagen
     * incrustable dentro del mismo HTML que se envía a exportar-pdf.php. */
    var btn = document.getElementById('btn-pdf');
    if (btn) {
      btn.addEventListener('click', function () {
        var dataUrl;
        try {
          dataUrl = canvas.toDataURL('image/png');
        } catch (e) {
          alert('No se pudo capturar el gráfico: ' + e.message);
          return;
        }
        var campo = document.querySelector('#form-pdf input[name="grafico"]');
        if (campo) campo.value = dataUrl;
        document.getElementById('form-pdf').submit();
      });
    }
    window.__graficoReporte = grafico;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', dibujarGrafico);
  } else {
    dibujarGrafico();
  }
})();