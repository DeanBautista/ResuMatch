<?php
/**
 * partials/results-header.php
 *
 * Title row for the results page: job title/company, "checked at" timestamp,
 * and the re-run button.
 *
 * Expects (set by results.php before including this file):
 *   string $jobTitle
 *   string $company
 *   string $checkedAt
 */

// Fallback logic for the heading — handles all four combinations of
// jobTitle/company being present or empty, since the raw "X @ Y" format
// breaks visually (dangling "@") when either side is missing.
$hasTitle   = trim((string) $jobTitle) !== '';
$hasCompany = trim((string) $company) !== '';

if ($hasTitle && $hasCompany) {
    $headingText = htmlspecialchars($jobTitle) . ' @ ' . htmlspecialchars($company);
} elseif ($hasTitle) {
    $headingText = htmlspecialchars($jobTitle);
} elseif ($hasCompany) {
    $headingText = htmlspecialchars($company);
} else {
    $headingText = 'Match Results';
}
?>
<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-6 sm:mb-8">
    <div>
        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 leading-tight">
            <?= $headingText ?>
        </h1>
        <p class="mt-1 flex items-center gap-1.5 text-sm text-gray-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
            </svg>
            <?= htmlspecialchars($checkedAt) ?>
        </p>
    </div>

    <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
        <a href="/" class="shrink-0 inline-flex items-center justify-center gap-2 w-full sm:w-auto rounded-full border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-50 hover:border-gray-400 active:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 111.414 1.414L7.414 9H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
            Analyze Another Resume
        </a>

        <button id="rerunCheckBtn" type="button" class="shrink-0 inline-flex items-center justify-center gap-2 w-full sm:w-auto rounded-full border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-50 transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
        </svg>
            Re-run check
        </button>
    </div>
</div>

<!-- partials/loading-overlay.php
     Full-screen "Analyzing" loading state for Match AI.
     Shown/hidden and driven entirely by js/resume-extract.js via #loadingOverlay.
     Re-run check (below) also drives it, via the same #loadingOverlay/#loadingMessage/
     #loadingBar/#loadingPhase elements, so both flows share one loading UI.
-->
<div id="loadingOverlay"
     class="fixed inset-0 z-[999] hidden items-center justify-center bg-white/90 backdrop-blur-sm px-6"
     role="status"
     aria-live="polite"
     aria-label="Analyzing resume">

  <div class="w-full max-w-xs sm:max-w-sm flex flex-col items-center text-center">

    <!-- Icon ring -->
    <div class="relative w-28 h-28 sm:w-36 sm:h-36 mb-6 sm:mb-8 shrink-0">
      <!-- soft pulsing halo -->
      <span class="absolute inset-0 rounded-full bg-orange-100/60 animate-loading-pulse"></span>
      <!-- static outer ring -->
      <span class="absolute inset-0 rounded-full border border-gray-100"></span>
      <!-- spinning progress ring -->
      <svg class="absolute inset-0 w-full h-full -rotate-90 animate-loading-spin" viewBox="0 0 100 100">
        <circle cx="50" cy="50" r="46" fill="none" stroke="#fed7aa" stroke-width="2.5" stroke-dasharray="40 250" stroke-linecap="round" />
      </svg>
      <!-- icon -->
      <div class="absolute inset-0 flex items-center justify-center">
        <svg id="loadingIcon" class="w-9 h-9 sm:w-11 sm:h-11 text-gray-900" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path id="loadingIconPath" stroke-linecap="round" stroke-linejoin="round"
                d="M9 12h6m-6 4h6M9 8h1M7 3h7l5 5v11a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z"
                class="animate-loading-draw" />
          <path class="checkmark-path opacity-0" stroke-linecap="round" stroke-linejoin="round" d="M8.5 12.5l2.4 2.4L16 9.6" />
        </svg>
      </div>
    </div>

    <!-- Message -->
    <p id="loadingMessage" class="text-gray-600 text-base sm:text-lg font-medium tracking-tight min-h-[1.75rem] transition-opacity duration-300">
      Reading your resume&hellip;
    </p>

    <!-- Progress bar -->
    <div class="w-full max-w-[280px] sm:max-w-xs h-1.5 bg-gray-100 rounded-full mt-5 overflow-hidden">
      <div id="loadingBar"
           class="h-full w-0 rounded-full bg-gradient-to-r from-orange-400 to-orange-500 transition-[width] duration-500 ease-out"></div>
    </div>

    <!-- Phase counter -->
    <p id="loadingPhase" class="text-[11px] sm:text-xs tracking-[0.2em] text-gray-400 mt-4 font-medium uppercase">
      Phase 1/4
    </p>

  </div>
</div>

<script src="<?= $GLOBALS['assetBase'] ?>/js/results/rerun-check.js" defer></script>