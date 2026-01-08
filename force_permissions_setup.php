<?php
/**
 * Force Permissions Setup - Check and Fix
 */

require_once 'config/config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✅ Database connected<br>";
    
    // Drop and recreate permissions table
    $pdo->exec("DROP TABLE IF EXISTS permissions");
    $pdo->exec("DROP TABLE IF EXISTS role_permissions");
    
    echo "🗑️ Old tables dropped<br>";
    
    // Create permissions table
    $pdo->exec("
        CREATE TABLE permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) UNIQUE NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Permissions table created<br>";
    
    // Create role_permissions table
    $pdo->exec("
        CREATE TABLE role_permissions (
            role_id INT,
            permission_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (role_id, permission_id),
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
            FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
        )
    ");
    echo "✅ Role permissions table created<br>";
    
    // Verify table structure
    echo "<h3>Verifying table structure...</h3>";
    $stmt = $pdo->query("DESCRIBE permissions");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Permissions columns: " . implode(", ", $columns) . "<br>";
    
    // Insert permissions one by one with error checking
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
    
    foreach ($permissions as $name => $description) {
        try {
            $stmt = $pdo->prepare("INSERT INTO permissions (name, description) VALUES (?, ?)");
            $stmt->execute([$name, $description]);
            echo "✅ Permission added: $name<br>";
        } catch (Exception $e) {
            echo "❌ Failed to add $name: " . $e->getMessage() . "<br>";
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
    
    echo "<h3>Setup Complete!</h3>";
    echo "<a href='index.php?r=dashboard/index'>Go to Dashboard</a>";
    
} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage());
}
?>
