<?php
/**
 * Debug Asset Loading - Fixed
 */

// Load config first
require_once 'config/config.php';

echo "<h3>Configuration Check:</h3>";
echo "APP_NAME: " . (defined('APP_NAME') ? APP_NAME : 'NOT DEFINED') . "<br>";
echo "APP_URL: " . (defined('APP_URL') ? APP_URL : 'NOT DEFINED') . "<br>";

echo "<h3>Asset Paths Debug:</h3>";

// Check if assets directory exists
$assetsDir = __DIR__ . '/assets';
echo "Assets directory: " . (is_dir($assetsDir) ? '✅ Exists' : '❌ Missing') . "<br>";

// Check CSS file
$cssFile = __DIR__ . '/assets/css/app.css';
echo "CSS file: " . (file_exists($cssFile) ? '✅ Exists' : '❌ Missing') . "<br>";
echo "CSS size: " . (file_exists($cssFile) ? filesize($cssFile) . ' bytes' : 'N/A') . "<br>";

// Check JS file
$jsFile = __DIR__ . '/assets/js/app.js';
echo "JS file: " . (file_exists($jsFile) ? '✅ Exists' : '❌ Missing') . "<br>";
echo "JS size: " . (file_exists($jsFile) ? filesize($jsFile) . ' bytes' : 'N/A') . "<br>";

// Show calculated asset URLs
echo "<h3>Asset URLs:</h3>";
$cssUrl = (defined('APP_URL') ? APP_URL : '') . '/assets/css/app.css';
$jsUrl = (defined('APP_URL') ? APP_URL : '') . '/assets/js/app.js';

echo "CSS URL: $cssUrl<br>";
echo "JS URL: $jsUrl<br>";

echo "<h3>Web Access Test:</h3>";
echo "<a href='$cssUrl' target='_blank'>Test CSS File</a><br>";
echo "<a href='$jsUrl' target='_blank'>Test JS File</a><br>";

// Test direct file access
echo "<h3>Direct File Test:</h3>";
if (file_exists($cssFile)) {
    echo "<a href='/evoappws/assets/css/app.css' target='_blank'>Direct CSS (relative)</a><br>";
}
if (file_exists($jsFile)) {
    echo "<a href='/evoappws/assets/js/app.js' target='_blank'>Direct JS (relative)</a><br>";
}

// Check .htaccess
echo "<h3>.htaccess Check:</h3>";
$htaccessFile = __DIR__ . '/.htaccess';
if (file_exists($htaccessFile)) {
    echo "✅ .htaccess exists<br>";
    $content = file_get_contents($htaccessFile);
    if (strpos($content, 'assets') !== false) {
        echo "⚠️ .htaccess mentions 'assets' - check rules<br>";
    }
} else {
    echo "❌ .htaccess missing<br>";
}

echo "<br><h3>Solutions:</h3>";
echo "1. If APP_URL is now defined, try the dashboard again<br>";
echo "2. If assets still 404, check .htaccess rules<br>";
echo "3. If direct links work, the issue is in URL generation<br>";

echo "<br><a href='index.php?r=dashboard/index'>Try Dashboard Again</a>";
?>
