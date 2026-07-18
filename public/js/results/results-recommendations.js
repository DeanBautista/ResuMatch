/**
 * js/results/results-recommendations.js
 *
 * Handles:
 *   1. "Copy" button per recommendation row (existing behavior — kept
 *      as-is, just documenting it here since this file already owned it).
 *   2. "Export as PDF" button — fetches export-pdf.php via AJAX, turns
 *      the response into a Blob, and triggers a browser download without
 *      navigating away from results.php.
 *   3. "Save to History" button — left as a stub / existing hook point,
 *      not part of this change.
 */

document.addEventListener('DOMContentLoaded', () => {
    initCopyButtons();
    initExportPdfButton();
});

/**
 * Wires up every ".js-copy-recommendation" button to copy its
 * data-copy-text into the clipboard, with a small "Copied" state
 * on the button itself for feedback.
 */
function initCopyButtons() {
    const buttons = document.querySelectorAll('.js-copy-recommendation');

    buttons.forEach((btn) => {
        btn.addEventListener('click', async () => {
            const text = btn.dataset.copyText || '';

            try {
                await navigator.clipboard.writeText(text);
                flashButtonState(btn, 'Copied');
            } catch (err) {
                console.error('Copy failed:', err);
                flashButtonState(btn, 'Failed');
            }
        });
    });
}

function flashButtonState(btn, label) {
    const original = btn.textContent;
    btn.textContent = label;
    btn.disabled = true;

    setTimeout(() => {
        btn.textContent = original;
        btn.disabled = false;
    }, 1500);
}

/**
 * Wires up the "Export as PDF" button (#js-export-pdf) to call
 * export-pdf.php via fetch, then convert the response into a Blob and
 * trigger a download — no page navigation, no new tab.
 */
function initExportPdfButton() {
    const exportBtn = document.getElementById('js-export-pdf');
    if (!exportBtn) return;

    exportBtn.addEventListener('click', async () => {
        const originalLabel = exportBtn.textContent;
        exportBtn.disabled = true;
        exportBtn.textContent = 'Generating…';

        try {
            const response = await fetch('/export-pdf.php', {
                method: 'GET',
                // Ensures the session cookie is sent so export-pdf.php
                // can read $_SESSION['last_analysis'] for *this* user.
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error(`Server responded with ${response.status}`);
            }

            const blob = await response.blob();

            // Pull filename from Content-Disposition if present, else fallback.
            const disposition = response.headers.get('Content-Disposition') || '';
            const match = disposition.match(/filename="?([^"]+)"?/);
            const filename = match ? match[1] : 'match-results.pdf';

            const blobUrl = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = blobUrl;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(blobUrl);

            notifyExportResult('PDF downloaded successfully.');
        } catch (err) {
            console.error('PDF export failed:', err);
            notifyExportResult('Could not generate PDF. Please try again.', 'error');
        } finally {
            exportBtn.disabled = false;
            exportBtn.textContent = originalLabel;
        }
    });
}

/**
 * Thin wrapper around the existing toast partial/JS (partials/toast.php +
 * js/toast.js, both already included on results.php). Named distinctly
 * from the project's own global `showToast` so this never shadows or
 * recursively calls itself if both files declare a same-named function
 * on window.
 */
function notifyExportResult(message, type = 'success') {
    if (typeof window.showToast === 'function') {
        window.showToast(message, type);
    } else {
        console.log(`[toast:${type}]`, message);
    }
}