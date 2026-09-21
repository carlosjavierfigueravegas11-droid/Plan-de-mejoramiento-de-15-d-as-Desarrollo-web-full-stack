const moneda = new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 });

const botonMenu = document.querySelector(".boton-menu");
const menuLateral = document.querySelector("#menu-lateral");
if (botonMenu && menuLateral) {
  botonMenu.addEventListener("click", () => menuLateral.classList.toggle("abierto"));
}

const tbody = document.querySelector("#tabla-productos tbody");
const buscador = document.querySelector("#buscador");
const formProducto = document.querySelector("#form-producto");
const hayTabla = Boolean(tbody && buscador && formProducto && typeof productos !== "undefined");

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

  tbody.addEventListener("click", (e) => {
    const boton = e.target.closest("button[data-accion]");
    if (!boton) return;
    const accion = boton.dataset.accion;
    const id = Number(boton.dataset.id);
    if (accion === "editar") abrirFormulario(id);
    if (accion === "eliminar") eliminarProducto(id);
  });

  buscador.addEventListener("input", (e) => {
    const termino = e.target.value.trim().toLowerCase();
    const filtrados = productos.filter(
      (p) => p.nombre.toLowerCase().includes(termino) || p.categoria.toLowerCase().includes(termino)
    );
    pintarTabla(filtrados);
  });

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

  formProducto.addEventListener("submit", (e) => {
    e.preventDefault();
    let valido = true;
    Array.from(formProducto.elements).forEach((campo) => {
      if (campo.name && reglas[campo.name] && validarCampo(campo) !== "") valido = false;
    });
    if (valido) {
      agregarProducto();
    } else {
      const primero = formProducto.querySelector(".invalid");
      if (primero) primero.focus();
    }
  });

  formProducto.addEventListener("input", (e) => {
    if (e.target.name && reglas[e.target.name]) validarCampo(e.target);
  });

  function abrirFormulario(id) {
    const producto = productos.find((p) => p.id === id);
    if (!producto) return;
    formProducto.elements.nombre.value = producto.nombre;
    formProducto.elements.categoria.value = producto.categoria;
    formProducto.elements.precio.value = producto.precio;
    formProducto.elements.stock.value = producto.stock;
    formProducto.elements.nombre.focus();
    formProducto.scrollIntoView({ behavior: "smooth", block: "center" });
  }

  function eliminarProducto(id) {
    const indice = productos.findIndex((p) => p.id === id);
    if (indice === -1) return;
    productos.splice(indice, 1);
    pintarTabla(productos);
  }

  function agregarProducto() {
    const nuevo = {
      id: Math.max(...productos.map((p) => p.id)) + 1,
      nombre: formProducto.elements.nombre.value.trim(),
      categoria: formProducto.elements.categoria.value,
      precio: Number(formProducto.elements.precio.value),
      stock: Number(formProducto.elements.stock.value)
    };
    productos.push(nuevo);
    pintarTabla(productos);
    formProducto.reset();
    Array.from(formProducto.elements).forEach((campo) => {
      if (reglas[campo.name]) validarCampo(campo);
    });
  }

  pintarTabla(productos);
}