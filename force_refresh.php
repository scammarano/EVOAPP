<?php
/**
 * Force Refresh Dashboard
 */

// Clear any output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Set no-cache headers
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Load config
require_once 'config/config.php';

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?r=auth/login');
    exit;
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
    
    $globalStats = [
        'instances' => count($instances),
        'chats' => array_sum(array_column($stats, 'chat_count')),
        'messages' => array_sum(array_column($stats, 'message_count')),
        'unread' => array_sum(array_column($stats, 'total_unread')),
        'campaigns' => 0,
        'contacts' => 0
    ];
    
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// Create viewHelper
$viewHelper = new class {
    public function escape($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    
    public function asset($path) {
        // Add timestamp to force refresh
        $timestamp = time();
        return APP_URL . '/assets/' . ltrim($path, '/') . '?v=' . $timestamp;
    }
    
    public function url($route) {
        return APP_URL . '/index.php?r=' . urlencode($route);
    }
    
    public function timeAgo($date) {
        if (!$date) return '';
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        $diff = time() - $timestamp;
        
        if ($diff < 60) return 'ahora';
        elseif ($diff < 3600) return floor($diff / 60) . ' min';
        elseif ($diff < 86400) return floor($diff / 3600) . ' h';
        elseif ($diff < 604800) return floor($diff / 86400) . ' días';
        else return date('d/m/Y', $timestamp);
    }
};

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Force Refresh Dashboard</title>
    <link rel="stylesheet" href="<?= $viewHelper->asset('css/app.css') ?>">
    <style>
        .debug-info {
            background: var(--surface);
            padding: 1rem;
            margin: 1rem;
            border-radius: 0.5rem;
            border: 1px solid var(--border);
        }
        .force-refresh {
            background: var(--primary-green);
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.25rem;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="sidebar">
            <div class="header">
                <h1><?= $viewHelper->escape(APP_NAME) ?></h1>
                <div class="header-actions">
                    <a href="<?= $viewHelper->url('dashboard/index') ?>" class="btn-icon" title="Dashboard">
                        <span class="icon-dashboard"></span>
                    </a>
                    <button class="btn-icon" onclick="window.location.href='<?= $viewHelper->url('auth/logout') ?>'" title="Logout">
                        <span class="icon-close"></span>
                    </button>
                </div>
            </div>
        </div>
        
        <div class="main-content">
            <div class="debug-info">
                <h3>🔄 Force Refresh Debug</h3>
                <p><strong>Assets loaded with timestamp:</strong> <?= time() ?></p>
                <p><strong>CSS URL:</strong> <?= $viewHelper->asset('css/app.css') ?></p>
                <p><strong>JS URL:</strong> <?= $viewHelper->asset('js/app.js') ?></p>
                <p><strong>Instances:</strong> <?= count($instances) ?></p>
                <button class="force-refresh" onclick="location.reload()">🔄 Reload Page</button>
                <button class="force-refresh" onclick="location.href='<?= $viewHelper->url('dashboard/index') ?>'">📊 Go to Dashboard</button>
            </div>
            
            <?php include __DIR__ . '/app/Views/dashboard/index.php'; ?>
        </div>
    </div>
    
    <script src="<?= $viewHelper->asset('js/app.js') ?>"></script>
    <script>
        console.log('Force refresh loaded at:', new Date());
        console.log('CSS loaded:', document.querySelector('link[rel="stylesheet"]'));
        console.log('JS loaded:', document.querySelector('script[src*="app.js"]'));
    </script>
</body>
</html>
