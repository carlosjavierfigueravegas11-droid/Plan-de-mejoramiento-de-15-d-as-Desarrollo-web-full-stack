/**
 * Día 13 — Mejora progresiva sobre el CRUD servidor.
 * La página ya funciona sin JavaScript (formularios POST + POST/Redirect/GET).
 * Si JS está activo, esta capa SOLO carga los datos de la fila en el formulario
 * al pulsar Editar. El guardado y el borrado se hacen siempre con un POST
 * normal del formulario: la confirmación de eliminar la gestiona onsubmit.
 */
(function () {
  "use strict";

  const tabla = document.querySelector("#tabla-productos");
  if (!tabla) return;

  const form = document.querySelector("#form-producto");
  if (!form) return;

  tabla.addEventListener("click", async (e) => {
    const boton = e.target.closest("button[data-accion='editar']");
    if (!boton) return;
    e.preventDefault();

    const id = Number(boton.dataset.id);
    if (!id) return;

    const respuesta = await fetch(`app/rutas/producto.php?id=${id}`, {
      headers: { Accept: "application/json" }
    });
    if (!respuesta.ok) return;
    const p = await respuesta.json();
    if (!p || p.id != id) return;

    form.elements.nombre.value = p.nombre;
    form.elements.categoria.value = String(p.categoria_id);
    form.elements.precio.value = p.precio;
    form.elements.stock.value = p.stock;
    form.elements.id.value = p.id;
    const botonGuardar = form.querySelector("button[type='submit']");
    if (botonGuardar) botonGuardar.textContent = "Guardar cambios";
    form.scrollIntoView({ behavior: "smooth", block: "center" });
    form.elements.nombre.focus();
  });
})();