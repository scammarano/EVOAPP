<?php
// Script para limpiar caches de PHP y OPcache
echo "🧹 Limpiando caches del sistema...\n";

// Limpiar OPcache si está activo
if (function_exists('opcache_reset')) {
    echo "✅ OPcache reseteado\n";
    opcache_reset();
} else {
    echo "⚠️ OPcache no está activo\n";
}

// Limpiar estatísticas de OPcache
if (function_exists('opcache_invalidate')) {
    // Invalidar archivos específicos del sistema
    $files_to_invalidate = [
        'app/Core/DB.php',
        'app/Models/Chat.php',
        'app/Controllers/WebhookController.php',
        'app/Controllers/InboxController.php'
    ];
    
    foreach ($files_to_invalidate as $file) {
        $full_path = __DIR__ . '/' . $file;
        if (file_exists($full_path)) {
            opcache_invalidate($full_path, true);
            echo "✅ Invalidado: $file\n";
        }
    }
}

// Limpiar cache de autoloader si existe
$autoload_cache = __DIR__ . '/vendor/composer/autoload_classmap.php';
if (file_exists($autoload_cache)) {
    echo "✅ Cache de autoloader encontrado\n";
    // Intentar regenerar
    if (file_exists(__DIR__ . '/composer.json')) {
        echo "🔄 Regenerando autoloader...\n";
        shell_exec('cd ' . __DIR__ . ' && composer dump-autoload 2>/dev/null');
    }
}

echo "🎉 Limpieza completada!\n";
echo "📝 Por favor, recarga la página para probar de nuevo.\n";
?>
