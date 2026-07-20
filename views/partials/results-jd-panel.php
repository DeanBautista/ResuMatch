<?php
/**
 * partials/results-jd-panel.php
 *
 * Floating "View Job Description" button, fixed to the bottom-right of the
 * viewport, that opens a slide-out panel with the JD text.
 *
 * Expects (from results.php):
 *   $jobDescription  string|null  — full JD text, or null if not available
 *                                   for this result (e.g. loaded from
 *                                   history before job_description was
 *                                   persisted to match_history)
 *   $jobTitle        string
 *   $company         string
 *
 * No dependency on JS frameworks — plain CSS transform + a few lines of
 * vanilla JS, consistent with the rest of the page (toast.js,
 * loading-overlay.js are similarly small hand-rolled widgets).
 */
$hasJd = $jobDescription !== null && trim($jobDescription) !== '';
?>
<button
    type="button"
    id="jd-fab"
    aria-haspopup="dialog"
    aria-controls="jd-panel"
    aria-expanded="false"
    class="fixed bottom-6 right-6 z-40 inline-flex items-center gap-2 rounded-full bg-white pl-4 pr-5 py-3 shadow-[0_8px_24px_-6px_rgba(15,23,42,0.25)] border border-gray-200/70 text-sm font-medium text-gray-800 hover:border-orange-300 hover:shadow-[0_10px_28px_-6px_rgba(249,115,22,0.35)] transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2"
>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-orange-500">
        <path d="M9 12h6m-6 4h6M9 8h1"/>
        <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z"/>
        <path d="M14 3v4a1 1 0 0 0 1 1h4"/>
    </svg>
    Job Description
</button>

<!-- Backdrop -->
<div
    id="jd-backdrop"
    class="fixed inset-0 z-40 bg-slate-900/30 backdrop-blur-[1px] opacity-0 pointer-events-none transition-opacity duration-300"
    aria-hidden="true"
></div>

<!-- Slide-out panel -->
<aside
    id="jd-panel"
    role="dialog"
    aria-modal="true"
    aria-labelledby="jd-panel-title"
    class="fixed top-0 right-0 z-50 h-full w-full sm:w-[440px] bg-white shadow-[-12px_0_40px_-12px_rgba(15,23,42,0.35)] translate-x-full transition-transform duration-300 ease-out flex flex-col"
>
    <div class="flex items-start justify-between gap-4 px-6 pt-6 pb-4 border-b border-gray-100">
        <div class="min-w-0">
            <p class="text-xs font-semibold tracking-wide text-orange-500 uppercase mb-1">Job Description</p>
            <h2 id="jd-panel-title" class="text-lg font-bold text-gray-900 truncate">
                <?= htmlspecialchars($jobTitle !== '' ? $jobTitle : '---') ?>
            </h2>
            <?php if ($company !== ''): ?>
                <p class="text-sm text-gray-500 truncate"><?= htmlspecialchars($company) ?></p>
            <?php endif; ?>
        </div>
        <button
            type="button"
            id="jd-panel-close"
            aria-label="Close job description panel"
            class="shrink-0 rounded-full p-2 text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-400"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                <path d="M18 6 6 18M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto px-6 py-5">
        <?php if ($hasJd): ?>
            <p class="whitespace-pre-wrap text-sm leading-relaxed text-gray-700"><?= htmlspecialchars($jobDescription) ?></p>
        <?php else: ?>
            <div class="h-full flex flex-col items-center justify-center text-center px-4 py-10">
                <div class="w-11 h-11 rounded-full bg-orange-50 flex items-center justify-center mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-orange-400">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 16v-4m0-4h.01"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-700">Job description not available</p>
                <p class="text-sm text-gray-500 mt-1 max-w-xs">This check was run before job descriptions were saved with results, so there's nothing to show here.</p>
            </div>
        <?php endif; ?>
    </div>
</aside>

<script src="<?= $GLOBALS['assetBase'] ?>/js/results/results-jd-panel.js"></script>