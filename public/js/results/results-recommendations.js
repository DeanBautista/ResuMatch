(function () {
    // --- Copy buttons -----------------------------------------------
    // Generic: works for any number of recommendation rows without
    // per-button wiring, since each button just reads its own
    // data-copy-text attribute.
    document.querySelectorAll('.js-copy-recommendation').forEach(function (btn) {
        btn.addEventListener('click', function () {
            let text = btn.getAttribute('data-copy-text') || '';

            function onCopied() {
                let original = btn.textContent;
                btn.textContent = 'Copied';
                btn.disabled = true;
                setTimeout(function () {
                    btn.textContent = original;
                    btn.disabled = false;
                }, 1500);

                // Use the app's existing toast system if it's on the page
                // (see partials/toast.php / js/toast.js), otherwise the
                // inline "Copied" button state above is sufficient
                // feedback on its own.
                if (typeof window.showToast === 'function') {
                    window.showToast('Recommendation copied to clipboard', 'success');
                }
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(onCopied).catch(function () {
                    fallbackCopy(text, onCopied);
                });
            } else {
                fallbackCopy(text, onCopied);
            }
        });
    });

    function fallbackCopy(text, done) {
        let ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try {
            document.execCommand('copy');
        } catch (e) {
            // Silently ignore — button just won't flip to "Copied".
        }
        document.body.removeChild(ta);
        done();
    }

    // --- Save to History / Export as PDF -----------------------------
    // These are stubbed as functional hooks: no backend endpoint for
    // either exists yet (there's no /api/history.php or PDF export route
    // in this codebase), so each dispatches a CustomEvent that a future
    // handler can listen for, plus a clear TODO for the real fetch() call.
    const saveBtn = document.getElementById('js-save-history');
    if (saveBtn) {
        saveBtn.addEventListener('click', function () {
            // TODO: replace with a real request once a history endpoint
            // exists, e.g.:
            //   fetch('/api/history.php', {
            //       method: 'POST',
            //       headers: { 'Content-Type': 'application/json' },
            //       body: JSON.stringify({ /* current result payload */ })
            //   })
            document.dispatchEvent(new CustomEvent('resumematch:save-history'));
            if (typeof window.showToast === 'function') {
                window.showToast('Saved to history');
            }
        });
    }

    const exportBtn = document.getElementById('js-export-pdf');
    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            // TODO: replace with a real request once a PDF export route
            // exists (e.g. server-side render via a headless browser, or
            // a client-side print stylesheet triggered via window.print()).
            document.dispatchEvent(new CustomEvent('resumematch:export-pdf'));
            if (typeof window.showToast === 'function') {
                window.showToast('Preparing PDF export…');
            }
        });
    }
})();