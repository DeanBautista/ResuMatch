<?php
require __DIR__ . '/vendor/autoload.php';

echo "PHP version: " . PHP_VERSION . "\n";
echo "Looking for .env at: " . __DIR__ . "\n";
echo ".env exists here? " . (file_exists(__DIR__ . '/.env') ? "YES" : "NO") . "\n";
echo "phpdotenv installed classes:\n";
echo class_exists('Dotenv\Dotenv') ? "Dotenv\\Dotenv class found\n" : "Dotenv\\Dotenv class NOT found\n";

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $result = $dotenv->load();
    echo "load() returned: ";
    var_dump($result);
    echo "getenv result: ";
    var_dump(getenv('GEMINI_API_KEY'));
    echo "\$_ENV result: ";
    var_dump($_ENV['GEMINI_API_KEY'] ?? 'NOT IN _ENV');
} catch (\Throwable $e) {
    echo "EXCEPTION CAUGHT:\n";
    echo get_class($e) . ": " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}