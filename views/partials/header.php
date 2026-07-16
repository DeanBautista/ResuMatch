<?php
$currentPath = $GLOBALS['currentPath'] ?? '';
$isHome = $currentPath === '/';
?>
<header class="relative">
  <div class="max-w-3xl lg:max-w-5xl xl:max-w-6xl mx-auto flex items-center justify-between px-6 lg:px-10 py-4">
    <span class="text-xl font-semibold text-gray-900">ResuMatch</span>

    <!-- Desktop / tablet nav -->
    <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-gray-700">
      <a
        href="/"
        class="hover:text-gray-900 <?= $isHome ? 'text-gray-900 font-semibold' : '' ?>"
        <?= $isHome ? 'aria-current="page"' : '' ?>
      >New Check</a>
      <a href="/history" class="hover:text-gray-900">History</a>
    </nav>

    <!-- Mobile menu toggle -->
    <button
      type="button"
      id="menuToggle"
      class="md:hidden p-2"
      aria-label="Menu"
      aria-expanded="false"
      aria-controls="mobileMenu"
    >
      <svg class="w-6 h-6 text-gray-900" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/>
      </svg>
    </button>
  </div>

  <!-- Mobile dropdown panel -->
  <nav
    id="mobileMenu"
    class="md:hidden hidden flex-col px-6 pb-4 text-sm font-medium text-gray-700"
  >
    <a
      href="/"
      class="py-3 border-t border-gray-200/70 <?= $isHome ? 'text-gray-900 font-semibold' : '' ?>"
      <?= $isHome ? 'aria-current="page"' : '' ?>
    >New Check</a>
    <a href="/history" class="py-3 border-t border-gray-200/70">History</a>
  </nav>
</header>

<script src="/js/mobile-menu.js" defer></script>