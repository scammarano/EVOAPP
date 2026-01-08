<?php
/**
 * EVOAPP Configuration
 */

// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'grupoecc_evoappWS');
define('DB_USER', 'grupoecc_toto');
define('DB_PASS', 'Toto123*.A');

// EvolutionAPI
define('EVO_BASE_URL', 'https://eapi.camcam.com.ve');
define('APP_KEY', '429683C4C977415CAAFCCE10F7D57E11');

// Application
define('APP_NAME', 'EVOAPP');
define('APP_URL', 'https://camcam.com.ve/evoappws');
define('TIMEZONE', 'America/Bogota');
define('DEBUG', true);

// Security
define('SESSION_LIFETIME', 7200); // 2 hours
define('CSRF_TOKEN_NAME', 'csrf_token');

// Pagination
define('CHATS_PER_PAGE', 15);
define('MESSAGES_PER_PAGE', 25);

// File uploads
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('UPLOAD_PATH', __DIR__ . '/../uploads');

// Timezone
date_default_timezone_set(TIMEZONE);

// Error reporting
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Database connection test
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    // Test query
    $stmt = $pdo->query("SELECT 1");
    $stmt->fetch();
    // Connection successful
} catch (PDOException $e) {
    // Connection failed - will show error
}
?>
