<?php
/**
 * EVOAPP Debug Script
 * Helps diagnose common issues
 */

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>EVOAPP Debug Information</h1>";

// Check PHP version
echo "<h2>PHP Version</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Required: 8.1+ - " . (version_compare(phpversion(), '8.1.0', '>=') ? '✅ OK' : '❌ FAIL') . "<br>";

// Check required extensions
echo "<h2>Required Extensions</h2>";
$required_extensions = ['pdo', 'curl', 'json', 'mbstring', 'fileinfo'];
foreach ($required_extensions as $ext) {
    $loaded = extension_loaded($ext) ? '✅' : '❌';
    echo "$ext: $loaded<br>";
}

// Check database connection
echo "<h2>Database Connection</h2>";
try {
    if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        echo "✅ Database connection successful<br>";
        echo "Host: " . DB_HOST . "<br>";
        echo "Database: " . DB_NAME . "<br>";
    } else {
        echo "❌ Database constants not defined<br>";
    }
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

// Check file permissions
echo "<h2>File Permissions</h2>";
$files_to_check = [
    __DIR__ . '/config/config.php',
    __DIR__ . '/app/Core/App.php',
    __DIR__ . '/index.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $readable = is_readable($file) ? '✅' : '❌';
        echo "$file: $readable<br>";
    } else {
        echo "$file: ❌ MISSING<br>";
    }
}

// Check .htaccess
echo "<h2>.htaccess</h2>";
$htaccess_file = __DIR__ . '/.htaccess';
if (file_exists($htaccess_file)) {
    echo "✅ .htaccess exists<br>";
    echo "Size: " . filesize($htaccess_file) . " bytes<br>";
} else {
    echo "❌ .htaccess missing<br>";
}

// Check uploads directory
echo "<h2>Uploads Directory</h2>";
$uploads_dir = __DIR__ . '/uploads';
if (is_dir($uploads_dir)) {
    $writable = is_writable($uploads_dir) ? '✅' : '❌';
    echo "Uploads directory: $writable<br>";
} else {
    echo "❌ Uploads directory missing<br>";
    if (!mkdir($uploads_dir, 0755, true)) {
        echo "✅ Created uploads directory<br>";
    } else {
        echo "❌ Failed to create uploads directory<br>";
    }
}

// Check constants
echo "<h2>Configuration Constants</h2>";
echo "APP_NAME: " . (defined('APP_NAME') ? APP_NAME : '❌ NOT SET') . "<br>";
echo "APP_URL: " . (defined('APP_URL') ? APP_URL : '❌ NOT SET') . "<br>";
echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : '❌ NOT SET') . "<br>";
echo "TIMEZONE: " . (defined('TIMEZONE') ? TIMEZONE : '❌ NOT SET') . "<br>";

echo "<h2>Server Information</h2>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";

echo "<h2>Session Status</h2>";
echo "Session Status: " . (session_status() ? '✅ Active' : '❌ Inactive') . "<br>";
echo "Session ID: " . session_id() . "<br>";

echo "<h2>Recommendations</h2>";
echo "<ol>";
echo "<li>Ensure config/config.php exists and is properly configured</li>";
echo "<li>Check database credentials and database exists</li>";
echo "<li>Verify all required PHP extensions are installed</li>";
echo "<li>Ensure uploads directory is writable</li>";
echo "<li>Check error logs in your hosting control panel</li>";
echo "</ol>";

// Try to include main files
echo "<h2>Main Files Check</h2>";
try {
    require_once __DIR__ . '/config/config.php';
    echo "✅ config/config.php loaded<br>";
} catch (Exception $e) {
    echo "❌ config/config.php failed: " . $e->getMessage() . "<br>";
}

try {
    require_once __DIR__ . '/app/Core/App.php';
    echo "✅ App.php loaded<br>";
} catch (Exception $e) {
    echo "❌ App.php failed: " . $e->getMessage() . "<br>";
}

try {
    require_once __DIR__ . '/app/Core/DB.php';
    echo "✅ DB.php loaded<br>";
} catch (Exception $e) {
    echo "❌ DB.php failed: " . $e->getMessage() . "<br>";
}

echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>1. Run this debug script to identify issues</li>";
echo "<li>2. Fix any configuration problems</li>";
echo "<li>3. Check your hosting error logs</li>";
echo "<li>4. Ensure database exists and is accessible</li>";
echo "<li>5. Verify file permissions</li>";
echo "</ol>";
?>
