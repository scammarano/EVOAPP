<?php
/**
 * Fix Permissions Using Correct Column Names
 */

require_once 'config/config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✅ Database connected<br>";
    
    // Clear existing permissions
    $pdo->exec("DELETE FROM permissions");
    $pdo->exec("DELETE FROM role_permissions");
    echo "🗑️ Cleared existing permissions<br>";
    
    // Insert permissions using 'key' column
    $permissions = [
        'instances.view' => 'View instances',
        'instances.manage' => 'Create, edit, delete instances',
        'chats.view' => 'View chats',
        'chats.send' => 'Send messages',
        'contacts.view' => 'View contacts',
        'contacts.edit' => 'Create, edit, delete contacts',
        'contacts.import' => 'Import contacts',
        'contacts.export' => 'Export contacts',
        'campaigns.view' => 'View campaigns',
        'campaigns.manage' => 'Create, edit, delete campaigns',
        'debug.view' => 'View debug information'
    ];
    
    foreach ($permissions as $key => $description) {
        try {
            $stmt = $pdo->prepare("INSERT INTO permissions (key, description) VALUES (?, ?)");
            $stmt->execute([$key, $description]);
            echo "✅ Permission added: $key<br>";
        } catch (Exception $e) {
            echo "❌ Failed to add $key: " . $e->getMessage() . "<br>";
        }
    }
    
    // Get admin user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['admin@evoapp.com']);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        die("❌ Admin user not found");
    }
    
    // Assign admin role if not assigned
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_roles WHERE user_id = ? AND role_id = 1");
    $stmt->execute([$admin['id']]);
    $hasRole = $stmt->fetchColumn();
    
    if (!$hasRole) {
        $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id, created_at) VALUES (?, 1, NOW())");
        $stmt->execute([$admin['id']]);
        echo "✅ Admin role assigned<br>";
    } else {
        echo "✅ Admin role already assigned<br>";
    }
    
    // Assign all permissions to admin
    $stmt = $pdo->query("SELECT id FROM permissions");
    $permissionIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($permissionIds as $permissionId) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, ?)");
        $stmt->execute([$permissionId]);
    }
    echo "✅ All permissions assigned to admin<br>";
    
    // Update User model to use 'key' instead of 'name'
    echo "<h3>Need to update User model</h3>";
    echo "The User model needs to use 'key' column instead of 'name' for permissions<br>";
    
    echo "<h3>Setup Complete!</h3>";
    echo "<a href='index.php?r=dashboard/index'>Go to Dashboard</a>";
    
} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage());
}
?>
