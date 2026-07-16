(function () {
  const toggle = document.getElementById('menuToggle');
  const menu = document.getElementById('mobileMenu');
  if (!toggle || !menu) return;

  toggle.addEventListener('click', function () {
    const isOpen = menu.classList.contains('open');

    if (isOpen) {
      menu.classList.remove('open');
      toggle.classList.remove('is-open');
      toggle.setAttribute('aria-expanded', 'false');
      setTimeout(function () {
        if (!menu.classList.contains('open')) menu.classList.add('hidden');
      }, 220);
    } else {
      menu.classList.remove('hidden');
      requestAnimationFrame(function () {
        menu.classList.add('open');
      });
      toggle.classList.add('is-open');
      toggle.setAttribute('aria-expanded', 'true');
    }
  });
})();