<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Admin\ResuMatch\Router;

$router = new Router();

$router->get('/', function () {
    require __DIR__ . '/../views/home.php';
});

$router->get('/results', function () {
    require __DIR__ . '/../views/results.php';
});

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);