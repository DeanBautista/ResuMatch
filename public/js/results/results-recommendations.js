/**
 * js/results/results-recommendations.js
 *
 * Handles:
 *   1. "Copy" button per recommendation row (existing behavior — kept
 *      as-is, just documenting it here since this file already owned it).
 *   2. "Export as PDF" button — fetches export-pdf.php via AJAX, turns
 *      the response into a Blob, and triggers a browser download without
 *      navigating away from results.php.
 *   3. "Save to History" button — POSTs to api/save-history.php, which
 *      persists $_SESSION['last_analysis'] (already populated server-side
 *      by run_analysis.php after a successful analyze/rerun) into the
 *      match_history table for the signed-in user. No payload is sent —
 *      everything needed already lives in the PHP session.
 */

document.addEventListener('DOMContentLoaded', () => {
    initCopyButtons();
    initExportPdfButton();
    initSaveHistoryButton();
});

/**
 * Wires up every ".js-copy-recommendation" button to copy its
 * data-copy-text into the clipboard, with a small "Copied" state
 * on the button itself for feedback.
 */
function initCopyButtons() {
    const buttons = document.querySelectorAll('.js-copy-recommendation');

    buttons.forEach((btn) => {
        const restingLabel = btn.textContent;

        btn.addEventListener('click', async () => {
            const text = btn.dataset.copyText || '';

            try {
                await navigator.clipboard.writeText(text);
                flashButtonState(btn, 'Copied', restingLabel);
            } catch (err) {
                console.error('Copy failed:', err);
                flashButtonState(btn, 'Failed', restingLabel);
            }
        });
    });
}

/**
 * Temporarily shows `label` on `btn` (e.g. "Copied", "Saved"), then
 * restores it to `restoreLabel` after a short delay. `restoreLabel` is
 * required explicitly rather than read from btn.textContent at call
 * time, because by the time this runs the button's current text may
 * already be a transitional one (e.g. "Saving…"), not its true resting
 * label — reading it here would "freeze in" the wrong text once
 * restored.
 */
function flashButtonState(btn, label, restoreLabel) {
    btn.textContent = label;
    btn.disabled = true;

    setTimeout(() => {
        btn.textContent = restoreLabel;
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
 * Wires up the "Save to History" button (#js-save-history) to call
 * api/save-history.php via fetch. No request body needed — the endpoint
 * reads $_SESSION['last_analysis'] / ['last_analysis_input'] set by
 * run_analysis.php. Session cookie carries auth ($_SESSION['user_id']),
 * so an unauthenticated user gets a 401 back, which we surface as a
 * plain toast rather than redirecting — keeps this button harmless to
 * click for a logged-out visitor instead of throwing them somewhere.
 */
function initSaveHistoryButton() {
    const saveBtn = document.getElementById('js-save-history');
    if (!saveBtn) return;

    // Captured once, from the button's resting state, so later renames
    // (to "Saving…" / "Saved") never get mistaken for the "real" label.
    const restingLabel = saveBtn.textContent;

    saveBtn.addEventListener('click', async () => {
        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving…';

        try {
            const response = await fetch('/api/save-history.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                },
            });

            // Read as text first (not response.json()) so that if the body
            // isn't valid JSON — e.g. a PHP warning/notice got echoed
            // before the json_encode() call — we can still log exactly
            // what the server actually sent, instead of a vague generic
            // error with no way to see what went wrong.
            const rawBody = await response.text();
            let data = null;
            try {
                data = JSON.parse(rawBody);
            } catch (parseErr) {
                console.error('Save to history: response was not valid JSON. Raw body:', rawBody);
            }

            if (response.status === 401) {
                notifyExportResult('Please sign in to save this result to your history.', 'error');
                saveBtn.textContent = restingLabel;
                saveBtn.disabled = false;
                return;
            }

            if (!response.ok || !data || data.ok !== true) {
                const message = (data && data.error)
                    || (data === null ? 'Server returned malformed response (see console for raw body).' : null)
                    || `Server responded with ${response.status}`;
                throw new Error(message);
            }

            // Success is permanent for this page load: no point letting
            // someone save the same analysis to history twice. Button
            // stays showing "Saved" and disabled — no revert timer.
            saveBtn.textContent = 'Saved';
            saveBtn.disabled = true;
            notifyExportResult('Saved to your history.');
        } catch (err) {
            console.error('Save to history failed:', err);
            notifyExportResult('Could not save to history. Please try again.', 'error');
            saveBtn.textContent = restingLabel;
            saveBtn.disabled = false;
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