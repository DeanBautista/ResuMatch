<?php
session_start();

$assetBase = $_ENV['ASSET_BASE'] ?? '';
$GLOBALS['assetBase'] = $assetBase;

error_log('[DEBUG] session id=' . session_id() . ' data=' . json_encode($_SESSION));
require_once __DIR__ . '/../vendor/autoload.php';

use Admin\ResuMatch\Router;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// Make the current path available globally for views (e.g. header.php)
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$GLOBALS['currentPath'] = $currentPath;

// --- Auth guard ---------------------------------------------------------
// Paths that require a logged-in user (i.e. $_SESSION['user_id'] set).
// Anything in this list, when hit without auth, bounces back to '/'.
//
// NOTE: '/results' (bare, no id) is intentionally NOT in this list —
// guests can run a resume analysis without signing in, and analyze.php
// redirects them straight to '/results' using session data. Only
// '/results/{id}' (loading a specific saved row from match_history)
// requires auth, since that's the only path that touches another user's
// data. Checked via isPathOrChild() so '/results/5', '/results/12' etc.
// all match without matching bare '/results'.
$protectedPaths = [
    '/history',
];

// '/results/{id}' specifically requires auth (see note above) even though
// bare '/results' does not — kept separate from $protectedPaths so the
// bare path isn't accidentally swept in by a shared prefix rule.
$protectedChildOnlyPaths = [
    '/results',
];

// All paths this app actually knows about. Anything NOT matched here
// (i.e. an unregistered/unknown URI) also bounces back to '/'.
$knownPaths = [
    '/',
    '/results',
    '/signin',
    '/history',
];

/**
 * True if $path is exactly $base or a single-segment child of it
 * (e.g. base '/results' matches '/results' and '/results/5', but not
 * '/results/5/extra' or '/resultsX').
 */
function isPathOrChild(string $base, string $path): bool
{
    if ($path === $base) {
        return true;
    }
    return strpos($path, $base . '/') === 0 && substr_count($path, '/') === substr_count($base, '/') + 1;
}

/**
 * True if $path is a single-segment CHILD of $base only (e.g. '/results/5'),
 * NOT the bare $base itself (e.g. NOT '/results').
 */
function isChildOnly(string $base, string $path): bool
{
    return $path !== $base && isPathOrChild($base, $path);
}

$isLoggedIn = !empty($_SESSION['user_id']);

$isProtected = false;
foreach ($protectedPaths as $protectedPath) {
    if (isPathOrChild($protectedPath, $currentPath)) {
        $isProtected = true;
        break;
    }
}
if (!$isProtected) {
    foreach ($protectedChildOnlyPaths as $protectedPath) {
        if (isChildOnly($protectedPath, $currentPath)) {
            $isProtected = true;
            break;
        }
    }
}

if ($isProtected && !$isLoggedIn) {
    error_log("[DEBUG] Blocked unauthenticated access to {$currentPath}, redirecting to /");
    header('Location: /');
    exit;
}

$isKnown = false;
foreach ($knownPaths as $knownPath) {
    if (isPathOrChild($knownPath, $currentPath)) {
        $isKnown = true;
        break;
    }
}

if (!$isKnown) {
    error_log("[DEBUG] Unregistered path {$currentPath}, redirecting to /");
    header('Location: /');
    exit;
}
// -------------------------------------------------------------------------

$router = new Router();

$router->get('/', function () {
    require __DIR__ . '/../views/home.php';
});

$router->get('/results', function () {
    require __DIR__ . '/../views/results.php';
});

// /results/{id} — same view, id is picked up from route param inside
// results.php via $GLOBALS['routeParams']['id'] (see below)
$router->get('/results/:id', function ($id) {
    $GLOBALS['routeParams'] = ['id' => $id];
    require __DIR__ . '/../views/results.php';
});

$router->get('/signin', function () {
    require __DIR__ . '/../views/signin.php';
});

$router->get('/history', function () {
    require __DIR__ . '/../views/history.php';
});

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);