<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Admin\ResuMatch\Router;

$router = new Router();

// Make the current path available globally for views (e.g. header.php)
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$GLOBALS['currentPath'] = $currentPath;

$router->get('/', function () {
    require __DIR__ . '/../views/home.php';
});

$router->get('/results', function () {
    require __DIR__ . '/../views/results.php';
});

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);