<!-- partials/loading-overlay.php
     Full-screen "Analyzing" loading state for Match AI.
     Shown/hidden and driven entirely by js/resume-extract.js via #loadingOverlay.
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