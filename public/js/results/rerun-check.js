(function () {
  const rerunBtn = document.getElementById('rerunCheckBtn');
  if (!rerunBtn) return;

  const overlay      = document.getElementById('loadingOverlay');
  const loadingMsg    = document.getElementById('loadingMessage');
  const loadingBar     = document.getElementById('loadingBar');
  const loadingPhase   = document.getElementById('loadingPhase');

  function showOverlay() {
    if (!overlay) return;
    overlay.classList.remove('hidden');
    overlay.classList.add('flex');
    if (loadingMsg) loadingMsg.textContent = 'Re-running your check\u2026';
    if (loadingPhase) loadingPhase.textContent = 'Re-analyzing';
    if (loadingBar) loadingBar.style.width = '20%';
  }

  function hideOverlay() {
    if (!overlay) return;
    overlay.classList.add('hidden');
    overlay.classList.remove('flex');
    if (loadingBar) loadingBar.style.width = '0%';
  }

  function notify(message, isError) {
    // Reuse the existing toast system if it's on the page (js/toast.js);
    // fall back to a plain alert so the button still communicates
    // failure/success even if toast.js isn't loaded on this view.
    if (typeof window.showToast === 'function') {
      window.showToast(message, isError ? 'error' : 'success');
    } else {
      alert(message);
    }
  }

  rerunBtn.addEventListener('click', async function () {
    rerunBtn.disabled = true;
    showOverlay();

    // Give the bar a little life while we wait on the request, since a
    // single real LLM call has no natural incremental progress signal.
    let fakeProgress = 20;
    const progressTimer = setInterval(function () {
      fakeProgress = Math.min(fakeProgress + 15, 90);
      if (loadingBar) loadingBar.style.width = fakeProgress + '%';
    }, 700);

    try {
      const res = await fetch('/api/rerun.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
      });

      const data = await res.json();

      if (!res.ok || !data.ok) {
        clearInterval(progressTimer);
        hideOverlay();
        rerunBtn.disabled = false;
        notify(data.error || 'Re-run failed. Please try again.', true);
        return;
      }

      if (loadingBar) loadingBar.style.width = '100%';
      // Analysis result is now stored in session by rerun.php — reload
      // /results so results.php re-reads it from $_SESSION['last_analysis'].
      window.location.href = '/results';
    } catch (err) {
      clearInterval(progressTimer);
      hideOverlay();
      rerunBtn.disabled = false;
      notify('Network error while re-running the check. Please try again.', true);
    }
  });
})();