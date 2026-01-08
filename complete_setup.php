<?php
/**
 * EVOAPP Complete Setup - Create Tables and Admin User
 */

// Include config
require_once 'config/config.php';

// Database connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "✅ Database connected<br>";
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage());
}

// Create tables
$queries = [
    // Users table
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    // Roles table
    "CREATE TABLE IF NOT EXISTS roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) UNIQUE NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    // User roles junction table
    "CREATE TABLE IF NOT EXISTS user_roles (
        user_id INT,
        role_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, role_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
    )",
    
    // Instances table
    "CREATE TABLE IF NOT EXISTS instances (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(100) UNIQUE NOT NULL,
        description TEXT,
        api_key VARCHAR(255) NOT NULL,
        base_url VARCHAR(255),
        webhook_token VARCHAR(255),
        webhook_enabled TINYINT(1) DEFAULT 1,
        forward_webhook_url VARCHAR(255),
        forward_webhook_enabled TINYINT(1) DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        last_webhook_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )"
];

// Execute table creation
foreach ($queries as $query) {
    try {
        $pdo->exec($query);
        echo "✅ Table created successfully<br>";
    } catch (PDOException $e) {
        echo "⚠️ Table creation warning: " . $e->getMessage() . "<br>";
    }
}

// Insert default roles
$roleQueries = [
    "INSERT IGNORE INTO roles (id, name, description) VALUES (1, 'admin', 'System administrator')",
    "INSERT IGNORE INTO roles (id, name, description) VALUES (2, 'user', 'Regular user')"
];

foreach ($roleQueries as $query) {
    try {
        $pdo->exec($query);
        echo "✅ Default roles inserted<br>";
    } catch (PDOException $e) {
        echo "⚠️ Role insertion warning: " . $e->getMessage() . "<br>";
    }
}

// Check if admin user already exists
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
$stmt->execute(['admin@evoapp.com']);
$exists = $stmt->fetchColumn();

if ($exists) {
    echo "⚠️ Admin user already exists<br>";
    echo "<a href='index.php?r=auth/login'>Go to Login</a>";
    exit;
}

// Create admin user
$password = 'admin123';
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

try {
    $pdo->beginTransaction();
    
    // Insert user
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password_hash, is_active, created_at, updated_at)
        VALUES (?, ?, ?, 1, NOW(), NOW())
    ");
    $stmt->execute(['Administrator', 'admin@evoapp.com', $passwordHash]);
    $userId = $pdo->lastInsertId();
    
    // Assign admin role
    $stmt = $pdo->prepare("
        INSERT INTO user_roles (user_id, role_id, created_at)
        VALUES (?, 1, NOW())
    ");
    $stmt->execute([$userId]);
    
    $pdo->commit();
    
    echo "✅ Admin user created successfully<br><br>";
    echo "<strong>Login Credentials:</strong><br>";
    echo "Email: admin@evoapp.com<br>";
    echo "Password: admin123<br><br>";
    echo "<a href='index.php?r=auth/login'>Go to Login</a><br>";
    echo "<br><strong>⚠️ Important:</strong> Change the password after first login!";
    
} catch (Exception $e) {
    $pdo->rollBack();
    die("❌ Error creating admin user: " . $e->getMessage());
}
?>
