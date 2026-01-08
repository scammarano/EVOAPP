<?php
/**
 * EVOAPP Setup - Create Admin User
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
