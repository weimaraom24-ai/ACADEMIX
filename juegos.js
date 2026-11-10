// Banco local de preguntas; cada índice `correcta` coincide con una opción de `opciones`.
const preguntasPorMateria = {
  "Lectura Crítica": [
    { pregunta: "¿Qué idea expresa mejor la tesis de un texto argumentativo?", opciones: ["El dato menos importante", "La postura central que defiende el autor", "La lista de personajes", "El significado de una palabra"], correcta: 1 },
    { pregunta: "Si un narrador cuenta hechos en los que participa, ¿qué tipo de narrador es?", opciones: ["Protagonista", "Observador externo", "Omnisciente", "Cronista histórico"], correcta: 0 },
    { pregunta: "¿Para qué sirve principalmente un conector como «sin embargo»?", opciones: ["Añadir un ejemplo", "Indicar una consecuencia", "Contrastar dos ideas", "Presentar una definición"], correcta: 2 },
    { pregunta: "Una conclusión válida debe…", opciones: ["Introducir un tema sin relación", "Repetir cada frase literalmente", "Contradecir las evidencias", "Cerrar la idea a partir de lo expuesto"], correcta: 3 },
    { pregunta: "¿Qué permite inferir un lector?", opciones: ["Una idea sugerida por pistas del texto", "Un dato que el texto niega", "La opinión de cualquier lector", "El título de otro libro"], correcta: 0 }
  ],
  "Matemáticas": [
    { pregunta: "Si 3x + 5 = 20, ¿cuánto vale x?", opciones: ["3", "5", "7", "15"], correcta: 1 },
    { pregunta: "¿Cuál es la pendiente de la recta y = 2x - 4?", opciones: ["-4", "-2", "2", "4"], correcta: 2 },
    { pregunta: "¿Cuánto es el 25 % de 80?", opciones: ["15", "20", "25", "40"], correcta: 1 },
    { pregunta: "¿Cuál es el área de un triángulo de base 10 y altura 6?", opciones: ["16", "30", "60", "120"], correcta: 1 },
    { pregunta: "¿Qué número continúa la secuencia 2, 6, 18, ...?", opciones: ["36", "48", "54", "72"], correcta: 2 }
  ],
  "Ciencias Naturales": [
    { pregunta: "¿Qué estructura celular contiene la mayor parte del ADN?", opciones: ["Núcleo", "Membrana", "Ribosoma", "Citoplasma"], correcta: 0 },
    { pregunta: "En una cadena alimentaria, las plantas suelen ser…", opciones: ["Consumidores secundarios", "Descomponedores", "Productores", "Depredadores"], correcta: 2 },
    { pregunta: "¿Qué gas absorben principalmente las plantas durante la fotosíntesis?", opciones: ["Oxígeno", "Dióxido de carbono", "Nitrógeno", "Hidrógeno"], correcta: 1 },
    { pregunta: "¿Cuál es la unidad de fuerza en el Sistema Internacional?", opciones: ["Julio", "Vatio", "Pascal", "Newton"], correcta: 3 },
    { pregunta: "¿Qué cambio de estado ocurre cuando el agua líquida se vuelve vapor?", opciones: ["Evaporación", "Condensación", "Solidificación", "Fusión"], correcta: 0 }
  ],
  "Competencias Ciudadanas": [
    { pregunta: "¿Cuál es una función principal de la Constitución?", opciones: ["Reemplazar todas las leyes locales", "Organizar el Estado y reconocer derechos", "Elegir cada decisión personal", "Regular solo el comercio"], correcta: 1 },
    { pregunta: "¿Qué mecanismo permite a la ciudadanía elegir representantes?", opciones: ["El voto", "El plebiscito escolar", "La tutela", "La conciliación"], correcta: 0 },
    { pregunta: "Ante un conflicto, una salida democrática busca…", opciones: ["Imponer la opinión más fuerte", "Evitar escuchar a las partes", "Dialogar y construir acuerdos", "Castigar sin investigar"], correcta: 2 },
    { pregunta: "¿Qué protege el derecho a la igualdad?", opciones: ["Que todos tengan las mismas ideas", "Que nadie pueda opinar", "Que existan privilegios permanentes", "La misma dignidad y protección ante la ley"], correcta: 3 },
    { pregunta: "¿Cuál es un ejemplo de participación ciudadana?", opciones: ["Informarse y participar en una consulta local", "Ignorar las decisiones públicas", "Compartir rumores como hechos", "Impedir que otros expresen su opinión"], correcta: 0 }
  ],
  "Inglés": [
    { pregunta: "Choose the correct option: She ___ to school every day.", opciones: ["go", "goes", "going", "gone"], correcta: 1 },
    { pregunta: "What is the opposite of «early»?", opciones: ["Fast", "Soon", "Late", "First"], correcta: 2 },
    { pregunta: "Complete: They have lived here ___ 2020.", opciones: ["for", "during", "from", "since"], correcta: 3 },
    { pregunta: "What does «borrow» mean?", opciones: ["Tomar prestado", "Comprar", "Devolver", "Romper"], correcta: 0 },
    { pregunta: "Choose the correct question: ___ you like music?", opciones: ["Are", "Do", "Does", "Is"], correcta: 1 }
  ]
};

// Referencias a los controles y pantallas que se actualizan durante el quiz.
const selectorMateria = document.getElementById("materia-juego");
const botonIniciar = document.getElementById("iniciar-juego");
const pantallaInicio = document.getElementById("inicio-juego");
const pantallaPartida = document.getElementById("partida");
const pantallaResultado = document.getElementById("resultado-juego");
const contenedorRespuestas = document.getElementById("respuestas");
const barraTiempo = document.getElementById("barra-tiempo");
// Duración y estado mutable de la partida actual.
const DURACION_PREGUNTA = 15;
let materiaActual = "";
let preguntasActuales = [];
let indicePregunta = 0;
let puntaje = 0;
let aciertos = 0;
let segundosRestantes = DURACION_PREGUNTA;
let temporizador = null;
let respuestaBloqueada = false;

// Genera una clave distinta para guardar el récord de cada materia en este navegador.
function claveRecord(materia) {
  return `academix-mejor-puntaje-${materia}`;
}

// Muestra el mejor puntaje local cuando el estudiante elige una materia.
function actualizarRecordVisible() {
  const materia = selectorMateria.value;
  const record = materia ? Number(localStorage.getItem(claveRecord(materia)) || 0) : 0;
  document.getElementById("record-previo").textContent = materia
    ? `Tu mejor puntaje en ${materia}: ${record.toLocaleString("es-CO")} puntos`
    : "Tu mejor puntaje aparecerá aquí.";
}

// Devuelve una copia de las preguntas en orden aleatorio, sin modificar el banco original.
function mezclarPreguntas(preguntas) {
  return [...preguntas].sort(() => Math.random() - 0.5);
}

// Reinicia los contadores, cambia a la pantalla de juego y carga la primera pregunta.
function iniciarJuego() {
  materiaActual = selectorMateria.value;
  if (!preguntasPorMateria[materiaActual]) return;

  clearInterval(temporizador);
  preguntasActuales = mezclarPreguntas(preguntasPorMateria[materiaActual]);
  indicePregunta = 0;
  puntaje = 0;
  aciertos = 0;
  pantallaInicio.hidden = true;
  pantallaResultado.hidden = true;
  pantallaPartida.hidden = false;
  document.getElementById("materia-actual").textContent = materiaActual;
  document.getElementById("puntaje-actual").textContent = "0";
  mostrarPregunta();
}

// Dibuja la pregunta y sus botones, y vuelve a iniciar el temporizador.
function mostrarPregunta() {
  respuestaBloqueada = false;
  const pregunta = preguntasActuales[indicePregunta];
  document.getElementById("contador-preguntas").textContent = `${indicePregunta + 1} / ${preguntasActuales.length}`;
  document.getElementById("texto-pregunta").textContent = pregunta.pregunta;
  document.getElementById("feedback").textContent = "";
  document.getElementById("feedback").className = "";
  document.getElementById("siguiente-pregunta").hidden = true;
  contenedorRespuestas.replaceChildren();

  pregunta.opciones.forEach((opcion, indice) => {
    const boton = document.createElement("button");
    boton.type = "button";
    boton.className = "respuesta";
    boton.textContent = `${indice + 1}.  ${opcion}`;
    boton.addEventListener("click", () => responder(indice));
    contenedorRespuestas.appendChild(boton);
  });

  iniciarTemporizador();
}

// Cuenta hacia atrás y registra una respuesta vacía cuando se acaba el tiempo.
function iniciarTemporizador() {
  clearInterval(temporizador);
  segundosRestantes = DURACION_PREGUNTA;
  actualizarTemporizador();
  temporizador = setInterval(() => {
    segundosRestantes -= 1;
    actualizarTemporizador();
    if (segundosRestantes <= 0) responder(null);
  }, 1000);
}

// Sincroniza el texto y el ancho/color de la barra con el tiempo restante.
function actualizarTemporizador() {
  document.getElementById("tiempo-texto").textContent = `${segundosRestantes} s`;
  barraTiempo.style.width = `${(segundosRestantes / DURACION_PREGUNTA) * 100}%`;
  barraTiempo.style.background = segundosRestantes <= 5 ? "#d94e46" : "#087f78";
}

// Bloquea las opciones, compara la respuesta, calcula puntos y habilita continuar.
function responder(indiceElegido) {
  if (respuestaBloqueada) return;
  respuestaBloqueada = true;
  clearInterval(temporizador);

  const pregunta = preguntasActuales[indicePregunta];
  const botones = [...contenedorRespuestas.querySelectorAll("button")];
  botones.forEach((boton, indice) => {
    boton.disabled = true;
    if (indice === pregunta.correcta) boton.classList.add("es-correcta");
    if (indice === indiceElegido && indice !== pregunta.correcta) boton.classList.add("es-incorrecta");
  });

  const feedback = document.getElementById("feedback");
  if (indiceElegido === pregunta.correcta) {
    aciertos += 1;
    puntaje += 100 + segundosRestantes * 20;
    document.getElementById("puntaje-actual").textContent = puntaje.toLocaleString("es-CO");
    feedback.textContent = `¡Correcto! +${100 + segundosRestantes * 20} puntos`;
    feedback.className = "feedback-correcto";
  } else if (indiceElegido === null) {
    feedback.textContent = `Se acabó el tiempo. Respuesta: ${pregunta.opciones[pregunta.correcta]}`;
    feedback.className = "feedback-error";
  } else {
    feedback.textContent = `No era esa. Respuesta: ${pregunta.opciones[pregunta.correcta]}`;
    feedback.className = "feedback-error";
  }

  const botonSiguiente = document.getElementById("siguiente-pregunta");
  botonSiguiente.textContent = indicePregunta === preguntasActuales.length - 1 ? "Ver resultado" : "Siguiente →";
  botonSiguiente.hidden = false;
  botonSiguiente.focus();
}

// Pasa a la siguiente pregunta o muestra el resumen al terminar el cuestionario.
function avanzarPregunta() {
  indicePregunta += 1;
  if (indicePregunta >= preguntasActuales.length) {
    terminarJuego();
    return;
  }
  mostrarPregunta();
}

// Guarda el mejor puntaje por materia y completa las estadísticas finales.
function terminarJuego() {
  clearInterval(temporizador);
  pantallaPartida.hidden = true;
  pantallaResultado.hidden = false;

  const recordAnterior = Number(localStorage.getItem(claveRecord(materiaActual)) || 0);
  const recordNuevo = Math.max(recordAnterior, puntaje);
  localStorage.setItem(claveRecord(materiaActual), String(recordNuevo));
  document.getElementById("titulo-resultado").textContent = aciertos === preguntasActuales.length ? "¡Reto perfecto!" : aciertos >= 3 ? "¡Buen trabajo!" : "¡Sigue practicando!";
  document.getElementById("resumen-resultado").textContent = materiaActual;
  document.getElementById("aciertos-resultado").textContent = `${aciertos}/${preguntasActuales.length}`;
  document.getElementById("puntaje-resultado").textContent = puntaje.toLocaleString("es-CO");
  document.getElementById("record-resultado").textContent = recordNuevo.toLocaleString("es-CO");
}

// Habilita el inicio al seleccionar materia y conecta las acciones de cada botón.
selectorMateria.addEventListener("change", () => {
  botonIniciar.disabled = !selectorMateria.value;
  actualizarRecordVisible();
});
botonIniciar.addEventListener("click", iniciarJuego);
document.getElementById("siguiente-pregunta").addEventListener("click", avanzarPregunta);
document.getElementById("repetir-juego").addEventListener("click", iniciarJuego);
document.getElementById("nuevo-juego").addEventListener("click", () => {
  pantallaResultado.hidden = true;
  pantallaInicio.hidden = false;
  actualizarRecordVisible();
  selectorMateria.focus();
});

actualizarRecordVisible();