<?php
session_start();
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
$protectedPaths = [
    '/history',
];

// All paths this app actually knows about. Anything NOT in this list
// (i.e. an unregistered/unknown URI) also bounces back to '/'.
$knownPaths = [
    '/',
    '/results',
    '/signin',
    '/history',
];

$isLoggedIn = !empty($_SESSION['user_id']);

if (in_array($currentPath, $protectedPaths, true) && !$isLoggedIn) {
    error_log("[DEBUG] Blocked unauthenticated access to {$currentPath}, redirecting to /");
    header('Location: /');
    exit;
}

if (!in_array($currentPath, $knownPaths, true)) {
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

$router->get('/signin', function () {
    require __DIR__ . '/../views/signin.php';
});

$router->get('/history', function () {
    require __DIR__ . '/../views/history.php';
});

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);