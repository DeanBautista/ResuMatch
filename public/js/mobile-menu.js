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

// Desktop profile dropdown (Sign in / Account menu)
(function () {
  const wrap = document.getElementById('profileMenuWrap');
  const toggle = document.getElementById('profileMenuToggle');
  const menu = document.getElementById('profileMenu');
  if (!wrap || !toggle || !menu) return;

  function closeMenu() {
    menu.classList.add('hidden');
    toggle.setAttribute('aria-expanded', 'false');
  }

  function openMenu() {
    menu.classList.remove('hidden');
    toggle.setAttribute('aria-expanded', 'true');
  }

  toggle.addEventListener('click', function (e) {
    e.stopPropagation();
    const isOpen = toggle.getAttribute('aria-expanded') === 'true';
    isOpen ? closeMenu() : openMenu();
  });

  // Click outside closes it
  document.addEventListener('click', function (e) {
    if (!wrap.contains(e.target)) closeMenu();
  });

  // Escape closes it
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeMenu();
  });
})();