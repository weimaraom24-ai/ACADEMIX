<!-- Quiz para estudiantes; juegos.js administra las reglas y el estado de cada partida. -->
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="description" content="Juegos de preguntas para practicar las materias de ACADEMIX.">
  <title>ACADEMIX — Juegos</title>
  <link rel="stylesheet" href="estudiante.css">
  <link rel="stylesheet" href="juegos.css">
</head>
<body>
  <header class="site-header" id="site-header">
    <div class="container header-inner">
      <div class="header-start">
        <button id="left-hamburger" class="left-hamburger" aria-label="Abrir menú">☰</button>
        <div class="brand-container">
          <a class="brand" href="estudiante.html" aria-label="Inicio ACADEMIX">
            <img src="logo.jpg" alt="Logo ACADEMIX">
            <div class="brand-meta"><span class="brand-name">ACADEMIX</span><small>Estudiante</small></div>
          </a>
        </div>
      </div>
      <div class="header-phrase">tu cerebro en su prime</div>
      <nav class="main-nav" id="main-nav" aria-label="Navegación principal">
        <ul>
          <li><a href="estudiante.html">Inicio</a></li>
          <li><a href="guias.html">Guías</a></li>
          <li><a class="active" href="juegos.php">Juegos</a></li>
          <li><a href="notas.html">Notas</a></li>
          <li><a href="ayuda.html">Ayuda</a></li>
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
      <li><a href="estudiante.html">Inicio</a></li>
      <li><a href="guias.html">Guías</a></li>
      <li><a class="active" href="juegos.php">Juegos</a></li>
      <li><a href="notas.html">Notas</a></li>
      <li><a href="ayuda.html">Ayuda</a></li>
    </ul>
  </nav>
  <div id="side-overlay" class="side-overlay" hidden></div>

  <main class="juegos-main">
    <section class="juegos-shell" aria-labelledby="titulo-juegos">
      <header class="juegos-heading">
        <div class="juegos-kicker"><span aria-hidden="true">◆</span> RETO RÁPIDO</div>
        <h1 id="titulo-juegos">Juega para aprender</h1>
        <p>Elige una materia, responde antes de que termine el tiempo y suma puntos.</p>
      </header>

      <!-- Pantalla inicial para seleccionar materia y consultar el récord local. -->
      <section id="inicio-juego" class="juego-setup" aria-label="Elegir juego">
        <div class="setup-topline"><span>ACADEMIX QUIZ</span><span>5 preguntas · 15 segundos cada una</span></div>
        <label for="materia-juego">Selecciona una materia</label>
        <div class="setup-controls">
          <select id="materia-juego">
            <option value="">Elige una materia</option>
            <option value="Lectura Crítica">Lectura Crítica</option>
            <option value="Matemáticas">Matemáticas</option>
            <option value="Ciencias Naturales">Ciencias Naturales</option>
            <option value="Competencias Ciudadanas">Competencias Ciudadanas</option>
            <option value="Inglés">Inglés</option>
          </select>
          <button id="iniciar-juego" class="boton-juego boton-iniciar" type="button" disabled>Empezar <span aria-hidden="true">→</span></button>
        </div>
        <p id="record-previo" class="record-previo" aria-live="polite">Tu mejor puntaje aparecerá aquí.</p>
      </section>

      <!-- Pantalla de preguntas, alternativas, temporizador y puntaje en curso. -->
      <section id="partida" class="partida" aria-label="Pregunta actual" hidden>
        <div class="partida-topline">
          <span id="materia-actual" class="materia-actual"></span>
          <span id="contador-preguntas" class="contador-preguntas"></span>
          <span class="puntaje-label">PUNTOS <strong id="puntaje-actual">0</strong></span>
        </div>
        <div class="temporizador" aria-label="Tiempo restante"><span id="barra-tiempo"></span></div>
        <p id="tiempo-texto" class="tiempo-texto" aria-live="off">15 s</p>
        <h2 id="texto-pregunta" class="texto-pregunta" tabindex="-1"></h2>
        <div id="respuestas" class="respuestas" role="group" aria-label="Opciones de respuesta"></div>
        <div class="feedback-line">
          <p id="feedback" role="status" aria-live="polite"></p>
          <button id="siguiente-pregunta" class="boton-juego boton-siguiente" type="button" hidden>Siguiente <span aria-hidden="true">→</span></button>
        </div>
      </section>

      <!-- Resumen final con acciones para repetir o cambiar de materia. -->
      <section id="resultado-juego" class="resultado-juego" aria-live="polite" hidden>
        <p class="juegos-kicker">RETO COMPLETADO</p>
        <h2 id="titulo-resultado">Buen intento</h2>
        <p id="resumen-resultado" class="resumen-resultado"></p>
        <div class="resultado-stats">
          <div><span>ACIERTOS</span><strong id="aciertos-resultado">0/5</strong></div>
          <div><span>PUNTAJE</span><strong id="puntaje-resultado">0</strong></div>
          <div><span>RÉCORD</span><strong id="record-resultado">0</strong></div>
        </div>
        <div class="resultado-actions">
          <button id="repetir-juego" class="boton-juego boton-iniciar" type="button">Jugar otra vez</button>
          <button id="nuevo-juego" class="boton-juego boton-secundario" type="button">Elegir materia</button>
        </div>
      </section>
    </section>
  </main>

  <footer class="site-footer">
    <div class="container"><small>ACADEMIX — Plataforma Educativa</small></div>
  </footer>

  <script src="estudiante-app.js"></script>
  <script src="estudiante-menu.js"></script>
  <script src="juegos.js"></script>
</body>
</html>