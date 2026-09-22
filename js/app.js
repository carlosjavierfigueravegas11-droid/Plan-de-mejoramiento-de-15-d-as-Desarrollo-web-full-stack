const moneda = new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 });

const botonMenu = document.querySelector(".boton-menu");
const menuLateral = document.querySelector("#menu-lateral");
if (botonMenu && menuLateral) {
  botonMenu.addEventListener("click", () => menuLateral.classList.toggle("abierto"));
}

const tbody = document.querySelector("#tabla-productos tbody");
const buscador = document.querySelector("#buscador");
const formProducto = document.querySelector("#form-producto");
const hayTabla = Boolean(tbody && buscador && formProducto);

const sirviendoDelServidor = location.protocol.startsWith("http");
const API = "app/rutas/productos.php";
let datos = [];
let editandoId = null;

function plantillaProducto(p) {
  const tr = document.createElement("tr");

  const nombre = document.createElement("th");
  nombre.scope = "row";
  nombre.dataset.label = "Nombre";
  nombre.textContent = p.nombre;
  tr.append(nombre);

  const celda = (texto, etiqueta) => {
    const td = document.createElement("td");
    td.dataset.label = etiqueta;
    td.textContent = texto;
    return td;
  };
  tr.append(celda(p.categoria, "Categoría"));
  tr.append(celda(moneda.format(p.precio), "Precio"));
  tr.append(celda(p.stock, "Stock"));

  const acciones = document.createElement("td");
  acciones.dataset.label = "Acciones";
  const btnEditar = document.createElement("button");
  btnEditar.type = "button";
  btnEditar.className = "boton-mini";
  btnEditar.dataset.accion = "editar";
  btnEditar.dataset.id = String(p.id);
  btnEditar.textContent = "Editar";
  const btnEliminar = document.createElement("button");
  btnEliminar.type = "button";
  btnEliminar.className = "boton-mini boton-peligro";
  btnEliminar.dataset.accion = "eliminar";
  btnEliminar.dataset.id = String(p.id);
  btnEliminar.textContent = "Eliminar";
  acciones.append(btnEditar, btnEliminar);
  tr.append(acciones);

  return tr;
}

if (hayTabla) {
  function pintarTabla(lista) {
    if (lista.length === 0) {
      const tr = document.createElement("tr");
      const td = document.createElement("td");
      td.colSpan = 5;
      td.dataset.label = "";
      td.textContent = "Sin resultados.";
      tr.append(td);
      tbody.replaceChildren(tr);
      return;
    }
    tbody.replaceChildren(...lista.map(plantillaProducto));
  }

  async function cargarDesdeServidor() {
    const respuesta = await fetch(API, { headers: { Accept: "application/json" } });
    if (!respuesta.ok) throw new Error("API no disponible");
    const json = await respuesta.json();
    if (!Array.isArray(json)) throw new Error("Respuesta no válida");
    datos = json;
    pintarTabla(datos);
  }

  function cargarRespaldoLocal() {
    if (typeof productos === "undefined") return;
    datos = productos;
    pintarTabla(datos);
  }

  tbody.addEventListener("click", (e) => {
    const boton = e.target.closest("button[data-accion]");
    if (!boton) return;
    const accion = boton.dataset.accion;
    const id = Number(boton.dataset.id);
    if (accion === "editar") abrirFormulario(id);
    if (accion === "eliminar") eliminarProducto(id);
  });

  function filtrarLocal(termino) {
    return datos.filter(
      (p) => p.nombre.toLowerCase().includes(termino) || p.categoria.toLowerCase().includes(termino)
    );
  }

  let esperaBusqueda = null;
  buscador.addEventListener("input", (e) => {
    const termino = e.target.value.trim().toLowerCase();

    if (sirviendoDelServidor) {
      buscarEnServidor(e.target.value.trim());
      return;
    }
    pintarTabla(filtrarLocal(termino));
  });

  async function buscarEnServidor(texto) {
    clearTimeout(esperaBusqueda);
    esperaBusqueda = setTimeout(async () => {
      const respuesta = await fetch(
        `app/rutas/buscar-productos.php?q=${encodeURIComponent(texto)}`,
        { headers: { Accept: "application/json" } }
      );
      if (!respuesta.ok) return;
      const json = await respuesta.json();
      if (Array.isArray(json)) pintarTabla(json);
    }, 250);
  }

  const reglas = {
    nombre: (valor) => {
      if (!valor.trim()) return "El nombre es obligatorio.";
      if (valor.trim().length < 3) return "El nombre debe tener al menos 3 caracteres.";
      return "";
    },
    categoria: (valor) => (valor ? "" : "Selecciona una categoría."),
    precio: (valor) => {
      if (!valor) return "El precio es obligatorio.";
      if (!(Number(valor) > 0)) return "El precio debe ser mayor que cero.";
      return "";
    },
    stock: (valor) => {
      if (!valor) return "El stock es obligatorio.";
      if (!Number.isInteger(Number(valor))) return "El stock debe ser un número entero.";
      if (Number(valor) < 0) return "El stock no puede ser negativo.";
      return "";
    }
  };

  function validarCampo(campo) {
    const regla = reglas[campo.name];
    const texto = regla ? regla(campo.value) : "";
    campo.setCustomValidity(texto);
    const aviso = document.getElementById(campo.getAttribute("aria-describedby"));
    if (aviso) aviso.textContent = texto;
    campo.classList.toggle("invalid", Boolean(texto));
    return texto;
  }

  function limpiarValidacion() {
    Array.from(formProducto.elements).forEach((campo) => {
      if (reglas[campo.name]) validarCampo(campo);
    });
  }

  function eliminarProducto(id) {
    if (sirviendoDelServidor) {
      eliminarEnServidor(id);
      return;
    }
    const indice = datos.findIndex((p) => p.id === id);
    if (indice === -1) return;
    datos.splice(indice, 1);
    pintarTabla(datos);
  }

  function abrirFormulario(id) {
    const producto = datos.find((p) => p.id === id);
    if (!producto) return;
    editandoId = id;
    formProducto.elements.nombre.value = producto.nombre;
    formProducto.elements.categoria.value = producto.categoria;
    formProducto.elements.precio.value = producto.precio;
    formProducto.elements.stock.value = producto.stock;
    formProducto.querySelector(".acciones-formulario label");
    const botonGuardar = formProducto.querySelector("button[type='submit']");
    if (botonGuardar) botonGuardar.textContent = "Guardar cambios";
    formProducto.elements.nombre.focus();
    formProducto.scrollIntoView({ behavior: "smooth", block: "center" });
  }

  function leerFormulario() {
    return {
      id: editandoId,
      nombre: formProducto.elements.nombre.value.trim(),
      categoria: formProducto.elements.categoria.value,
      precio: Number(formProducto.elements.precio.value),
      stock: Number(formProducto.elements.stock.value)
    };
  }

  function objetoLocal(item) {
    return {
      id: item.id,
      nombre: item.nombre,
      categoria: item.categoria,
      precio: Number(item.precio),
      stock: Number(item.stock)
    };
  }

  formProducto.addEventListener("submit", (e) => {
    e.preventDefault();
    let valido = true;
    Array.from(formProducto.elements).forEach((campo) => {
      if (campo.name && reglas[campo.name] && validarCampo(campo) !== "") valido = false;
    });
    if (!valido) {
      const primero = formProducto.querySelector(".invalid");
      if (primero) primero.focus();
      return;
    }

    if (sirviendoDelServidor) {
      if (editandoId !== null) {
        actualizarEnServidor(leerFormulario());
      } else {
        crearEnServidor(leerFormulario());
      }
      return;
    }

    const datosForm = leerFormulario();
    if (datosForm.id !== null) {
      const indice = datos.findIndex((p) => p.id === datosForm.id);
      if (indice !== -1) datos[indice] = objetoLocal(datosForm);
    } else {
      datos.push({
        id: datos.length ? Math.max(...datos.map((p) => p.id)) + 1 : 1,
        nombre: datosForm.nombre,
        categoria: datosForm.categoria,
        precio: datosForm.precio,
        stock: datosForm.stock
      });
    }
    editandoId = null;
    pintarTabla(datos);
    formProducto.reset();
    const botonGuardar = formProducto.querySelector("button[type='submit']");
    if (botonGuardar) botonGuardar.textContent = "Guardar producto";
    limpiarValidacion();
  });

  async function crearEnServidor(item) {
    const respuesta = await fetch(API, {
      method: "POST",
      headers: { "Content-Type": "application/json", Accept: "application/json" },
      body: JSON.stringify({ nombre: item.nombre, categoria: item.categoria, precio: item.precio, stock: item.stock })
    });
    if (respuesta.ok) {
      editandoId = null;
      formProducto.reset();
      limpiarValidacion();
      await cargarDesdeServidor();
    }
  }

  async function actualizarEnServidor(item) {
    const respuesta = await fetch(`${API}?id=${item.id}`, {
      method: "PUT",
      headers: { "Content-Type": "application/json", Accept: "application/json" },
      body: JSON.stringify({ nombre: item.nombre, categoria: item.categoria, precio: item.precio, stock: item.stock })
    });
    if (respuesta.ok) {
      editandoId = null;
      formProducto.reset();
      limpiarValidacion();
      await cargarDesdeServidor();
    }
  }

  async function eliminarEnServidor(id) {
    const respuesta = await fetch(`${API}?id=${id}`, { method: "DELETE" });
    if (respuesta.ok) {
      await cargarDesdeServidor();
    }
  }

  formProducto.addEventListener("input", (e) => {
    if (e.target.name && reglas[e.target.name]) validarCampo(e.target);
  });

  if (sirviendoDelServidor) {
    cargarDesdeServidor().catch(cargarRespaldoLocal);
  } else {
    cargarRespaldoLocal();
  }
}