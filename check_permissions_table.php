<?php
/**
 * Check Permissions Table Structure
 */

require_once 'config/config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✅ Database connected<br>";
    
    // Check permissions table structure
    echo "<h3>Permissions Table Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE permissions");
    $columns = $stmt->fetchAll();
    
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}) {$column['Null']} {$column['Key']}<br>";
    }
    
    // Check if there's any data
    echo "<h3>Current Permissions:</h3>";
    $stmt = $pdo->query("SELECT * FROM permissions");
    $permissions = $stmt->fetchAll();
    
    if (empty($permissions)) {
        echo "No permissions found<br>";
    } else {
        foreach ($permissions as $permission) {
            echo "- ID: {$permission['id']}, Name: " . ($permission['name'] ?? 'N/A') . "<br>";
        }
    }
    
    // Try to insert a test permission
    echo "<h3>Testing Insert:</h3>";
    try {
        $stmt = $pdo->prepare("INSERT INTO permissions (name, description) VALUES (?, ?)");
        $stmt->execute(['test.permission', 'Test permission']);
        echo "✅ Insert successful<br>";
        
        // Remove test permission
        $pdo->exec("DELETE FROM permissions WHERE name = 'test.permission'");
        echo "✅ Test permission removed<br>";
    } catch (Exception $e) {
        echo "❌ Insert failed: " . $e->getMessage() . "<br>";
    }
    
    // Show all tables
    echo "<h3>All Tables:</h3>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "- $table<br>";
    }
    
} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage());
}
?>
