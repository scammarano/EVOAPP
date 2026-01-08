<?php
/**
 * Create Test Instance
 */

require_once 'config/config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // Check if instance already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM instances WHERE slug = ?");
    $stmt->execute(['main']);
    $exists = $stmt->fetchColumn();
    
    if ($exists) {
        echo "⚠️ Instance 'main' already exists<br>";
    } else {
        // Create test instance
        $stmt = $pdo->prepare("
            INSERT INTO instances (slug, description, api_key, base_url, webhook_enabled, is_active, created_at, updated_at)
            VALUES (?, ?, ?, ?, 1, 1, NOW(), NOW())
        ");
        $stmt->execute([
            'main',
            'Main WhatsApp Instance',
            'test-api-key-123',
            'https://eapi.camcam.com.ve'
        ]);
        
        echo "✅ Test instance 'main' created<br>";
    }
    
    // Show all instances
    $stmt = $pdo->query("SELECT * FROM instances ORDER BY slug");
    $instances = $stmt->fetchAll();
    
    echo "<h3>Current Instances:</h3>";
    foreach ($instances as $instance) {
        echo "- <strong>{$instance['slug']}</strong>: {$instance['description']}<br>";
    }
    
    echo "<br><a href='index.php?r=dashboard/index'>Go to Dashboard</a>";
    
} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage());
}
?>
