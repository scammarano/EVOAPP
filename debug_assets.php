<?php
/**
 * Debug Asset Loading
 */

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
echo "APP_URL: " . (defined('APP_URL') ? APP_URL : 'NOT DEFINED') . "<br>";
echo "CSS URL: " . (defined('APP_URL') ? APP_URL . '/assets/css/app.css' : 'N/A') . "<br>";
echo "JS URL: " . (defined('APP_URL') ? APP_URL . '/assets/js/app.js' : 'N/A') . "<br>";

// Test if assets are accessible via web
echo "<h3>Web Access Test:</h3>";
$cssUrl = (defined('APP_URL') ? APP_URL : '') . '/assets/css/app.css';
$jsUrl = (defined('APP_URL') ? APP_URL : '') . '/assets/js/app.js';

echo "<a href='$cssUrl' target='_blank'>Test CSS File</a><br>";
echo "<a href='$jsUrl' target='_blank'>Test JS File</a><br>";

// Show current directory structure
echo "<h3>Directory Structure:</h3>";
function listDir($dir, $prefix = '') {
    if (!is_dir($dir)) return;
    
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item[0] == '.') continue;
        
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            echo $prefix . "📁 $item/<br>";
            if ($prefix === '' && $item === 'assets') {
                listDir($path, $prefix . '  ');
            }
        } else {
            echo $prefix . "📄 $item<br>";
        }
    }
}

listDir(__DIR__);

echo "<br><h3>Next Steps:</h3>";
echo "1. Click the links above to test if assets load<br>";
echo "2. If assets don't load, check .htaccess rules<br>";
echo "3. If assets load, the issue is in the HTML/JavaScript<br>";

echo "<br><a href='index.php?r=dashboard/index'>Try Dashboard Again</a>";
?>
