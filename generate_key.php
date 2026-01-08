<?php
// Generate secure APP_KEY
$key = bin2hex(random_bytes(16));
echo "APP_KEY: " . $key . "\n";
echo "Copy this key to your config.php file\n";
?>
