<?php
/**
 * Debug Dashboard Issues
 */

require_once 'config/config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✅ Database connected<br>";
    
    // Check instances
    echo "<h3>Instances in Database:</h3>";
    $stmt = $pdo->query("SELECT * FROM instances ORDER BY slug");
    $instances = $stmt->fetchAll();
    
    if (empty($instances)) {
        echo "❌ No instances found in database<br>";
    } else {
        foreach ($instances as $instance) {
            echo "- ID: {$instance['id']}, Slug: {$instance['slug']}, Active: " . ($instance['is_active'] ? 'Yes' : 'No') . "<br>";
        }
    }
    
    // Check instance stats
    echo "<h3>Instance Stats Query:</h3>";
    try {
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
        
        echo "✅ Stats query successful<br>";
        echo "Found " . count($stats) . " instance stats<br>";
        
        foreach ($stats as $stat) {
            echo "- {$stat['slug']}: {$stat['chat_count']} chats, {$stat['message_count']} messages<br>";
        }
    } catch (Exception $e) {
        echo "❌ Stats query failed: " . $e->getMessage() . "<br>";
    }
    
    // Check files
    echo "<h3>File Check:</h3>";
    $files = [
        'assets/css/app.css',
        'assets/js/app.js',
        'app/Controllers/DashboardController.php',
        'app/Views/dashboard/index.php'
    ];
    
    foreach ($files as $file) {
        $path = __DIR__ . '/' . $file;
        if (file_exists($path)) {
            echo "✅ $file exists<br>";
        } else {
            echo "❌ $file missing<br>";
        }
    }
    
    // Check current user
    echo "<h3>Current User:</h3>";
    session_start();
    if (isset($_SESSION['user_id'])) {
        echo "✅ User logged in: ID {$_SESSION['user_id']}, Email {$_SESSION['user_email']}<br>";
    } else {
        echo "❌ No user logged in<br>";
    }
    
    echo "<br><a href='index.php?r=dashboard/index'>Try Dashboard Again</a>";
    
} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage());
}
?>
