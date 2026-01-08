<?php
/**
 * EVOAPP Simple Admin Setup
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

// Check table structure
echo "<h3>Checking users table structure...</h3>";
$stmt = $pdo->query("DESCRIBE users");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Columns found: " . implode(", ", $columns) . "<br>";

// Check if admin user already exists
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
$stmt->execute(['admin@evoapp.com']);
$exists = $stmt->fetchColumn();

if ($exists) {
    echo "⚠️ Admin user already exists<br>";
    echo "<a href='index.php?r=auth/login'>Go to Login</a>";
    exit;
}

// Create admin user - try different approaches
$password = 'admin123';
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Try 1: Without timestamp columns
try {
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password_hash, is_active)
        VALUES (?, ?, ?, 1)
    ");
    $stmt->execute(['Administrator', 'admin@evoapp.com', $passwordHash]);
    $userId = $pdo->lastInsertId();
    echo "✅ Admin user created (method 1)<br>";
    
    // Assign admin role
    $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, 1)");
    $stmt->execute([$userId]);
    echo "✅ Admin role assigned<br>";
    
} catch (Exception $e) {
    echo "⚠️ Method 1 failed: " . $e->getMessage() . "<br>";
    
    // Try 2: With explicit timestamps
    try {
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password_hash, is_active, created_at, updated_at)
            VALUES (?, ?, ?, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ");
        $stmt->execute(['Administrator', 'admin@evoapp.com', $passwordHash]);
        $userId = $pdo->lastInsertId();
        echo "✅ Admin user created (method 2)<br>";
        
        // Assign admin role
        $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id, created_at) VALUES (?, 1, CURRENT_TIMESTAMP)");
        $stmt->execute([$userId]);
        echo "✅ Admin role assigned<br>";
        
    } catch (Exception $e2) {
        echo "❌ All methods failed: " . $e2->getMessage() . "<br>";
        exit;
    }
}

echo "<br><strong>Login Credentials:</strong><br>";
echo "Email: admin@evoapp.com<br>";
echo "Password: admin123<br><br>";
echo "<a href='index.php?r=auth/login'>Go to Login</a><br>";
echo "<br><strong>⚠️ Important:</strong> Change the password after first login!";
?>
