<?php
$currentPath = $GLOBALS['currentPath'] ?? '';
$isHome = $currentPath === '/';

// Auth state — relies on session already being started upstream (e.g. bootstrap/session_start()).
$isAuthenticated = !empty($_SESSION['user_id']);
$userName  = $_SESSION['name']  ?? '';
$userEmail = $_SESSION['email'] ?? '';

// Fallback display name + initial for the avatar bubble.
$displayName = $userName !== '' ? $userName : ($userEmail !== '' ? explode('@', $userEmail)[0] : 'Account');
$initial     = strtoupper(substr($displayName, 0, 1) ?: 'U');
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

      <?php if ($isAuthenticated): ?>
        <!-- Profile dropdown (desktop) -->
        <div class="relative" id="profileMenuWrap">
          <button
            type="button"
            id="profileMenuToggle"
            class="flex items-center gap-2 rounded-full pl-1 pr-3 py-1 hover:bg-gray-100 transition-colors"
            aria-haspopup="true"
            aria-expanded="false"
            aria-controls="profileMenu"
          >
            <span class="flex items-center justify-center w-7 h-7 rounded-full bg-gray-900 text-white text-xs font-semibold">
              <?= htmlspecialchars($initial, ENT_QUOTES) ?>
            </span>
            <span class="text-gray-900 font-medium max-w-[10rem] truncate">
              <?= htmlspecialchars($displayName, ENT_QUOTES) ?>
            </span>
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/>
            </svg>
          </button>

          <div
            id="profileMenu"
            role="menu"
            class="hidden absolute right-0 mt-2 w-56 rounded-xl border border-gray-200 bg-white shadow-lg py-1 z-20"
          >
            <div class="px-4 py-3 border-b border-gray-100">
              <p class="text-sm font-semibold text-gray-900 truncate"><?= htmlspecialchars($displayName, ENT_QUOTES) ?></p>
              <?php if ($userEmail): ?>
                <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($userEmail, ENT_QUOTES) ?></p>
              <?php endif; ?>
            </div>
            <form action="/api/auth/logout.php" method="POST" role="none">
              <button
                type="submit"
                role="menuitem"
                class="w-full text-left px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Log out
              </button>
            </form>
          </div>
        </div>
      <?php else: ?>
        <a
          href="/signin"
          class="flex items-center gap-1.5 hover:text-gray-900"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5M15 12H3"/>
          </svg>
          Sign in
        </a>
      <?php endif; ?>
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

  <!-- Overlay behind mobile dropdown -->
  <div
    id="mobileMenuOverlay"
    class="md:hidden hidden fixed inset-0 bg-black/40 z-30"
  ></div>

  <!-- Mobile dropdown panel -->
  <nav
    id="mobileMenu"
    class="md:hidden hidden flex-col absolute top-full left-0 right-0 mx-4 mt-2 rounded-xl bg-white border border-gray-200 shadow-lg px-6 py-2 text-sm font-medium text-gray-700 z-40"
  >
    <div class="flex flex-col">
        <a
        href="/"
        class="py-3 <?= $isHome ? 'text-gray-900 font-semibold' : '' ?>"
        <?= $isHome ? 'aria-current="page"' : '' ?>
      >New Check</a>
      <a href="/history" class="py-3 border-t border-gray-200">History</a>

   </div>
    
    <?php if ($isAuthenticated): ?>
      <div class="py-3 border-t border-gray-200">
        <div class="flex items-center gap-3 mb-3">
          <span class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-900 text-white text-xs font-semibold shrink-0">
            <?= htmlspecialchars($initial, ENT_QUOTES) ?>
          </span>
          <div class="min-w-0">
            <p class="text-sm font-semibold text-gray-900 truncate"><?= htmlspecialchars($displayName, ENT_QUOTES) ?></p>
            <?php if ($userEmail): ?>
              <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($userEmail, ENT_QUOTES) ?></p>
            <?php endif; ?>
          </div>
        </div>
        <form action="/api/auth/logout.php" method="POST">
          <button
            type="submit"
            class="w-full flex items-center gap-2 text-red-600 font-medium pb-1"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            Log out
          </button>
        </form>
      </div>
    <?php else: ?>
      <a
        href="/signin"
        class="py-3 border-t border-gray-200 flex items-center gap-1.5"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5M15 12H3"/>
        </svg>
        Sign in
      </a>
    <?php endif; ?>
  </nav>
</header>

<script src="/js/mobile-menu.js" defer></script>