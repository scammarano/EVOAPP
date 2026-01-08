<?php
/**
 * Debug Dashboard HTML Output
 */

// Simulate dashboard controller
require_once 'config/config.php';

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("❌ User not logged in");
}

// Get user data
$user = [
    'id' => $_SESSION['user_id'],
    'name' => $_SESSION['user_name'],
    'email' => $_SESSION['user_email']
];

// Get instances
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    $instances = $pdo->query("SELECT * FROM instances WHERE is_active = 1 ORDER BY slug")->fetchAll();
    $stats = $pdo->query("
        SELECT 
            i.id,
            i.slug,
            i.description,
            i.is_active,
            i.last_webhook_at,
            COUNT(DISTINCT c.id) as chat_count,
            COUNT(DISTINCT CASE WHEN c.unread_count > 0 THEN c.id END) as unread_chats,
            COUNT(DISTINCT m.id) as message_count,
            MAX(m.ts) as last_message_at,
            SUM(c.unread_count) as total_unread
        FROM instances i
        LEFT JOIN chats c ON i.id = c.instance_id
        LEFT JOIN messages m ON c.id = m.chat_id
        GROUP BY i.id, i.slug, i.description, i.is_active, i.last_webhook_at
        ORDER BY i.slug
    ")->fetchAll();
    
    // Calculate global stats
    $globalStats = [
        'instances' => count($instances),
        'chats' => array_sum(array_column($stats, 'chat_count')),
        'messages' => array_sum(array_column($stats, 'message_count')),
        'unread' => array_sum(array_column($stats, 'total_unread')),
        'campaigns' => 0, // TODO: Get from campaigns table
        'contacts' => 0 // TODO: Get from contacts table
    ];
    
} catch (Exception $e) {
    die("❌ Database error: " . $e->getMessage());
}

echo "<h3>Debug Info:</h3>";
echo "User: {$user['name']} ({$user['email']})<br>";
echo "Instances: " . count($instances) . "<br>";
echo "Global Stats: " . json_encode($globalStats) . "<br>";

echo "<h3>Instance Data:</h3>";
foreach ($instances as $instance) {
    echo "- {$instance['slug']}: {$instance['description']}<br>";
}

echo "<h3>Stats Data:</h3>";
foreach ($stats as $stat) {
    echo "- {$stat['slug']}: {$stat['chat_count']} chats<br>";
}

// Check if view file exists and is readable
$viewFile = __DIR__ . '/app/Views/dashboard/index.php';
if (!file_exists($viewFile)) {
    die("❌ View file not found: $viewFile");
}

echo "<h3>View File:</h3>";
echo "✅ Found: $viewFile<br>";
echo "Size: " . filesize($viewFile) . " bytes<br>";

// Show first few lines of view file
echo "<h3>View File Preview:</h3>";
$lines = file($viewFile);
for ($i = 0; $i < min(10, count($lines)); $i++) {
    echo "Line " . ($i + 1) . ": " . htmlspecialchars($lines[$i]) . "<br>";
}

echo "<br><a href='index.php?r=dashboard/index'>Try Dashboard Again</a>";
?>
