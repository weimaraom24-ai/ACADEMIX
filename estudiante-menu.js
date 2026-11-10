// Sincroniza el elemento activo y los controles de teclado del menú de navegación.
document.addEventListener('DOMContentLoaded', () => {
  // Admite tanto el selector antiguo `.menu` como el menú actual `.main-nav`.
  const menu = document.querySelector('.menu') || document.querySelector('.main-nav');
  if (!menu) return;

  const links = Array.from(menu.querySelectorAll('a'));
  if (!links.length) return;

  // Mantiene una sola opción marcada como activa.
  function setActive(el) {
    links.forEach(l => l.classList.remove('active'));
    if (el) el.classList.add('active');
  }

  // Determina la página actual a partir del nombre del archivo.
  const path = location.pathname.split('/').pop().toLowerCase();
  let matched = links.find(a => {
    const href = a.getAttribute('href') || '';
    return href.split('/').pop().toLowerCase() === path;
  });
  if (!matched) matched = links[0];
  setActive(matched);

  // Actualiza el estado visual y evita recargar la página actual.
  links.forEach(a => {
    a.addEventListener('click', (e) => {
      const href = (a.getAttribute('href') || '').split('/').pop().toLowerCase();
      const current = location.pathname.split('/').pop().toLowerCase();
      setActive(a);
      // Da una respuesta visual breve al clic o toque.
      a.classList.add('pressed');
      setTimeout(()=> a.classList.remove('pressed'), 240);

      // Si el enlace apunta a esta página, evita recargar y perder el estado visual.
      if (href === current || href === '') {
        e.preventDefault();
      }
    });

    // Permite activar enlaces con Enter o Espacio y recorrerlos con flechas.
    a.addEventListener('keydown', (ev) => {
      if (ev.key === 'Enter' || ev.key === ' ') {
        ev.preventDefault();
        a.click();
      }
      // Las flechas izquierda y derecha mueven el foco entre enlaces.
      if (ev.key === 'ArrowRight' || ev.key === 'ArrowLeft') {
        ev.preventDefault();
        const idx = links.indexOf(a);
        let next = idx;
        if (ev.key === 'ArrowRight') next = (idx + 1) % links.length;
        else if (ev.key === 'ArrowLeft') next = (idx - 1 + links.length) % links.length;
        links[next].focus();
        // Da una señal visual breve al cambiar de opción con el teclado.
        links[next].classList.add('pressed');
        setTimeout(()=> links[next].classList.remove('pressed'), 180);
      }
    });
  });

  // Usa data-preview como tooltip si el enlace no tiene ya un título.
  links.forEach(a => {
    const preview = a.dataset && a.dataset.preview;
    if (preview && !a.getAttribute('title')) a.setAttribute('title', preview);
  });
});
