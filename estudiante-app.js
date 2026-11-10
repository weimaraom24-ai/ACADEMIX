// Interacciones compartidas: menú lateral, desplazamiento, animaciones y teclado.
(function(){

  // Función para seleccionar un solo elemento del documento
  function qs(sel, ctx) { 
    return (ctx||document).querySelector(sel); 
  }

  // Función para seleccionar varios elementos del documento
  function qsa(sel, ctx) { 
    return Array.from((ctx||document).querySelectorAll(sel)); 
  }

  // Ejecutar el código cuando el documento HTML haya terminado de cargar
  document.addEventListener('DOMContentLoaded', () => {

    // Obtener los elementos principales del menú y encabezado
    const btn = qs('#btn-menu');
    const nav = qs('.main-nav');
    const header = qs('#site-header');
    const links = qsa('.main-nav a');
    const leftBtn = qs('#left-hamburger');
    const side = qs('#side-panel');
    const sideClose = qs('#side-close');
    const sideLinks = qsa('#side-panel a');
    const overlay = qs('#side-overlay');

    // Alterna la visibilidad del menú principal en pantallas móviles.
    if (btn && nav) {
      btn.addEventListener('click', () => {

        // Agregar o quitar la clase "open" del menú
        nav.classList.toggle('open');

        // Comprobar si el menú quedó abierto
        const open = nav.classList.contains('open');

        // Actualizar el atributo aria-expanded para accesibilidad
        btn.setAttribute('aria-expanded', open);
      });
    }

    // Controla la apertura del panel lateral.
    function openSide() {

      // Si el panel no existe, no realizar ninguna acción
      if (!side) return;

      // Agregar la clase que muestra el panel
      side.classList.add('open');

      // Indicar que el panel está visible
      side.setAttribute('aria-hidden','false');

      // Mostrar la capa de fondo detrás del panel
      if (overlay) { 
        overlay.hidden = false; 
        overlay.classList.add('show'); 
      }
    }

    // Función para cerrar el panel lateral
    function closeSide() {

      // Si el panel no existe, no realizar ninguna acción
      if (!side) return;

      // Quitar la clase que mantiene abierto el panel
      side.classList.remove('open');

      // Indicar que el panel está oculto
      side.setAttribute('aria-hidden','true');

      // Ocultar la capa de fondo después de la animación
      if (overlay) { 
        overlay.classList.remove('show'); 
        setTimeout(()=> overlay.hidden = true, 320); 
      }
    }

    // Abrir el panel lateral al presionar el botón izquierdo
    if (leftBtn) leftBtn.addEventListener('click', openSide);

    // Cerrar el panel al presionar el botón de cerrar
    if (sideClose) sideClose.addEventListener('click', closeSide);

    // Cerrar el panel cuando se selecciona uno de sus enlaces
    sideLinks.forEach(a => a.addEventListener('click', closeSide));

    // Cerrar el panel al hacer clic en la capa de fondo
    if (overlay) overlay.addEventListener('click', closeSide);

    // Permitir cerrar el panel utilizando la tecla Escape
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && side && side.classList.contains('open')) closeSide();
    });

    // Desplaza suavemente a secciones internas del documento.
    links.forEach(a => {
      a.addEventListener('click', (e) => {

        // Obtener la dirección del enlace
        const href = a.getAttribute('href');

        // Comprobar si el enlace apunta a una sección mediante #
        if (href && href.startsWith('#')) {

          // Evitar el comportamiento normal del enlace
          e.preventDefault();

          // Buscar la sección correspondiente
          const target = document.querySelector(href);

          // Desplazarse suavemente hasta la sección encontrada
          if (target) target.scrollIntoView({behavior:'smooth',block:'start'});

          // Cerrar el menú móvil después de seleccionar una sección
          if (nav.classList.contains('open')) nav.classList.remove('open');
        }
      });
    });

    // Actualiza el enlace activo según la sección que queda visible.
    // Obtener las secciones del contenido principal que tienen un identificador
    const sections = qsa('main section[id]');

    // Crear un observador para detectar qué sección está visible
    const obs = new IntersectionObserver(entries => {
      entries.forEach(entry => {

        // Comprobar si la sección está visible en la pantalla
        if (entry.isIntersecting) {

          // Obtener el identificador de la sección visible
          const id = entry.target.id;

          // Revisar todos los enlaces del menú
          links.forEach(a => {

            // Obtener el destino del enlace
            const href = a.getAttribute('href')||'';

            // Activar el enlace que corresponde a la sección visible
            if (href === '#'+id) a.classList.add('active'); 
            else a.classList.remove('active');
          });
        }
      });
    },{threshold:0.45});

    // Observar cada sección para detectar cuándo aparece en pantalla
    sections.forEach(s => obs.observe(s));

    // Anima los elementos marcados con data-anim cuando entran en pantalla.
    const animEls = qsa('[data-anim]');

    // Crear un observador para mostrar los elementos al aparecer en pantalla
    const revealIO = new IntersectionObserver((entries, o) => {
      entries.forEach(en => {

        // Comprobar si el elemento está visible
        if (en.isIntersecting) {

          // Agregar la clase que activa la animación de aparición
          en.target.classList.add('reveal');

          // Dejar de observar el elemento después de mostrarlo
          o.unobserve(en.target);
        }
      });
    }, {threshold:0.12});

    // Observar todos los elementos que tengan data-anim
    animEls.forEach(el => revealIO.observe(el));

    // keyboard navigation: left/right jump on header links
    // Permitir navegar entre los enlaces del menú usando las flechas del teclado
    links.forEach((a,i) => {
      a.addEventListener('keydown', (ev) => {

        // Comprobar si se presionó la flecha derecha o izquierda
        if (ev.key === 'ArrowRight' || ev.key === 'ArrowLeft') {

          // Evitar el comportamiento predeterminado del teclado
          ev.preventDefault();

          // Calcular cuál será el siguiente enlace
          const next = ev.key === 'ArrowRight' 
            ? (i+1)%links.length 
            : (i-1+links.length)%links.length;

          // Mover el foco al enlace correspondiente
          links[next].focus();
        }
      });
    });

    // small enhancement: smooth scroll on page load if hash present
    // Comprobar si la URL contiene un identificador de sección
    if (location.hash) {

      // Buscar la sección indicada en la URL
      const t = document.querySelector(location.hash);

      // Si existe, desplazarse suavemente hasta ella después de un pequeño retraso
      if (t) setTimeout(()=> t.scrollIntoView({behavior:'smooth',block:'start'}),200);
    }
  });
})();