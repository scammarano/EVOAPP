<?php
/**
 * Manual verification: ensure users only see permitted instances.
 *
 * Usage:
 *   php verify_instance_access.php <userId> [instanceIdToCheck]
 */

require_once __DIR__ . '/config/config.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

App\Core\DB::init();

$userId = $argv[1] ?? null;
$instanceIdToCheck = $argv[2] ?? null;

if (!$userId) {
    echo "Usage: php verify_instance_access.php <userId> [instanceIdToCheck]\n";
    exit(1);
}

$userId = (int)$userId;
$instances = App\Models\Instance::getAccessibleInstances($userId);

echo "Accessible instances for user {$userId}:\n";
if (empty($instances)) {
    echo "- (none)\n";
} else {
    foreach ($instances as $instance) {
        $slug = $instance['slug'] ?? '(no slug)';
        echo "- {$instance['id']} :: {$slug}\n";
    }
}

if ($instanceIdToCheck !== null) {
    $instanceIdToCheck = (int)$instanceIdToCheck;
    $hasAccess = false;
    foreach ($instances as $instance) {
        if ((int)$instance['id'] === $instanceIdToCheck) {
            $hasAccess = true;
            break;
        }
    }

    echo "\nInstance {$instanceIdToCheck} access: " . ($hasAccess ? "ALLOWED" : "DENIED") . "\n";
    if (!$hasAccess) {
        echo "Expected: ensure user_instances has no can_view=1 row for this user/instance.\n";
    }
}
