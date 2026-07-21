<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/svg+xml" href="/site-icon.svg">
<title>History &middot; ResuMatch</title>
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

      <div id="sortDropdownMenu" class="hidden absolute right-0 mt-2 w-full sm:w-48 bg-white border border-gray-200 rounded-xl shadow-[0_8px_24px_rgba(30,64,175,0.12)] z-20 overflow-hidden">
        <button type="button" class="sort-option w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition" data-value="recent">Most recent</button>
        <button type="button" class="sort-option w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition" data-value="oldest">Oldest</button>
        <button type="button" class="sort-option w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition" data-value="highest">Highest match</button>
        <button type="button" class="sort-option w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition" data-value="lowest">Lowest match</button>
      </div>
    </div>
  </section>

  <!-- History list -->
  <section id="historyList" class="space-y-4 pb-10 lg:pb-16"> </section>

  <!-- Empty state (hidden by default, shown via JS when search yields no results) -->
  <section id="historyEmptyState" class="hidden text-center py-16 lg:py-24">
    <svg class="w-10 h-10 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
    </svg>
    <p class="text-gray-600 font-medium">No checks match your search.</p>
  </section>

</main>

<!-- Delete confirmation modal -->
<div id="deleteConfirmModal" class="hidden fixed inset-0 z-50 flex items-center justify-center px-4">
  <div id="deleteConfirmBackdrop" class="absolute inset-0 bg-gray-900/40"></div>
  <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
    <h2 class="text-lg font-semibold text-gray-900">Delete this check?</h2>
    <p class="mt-2 text-sm text-gray-500">This can't be undone. The saved analysis will be permanently removed from your history.</p>
    <div class="mt-6 flex justify-end gap-3">
      <button type="button" id="deleteConfirmCancel" class="px-4 py-2 text-sm font-medium text-gray-700 rounded-full border border-gray-300 hover:bg-gray-50 transition">
        Cancel
      </button>
      <button type="button" id="deleteConfirmAccept" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-full hover:bg-red-700 transition">
        Delete
      </button>
    </div>
  </div>
</div>

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

<script src="<?= $GLOBALS['assetBase'] ?>/js/toast.js"></script>
<script src="<?= $GLOBALS['assetBase'] ?>/js/loading-overlay.js"></script>
<script src="<?= $GLOBALS['assetBase'] ?>/js/history/history-page.js"></script>

</body>
</html>