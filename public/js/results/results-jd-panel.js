(function () {
    const fab      = document.getElementById('jd-fab');
    const panel    = document.getElementById('jd-panel');
    const backdrop = document.getElementById('jd-backdrop');
    const closeBtn = document.getElementById('jd-panel-close');
    if (!fab || !panel || !backdrop || !closeBtn) return;

    let lastFocused = null;

    function openPanel() {
        lastFocused = document.activeElement;
        panel.classList.remove('translate-x-full');
        backdrop.classList.remove('opacity-0', 'pointer-events-none');
        fab.setAttribute('aria-expanded', 'true');
        document.body.classList.add('overflow-hidden');
        closeBtn.focus();
        document.addEventListener('keydown', onKeydown);
    }

    function closePanel() {
        panel.classList.add('translate-x-full');
        backdrop.classList.add('opacity-0', 'pointer-events-none');
        fab.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('overflow-hidden');
        document.removeEventListener('keydown', onKeydown);
        if (lastFocused) lastFocused.focus();
    }

    function onKeydown(e) {
        if (e.key === 'Escape') closePanel();
    }

    fab.addEventListener('click', openPanel);
    closeBtn.addEventListener('click', closePanel);
    backdrop.addEventListener('click', closePanel);
})();