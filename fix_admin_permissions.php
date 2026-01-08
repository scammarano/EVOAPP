<?php
/**
 * Fix Admin Permissions
 */

require_once 'config/config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // Get admin user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['admin@evoapp.com']);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        die("❌ Admin user not found");
    }
    
    // Check if admin role is assigned
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_roles WHERE user_id = ? AND role_id = 1");
    $stmt->execute([$admin['id']]);
    $hasRole = $stmt->fetchColumn();
    
    if (!$hasRole) {
        // Assign admin role
        $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id, created_at) VALUES (?, 1, NOW())");
        $stmt->execute([$admin['id']]);
        echo "✅ Admin role assigned<br>";
    } else {
        echo "✅ Admin role already assigned<br>";
    }
    
    // Create permissions table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) UNIQUE NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create role_permissions table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS role_permissions (
            role_id INT,
            permission_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (role_id, permission_id),
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
            FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
        )
    ");
    
    // Insert default permissions
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
        $stmt = $pdo->prepare("INSERT IGNORE INTO permissions (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $description]);
    }
    
    // Assign all permissions to admin role
    $stmt = $pdo->query("SELECT id FROM permissions");
    $permissionIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($permissionIds as $permissionId) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, ?)");
        $stmt->execute([$permissionId]);
    }
    
    echo "✅ All permissions assigned to admin role<br>";
    echo "✅ Admin permissions fixed<br><br>";
    echo "<a href='index.php?r=dashboard/index'>Go to Dashboard</a>";
    
} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage());
}
?>
