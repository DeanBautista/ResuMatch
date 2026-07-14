<?php
// If the requested file actually exists (like a .css or .js file), serve it directly
$path = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (php_sapi_name() === 'cli-server' && is_file($path)) {
    return false;
}
require __DIR__ . '/index.php';