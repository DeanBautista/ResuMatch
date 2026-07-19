<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>History &middot; Match</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/css/animations.css">
</head>
<body class="min-h-screen flex flex-col bg-[radial-gradient(circle_at_15%_-10%,#cfe0fb_0%,transparent_45%),radial-gradient(circle_at_100%_0%,#e3edfd_0%,transparent_50%),linear-gradient(160deg,#dbeafe_0%,#eaf3fd_45%,#f3f8fe_100%)] bg-fixed">

<?php include 'partials/header.php'; ?>

<main class="flex-1 max-w-3xl lg:max-w-5xl xl:max-w-6xl mx-auto w-full px-6 lg:px-10">
    
  <section class="pt-12 lg:pt-16 pb-6 lg:pb-8 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
    <div>
      <h1 class="text-3xl sm:text-4xl lg:text-4xl font-extrabold text-gray-900 leading-tight flex items-baseline gap-2 flex-wrap">
        History
        <span class="text-base sm:text-lg font-medium text-gray-500"><?= isset($checks) ? count($checks) : 12 ?> checks</span>
      </h1>
    </div>
  </section>

  <!-- Search & Sort -->
  <section class="flex flex-col sm:flex-row gap-3 sm:gap-4 mb-6">
    <div class="relative flex-1">
      <svg class="w-4 h-4 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
      </svg>
      <input
        type="text"
        id="historySearchInput"
        placeholder="Search history..."
        class="w-full border border-gray-300 bg-white rounded-full pl-11 pr-4 py-3 text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-800 transition"
      >
    </div>

    <div class="relative w-full sm:w-48 shrink-0">
      <button
        type="button"
        id="sortDropdownBtn"
        class="w-full flex items-center justify-between gap-2 border border-gray-300 bg-white rounded-full pl-5 pr-4 py-3 text-sm font-medium text-gray-800 hover:border-gray-400 transition"
      >
        <span id="sortDropdownLabel">Most recent</span>
        <svg class="w-4 h-4 text-gray-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
        </svg>
      </button>

      <div id="sortDropdownMenu" class="hidden absolute right-0 mt-2 w-full sm:w-48 bg-white border border-gray-200 rounded-xl shadow-[0_8px_24px_rgba(30,64,175,0.12)] z-10 overflow-hidden">
        <button type="button" class="sort-option w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition" data-value="recent">Most recent</button>
        <button type="button" class="sort-option w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition" data-value="oldest">Oldest</button>
        <button type="button" class="sort-option w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition" data-value="highest">Highest match</button>
        <button type="button" class="sort-option w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition" data-value="lowest">Lowest match</button>
      </div>
    </div>
  </section>

  <!-- History list -->
  <section id="historyList" class="space-y-4 pb-10 lg:pb-16">

    <!-- Item -->
    <a href="/results/1" class="history-item group border border-gray-300 bg-white rounded-2xl shadow-[0_2px_8px_rgba(30,64,175,0.06),0_12px_32px_rgba(30,64,175,0.10)] hover:shadow-[0_4px_12px_rgba(30,64,175,0.10),0_16px_36px_rgba(30,64,175,0.14)] transition-shadow duration-300 p-5 sm:p-6 flex items-center gap-4 sm:gap-5"
       data-title="senior product designer acme co" data-score="94" data-date="2">
      <div class="shrink-0 w-14 h-14 sm:w-16 sm:h-16 rounded-full border border-gray-200 flex items-center justify-center font-bold text-gray-900 text-sm sm:text-base">
        94%
      </div>
      <div class="min-w-0 flex-1">
        <p class="font-semibold text-gray-900 text-base sm:text-lg truncate">Senior Product Designer @ Acme Co.</p>
        <p class="mt-1 text-sm text-gray-500 flex items-center gap-1.5 flex-wrap">
          <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-green-100 shrink-0">
            <svg class="w-2.5 h-2.5 text-green-600" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
          </span>
          <span class="text-green-700 font-medium">Strong Match</span>
          <span class="text-gray-300">&middot;</span>
          <span>Checked 2 days ago</span>
        </p>
      </div>
      <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-700 group-hover:translate-x-0.5 transition shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
      </svg>
    </a>

    <!-- Item -->
    <a href="/results/2" class="history-item group border border-gray-300 bg-white rounded-2xl shadow-[0_2px_8px_rgba(30,64,175,0.06),0_12px_32px_rgba(30,64,175,0.10)] hover:shadow-[0_4px_12px_rgba(30,64,175,0.10),0_16px_36px_rgba(30,64,175,0.14)] transition-shadow duration-300 p-5 sm:p-6 flex items-center gap-4 sm:gap-5"
       data-title="ux researcher globaltech" data-score="72" data-date="5">
      <div class="shrink-0 w-14 h-14 sm:w-16 sm:h-16 rounded-full border border-gray-200 flex items-center justify-center font-bold text-gray-900 text-sm sm:text-base">
        72%
      </div>
      <div class="min-w-0 flex-1">
        <p class="font-semibold text-gray-900 text-base sm:text-lg truncate">UX Researcher @ GlobalTech</p>
        <p class="mt-1 text-sm text-gray-500 flex items-center gap-1.5 flex-wrap">
          <span class="text-amber-700 font-medium">Moderate Match</span>
          <span class="text-gray-300">&middot;</span>
          <span>Checked 5 days ago</span>
        </p>
      </div>
      <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-700 group-hover:translate-x-0.5 transition shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
      </svg>
    </a>

    <!-- Item -->
    <a href="/results/3" class="history-item group border border-gray-300 bg-white rounded-2xl shadow-[0_2px_8px_rgba(30,64,175,0.06),0_12px_32px_rgba(30,64,175,0.10)] hover:shadow-[0_4px_12px_rgba(30,64,175,0.10),0_16px_36px_rgba(30,64,175,0.14)] transition-shadow duration-300 p-5 sm:p-6 flex items-center gap-4 sm:gap-5"
       data-title="lead visual designer startup inc" data-score="88" data-date="7">
      <div class="shrink-0 w-14 h-14 sm:w-16 sm:h-16 rounded-full border border-gray-200 flex items-center justify-center font-bold text-gray-900 text-sm sm:text-base">
        88%
      </div>
      <div class="min-w-0 flex-1">
        <p class="font-semibold text-gray-900 text-base sm:text-lg truncate">Lead Visual Designer @ Startup Inc.</p>
        <p class="mt-1 text-sm text-gray-500 flex items-center gap-1.5 flex-wrap">
          <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-green-100 shrink-0">
            <svg class="w-2.5 h-2.5 text-green-600" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
          </span>
          <span class="text-green-700 font-medium">Strong Match</span>
          <span class="text-gray-300">&middot;</span>
          <span>Checked 1 week ago</span>
        </p>
      </div>
      <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-700 group-hover:translate-x-0.5 transition shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
      </svg>
    </a>

  </section>

  <!-- Empty state (hidden by default, shown via JS when search yields no results) -->
  <section id="historyEmptyState" class="hidden text-center py-16 lg:py-24">
    <svg class="w-10 h-10 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
    </svg>
    <p class="text-gray-600 font-medium">No checks match your search.</p>
  </section>

</main>

<footer class="border-t border-gray-200/60">
  <div class="max-w-3xl lg:max-w-5xl xl:max-w-6xl mx-auto px-6 lg:px-10 py-6 text-center">
    <nav class="flex items-center justify-center gap-6 text-sm text-gray-700">
      <a href="/privacy" class="hover:text-gray-900">Privacy</a>
      <a href="/terms" class="hover:text-gray-900">Terms</a>
      <a href="/support" class="hover:text-gray-900">Support</a>
    </nav>
    <p class="text-xs text-gray-500 mt-3">&copy; <?= date('Y') ?> Match AI. All rights reserved.</p>
  </div>
</footer>

<?php include 'partials/toast.php'; ?>
<?php include 'partials/loading-overlay.php'; ?>

<script src="/js/toast.js"></script>
<script src="/js/loading-overlay.js"></script>
<script src="/js/history/history-page.js"></script>

</body>
</html>