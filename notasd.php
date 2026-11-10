<!-- Vista docente activa: filtra entregas por grado, materia y guía para calificarlas. -->
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>ACADEMIX — Registro de Notas (Docentes)</title>
  <link rel="stylesheet" href="estudiante.css">
  <link rel="stylesheet" href="notas-docente.css">
  <meta name="description" content="Registro de notas para docentes">
</head>

<body>

<header class="site-header" id="site-header">
  <div class="container header-inner">

    <div class="header-start">
      <button id="left-hamburger" class="left-hamburger" aria-label="Abrir menú">☰</button>

      <div class="brand-container">
        <a class="brand" href="docente.html" aria-label="Inicio ACADEMIX">
          <img src="logo.jpg" alt="logo Academix">
          <div class="brand-meta">
            <span class="brand-name">ACADEMIX</span>
            <small> Docente</small>
          </div>
        </a>
      </div>
    </div>

    <div class="header-phrase">tu cerebro en su prime</div>

    <nav class="main-nav" id="main-nav" aria-label="Navegación principal">
      <ul>
        <li><a href="docente.html">Inicio</a></li>
        <li><a href="guiasD.html">Guías</a></li>
        <li><a class="active" href="notasd.php">Notas</a></li>
        <li><a href="ayudaD.html">Soporte</a></li>
      </ul>
    </nav>

    <div class="header-actions">
      <button class="btn-logout" type="button" onclick="window.location.href='logout.php'">Salir</button>
      <button id="btn-menu" class="btn-menu" aria-label="Abrir menú" aria-expanded="false">☰</button>
    </div>

  </div>
</header>

<nav id="side-panel" class="side-panel" aria-hidden="true">
  <button id="side-close" class="side-close" aria-label="Cerrar menú">×</button>

  <ul>
    <li><a href="docente.html">Inicio</a></li>
    <li><a href="guiasD.html">Guías</a></li>
    <li><a href="notasd.php">Notas</a></li>
    <li><a href="ayudaD.html">Soporte</a></li>
  </ul>
</nav>

<div id="side-overlay" class="side-overlay" hidden></div>

<main class="notas-docente container">

  <section class="notas-section">

    <div class="notas-heading">
      <p class="notas-eyebrow">ACADEMIX / DOCENCIA</p>
      <h1>Notas por guía</h1>
    </div>

    <div class="notas-filtros">

      <div class="filtro-campo">
        <label for="filtro-grupo">Grado</label>

        <select id="filtro-grupo">
          <option value="">Seleccionar grado</option>
          <option value="10-1">10-1</option>
          <option value="10-2">10-2</option>
          <option value="10-3">10-3</option>
          <option value="10-4">10-4</option>
          <option value="10-5">10-5</option>
          <option value="10-6">10-6</option>
          <option value="10-7">10-7</option>
          <option value="11-1">11-1</option>
          <option value="11-2">11-2</option>
          <option value="11-3">11-3</option>
          <option value="11-4">11-4</option>
          <option value="11-5">11-5</option>
          <option value="11-6">11-6</option>
          <option value="11-7">11-7</option>
        </select>

      </div>

      <div class="filtro-campo">

        <label for="filtro-materia">Materia</label>

        <select id="filtro-materia">
          <option value="">Seleccionar materia</option>
          <option value="Lectura Critica">Lectura Critica</option>
          <option value="Matematicas">Matematicas</option>
          <option value="Ciencias Naturales">Ciencias Naturales</option>
          <option value="Competencias Ciudadanas">Competencias Ciudadanas</option>
          <option value="Ingles">Ingles</option>
        </select>

      </div>

      <div class="filtro-campo">

        <label for="filtro-guia">Guía / Actividad</label>

        <select id="filtro-guia">
          <option value="">Seleccionar guía</option>
        </select>

      </div>

    </div>

    <button id="buscar-notas" class="btn-buscar-notas" type="button">
      Buscar
    </button>

    <p id="estado-notas" class="estado-notas">
      Selecciona un grado, una materia y una guía.
    </p>

    <div id="contenedor-tabla-notas" class="tabla-notas-wrap" hidden>

      <table class="tabla-notas">

        <thead>
          <tr>
            <th>Estudiante</th>
            <th>Entrega</th>
            <th>Nota / 5.0</th>
            <th>Promedio materia</th>
            <th>Acción</th>
          </tr>
        </thead>

        <tbody id="tabla-estudiantes-notas"></tbody>

      </table>

    </div>

  </section>

</main>

<footer class="site-footer">
  <div class="container">
    <small>ACADEMIX — Plataforma Educativa</small>
  </div>
</footer>

<script src="estudiante-app.js"></script>

<script>

// Referencias a filtros, mensajes de estado y tabla de estudiantes.
const grupoSelect = document.getElementById("filtro-grupo");
const materiaSelect = document.getElementById("filtro-materia");
const guiaSelect = document.getElementById("filtro-guia");

const estadoNotas = document.getElementById("estado-notas");
const contenedorTabla = document.getElementById("contenedor-tabla-notas");
const tablaEstudiantes = document.getElementById("tabla-estudiantes-notas");
const botonBuscar = document.getElementById("buscar-notas");


// Carga las guías disponibles para la combinación actual de grado y materia.
async function cargarGuias() {

  const grupo = grupoSelect.value;
  const materia = materiaSelect.value;

  guiaSelect.innerHTML = '<option value="">Seleccionar guía</option>';

  if (!grupo || !materia) {
    return;
  }

  try {

    const parametros = new URLSearchParams({
      grupo: grupo,
      materia: materia
    });

    const respuesta = await fetch(`listar_notas_docente.php?${parametros}`);

    const datos = await respuesta.json();

    if (!respuesta.ok) {
      throw new Error(datos.error || "No se pudieron cargar las guías.");
    }

    datos.guias.forEach(guia => {

      const opcion = document.createElement("option");

      opcion.value = guia.id;
      opcion.textContent = guia.titulo;

      guiaSelect.appendChild(opcion);

    });

    if (datos.guias.length === 0) {

      estadoNotas.textContent =
        "No hay guías montadas para esta materia.";

    } else {

      estadoNotas.textContent =
        "Selecciona la guía que quieres calificar.";

    }

  } catch (error) {

    estadoNotas.textContent =
      error.message || "No se pudieron cargar las guías.";

  }

}


// Pide los estudiantes y entregas de la guía y crea las filas editables.
async function cargarEstudiantes() {

  const grupo = grupoSelect.value;
  const materia = materiaSelect.value;
  const guiaId = guiaSelect.value;

  tablaEstudiantes.replaceChildren();
  contenedorTabla.hidden = true;

  if (!grupo || !materia || !guiaId) {

    estadoNotas.textContent =
      "Selecciona un grado, una materia y una guía.";

    return;

  }

  estadoNotas.textContent = "Cargando estudiantes...";

  try {

    const parametros = new URLSearchParams({
      grupo: grupo,
      materia: materia,
      guia_id: guiaId
    });

    const respuesta =
      await fetch(`listar_notas_docente.php?${parametros}`);

    const datos = await respuesta.json();

    if (!respuesta.ok) {
      throw new Error(
        datos.error || "No se pudieron cargar los estudiantes."
      );
    }

    if (datos.estudiantes.length === 0) {

      estadoNotas.textContent =
        `No hay estudiantes registrados en el grado ${grupo}.`;

      return;

    }

    datos.estudiantes.forEach(estudiante => {

      const fila = document.createElement("tr");

      const nombre = document.createElement("td");
      const entrega = document.createElement("td");
      const celdaNota = document.createElement("td");
      const celdaPromedio = document.createElement("td");
      const celdaAccion = document.createElement("td");

      nombre.textContent =
        `${estudiante.apellido}, ${estudiante.nombre}`;

      celdaPromedio.textContent = estudiante.promedio_materia === null
        ? "Sin notas"
        : `${Number(estudiante.promedio_materia).toFixed(1)} / 5.0`;


      if (estudiante.entrega_id) {

        const enlace = document.createElement("a");

        enlace.href = estudiante.archivo;
        enlace.target = "_blank";
        enlace.textContent = "Ver trabajo";

        entrega.appendChild(enlace);

      } else {

        entrega.textContent = "Sin entrega";

      }


      const entradaNota = document.createElement("input");

      entradaNota.type = "number";
      entradaNota.min = "0";
      entradaNota.max = "5";
      entradaNota.step = "0.1";

      entradaNota.value =
        estudiante.nota ?? "";

      entradaNota.placeholder =
        "0.0 - 5.0";


      const botonGuardar =
        document.createElement("button");

      botonGuardar.type = "button";
      botonGuardar.className =
        "btn-guardar-nota";

      botonGuardar.textContent =
        "Guardar";


      if (!estudiante.entrega_id) {

        entradaNota.disabled = true;
        botonGuardar.disabled = true;

      } else {

        botonGuardar.addEventListener(
          "click",
          () => guardarNota(
            estudiante.entrega_id,
            entradaNota,
            botonGuardar
          )
        );

      }


      celdaNota.appendChild(entradaNota);
      celdaAccion.appendChild(botonGuardar);

      fila.append(
        nombre,
        entrega,
        celdaNota,
        celdaPromedio,
        celdaAccion
      );

      tablaEstudiantes.appendChild(fila);

    });


    estadoNotas.textContent =
      `${datos.estudiantes.length} estudiantes · ${materia} · ${datos.guia.titulo}`;

    contenedorTabla.hidden = false;

  } catch (error) {

    estadoNotas.textContent =
      error.message ||
      "No se pudieron cargar los estudiantes.";

  }

}


// Valida el rango permitido y guarda la nota asociada a la entrega.
async function guardarNota(entregaId, entrada, boton) {

  if (
    entrada.value === "" ||
    Number(entrada.value) < 0 ||
    Number(entrada.value) > 5
  ) {

    entrada.focus();

    estadoNotas.textContent =
      "Ingresa una nota entre 0.0 y 5.0.";

    return;

  }


  boton.disabled = true;
  boton.textContent = "Guardando...";


  const datos = new URLSearchParams({

    entrega_id: entregaId,

    nota: entrada.value

  });


  try {

    const respuesta =
      await fetch(
        "guardar_nota_estudiante.php",
        {
          method: "POST",
          body: datos
        }
      );


    const resultado =
      await respuesta.json();


    if (!respuesta.ok) {

      throw new Error(
        resultado.error ||
        "No se pudo guardar la nota."
      );

    }


    entrada.value =
      resultado.nota;

    estadoNotas.textContent =
      "Nota guardada correctamente.";

    boton.textContent =
      "Guardado";


  } catch (error) {

    estadoNotas.textContent =
      error.message ||
      "No se pudo guardar la nota.";

    boton.textContent =
      "Guardar";

  } finally {

    boton.disabled = false;

  }

}


grupoSelect.addEventListener(
  "change",
  cargarGuias
);

materiaSelect.addEventListener(
  "change",
  cargarGuias
);

botonBuscar.addEventListener(
  "click",
  cargarEstudiantes
);

</script>

</body>
</html>