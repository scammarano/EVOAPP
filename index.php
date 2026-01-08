<?php
/**
 * EVOAPP - Front Controller
 * Multi-instance WhatsApp management via EvolutionAPI
 */

session_start();

// Load configuration
require_once __DIR__ . '/config/config.php';

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/app/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Initialize application
$app = new App\Core\App();
$app->run();
