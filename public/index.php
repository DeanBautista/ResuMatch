<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Admin\ResuMatch\Router;

$router = new Router();

$router->get('/', function () {
    echo "Welcome to ResuMatch!";
});

$router->get('/about', function () {
    echo "About ResuMatch";
});

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);