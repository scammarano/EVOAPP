<?php
// Script para verificar y forzar la recarga del método columnExists
echo "🔍 Verificando método columnExists()...\n";

// Incluir manualmente el archivo DB
require_once __DIR__ . '/app/Core/DB.php';

// Verificar si la clase existe
if (class_exists('App\Core\DB')) {
    echo "✅ Clase App\Core\DB encontrada\n";
    
    // Verificar si el método existe
    if (method_exists('App\Core\DB', 'columnExists')) {
        echo "✅ Método columnExists() encontrado\n";
        
        // Probar el método
        try {
            $result = \App\Core\DB::columnExists('instances', 'id');
            echo "✅ Método columnExists() funciona correctamente\n";
            echo "📊 Resultado de prueba: " . ($result ? 'true' : 'false') . "\n";
        } catch (Exception $e) {
            echo "❌ Error al probar columnExists(): " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ Método columnExists() NO encontrado\n";
        echo "🔍 Métodos disponibles en DB:\n";
        $methods = get_class_methods('App\Core\DB');
        foreach ($methods as $method) {
            echo "  - $method\n";
        }
    }
} else {
    echo "❌ Clase App\Core\DB NO encontrada\n";
}

// Mostrar información de OPcache
echo "\n📊 Información de OPcache:\n";
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status();
    echo "✅ OPcache está activo\n";
    echo "📈 Scripts cacheados: " . $status['opcache_statistics']['num_cached_scripts'] . "\n";
    echo "🔄 Hits: " . $status['opcache_statistics']['hits'] . "\n";
    echo "❌ Misses: " . $status['opcache_statistics']['misses'] . "\n";
} else {
    echo "⚠️ OPcache no está activo\n";
}

echo "\n🎯 Recomendaciones:\n";
echo "1. Ejecuta clear_cache.php para limpiar caches\n";
echo "2. Reinicia el servidor web si es posible\n";
echo "3. Verifica que los archivos estén actualizados en el servidor\n";
?>
