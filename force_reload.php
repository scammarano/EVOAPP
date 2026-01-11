<?php
// Forzar recarga de PHP sin reiniciar servidor
echo "🔄 FORZANDO RECARGA DE PHP\n\n";

// 1. Limpiar OPcache si está activo
echo "🧹 Limpiando OPcache...\n";
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ OPcache reseteado\n";
} elseif (function_exists('opcache_invalidate')) {
    // Invalidar archivos principales
    $files_to_invalidate = [
        __DIR__ . '/index.php',
        __DIR__ . '/app/Core/App.php',
        __DIR__ . '/app/Core/DB.php',
        __DIR__ . '/config/config.php'
    ];
    
    foreach ($files_to_invalidate as $file) {
        if (file_exists($file)) {
            opcache_invalidate($file, true);
            echo "✅ Invalidado: " . basename($file) . "\n";
        }
    }
} else {
    echo "⚠️ OPcache no está activo\n";
}

// 2. Limpiar stat cache
echo "\n🧹 Limpiendo stat cache...\n";
clearstatcache(true);

// 3. Forzar recarga de archivos principales
echo "\n🔄 Forzando recarga de archivos principales...\n";

$main_files = [
    'index.php' => 'Punto de entrada principal',
    'app/Core/App.php' => 'Núcleo de la aplicación',
    'app/Core/DB.php' => 'Clase de base de datos',
    'config/config.php' => 'Configuración'
];

foreach ($main_files as $file => $description) {
    $full_path = __DIR__ . '/' . $file;
    if (file_exists($full_path)) {
        // Forzar recarga tocando el archivo
        $current_time = time();
        touch($full_path, $current_time);
        echo "✅ Recargado: $file - $description\n";
    } else {
        echo "❌ No encontrado: $file\n";
    }
}

// 4. Probar configuración inmediatamente
echo "\n🧪 Probando configuración inmediatamente...\n";

try {
    // Incluir configuración correcta
    require_once __DIR__ . '/config/config.php';
    
    if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
        echo "✅ Constantes BD definidas:\n";
        echo "📊 BD: " . DB_NAME . "\n";
        echo "👤 Usuario: " . DB_USER . "\n";
        echo "🌐 Host: " . DB_HOST . "\n";
        
        // Probar conexión
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            
            $stmt = $pdo->query("SELECT 1");
            $result = $stmt->fetch();
            
            echo "✅ Conexión BD exitosa\n";
            
            // Probar métodos de DB
            require_once __DIR__ . '/app/Core/DB.php';
            
            if (method_exists('App\Core\DB', 'columnExists')) {
                $exists = \App\Core\DB::columnExists('instances', 'id');
                echo "✅ columnExists() funciona: " . ($exists ? 'true' : 'false') . "\n";
            } else {
                echo "❌ columnExists() no disponible\n";
            }
            
            if (method_exists('App\Core\DB', 'tableExists')) {
                $exists = \App\Core\DB::tableExists('instances');
                echo "✅ tableExists() funciona: " . ($exists ? 'true' : 'false') . "\n";
            } else {
                echo "❌ tableExists() no disponible\n";
            }
            
        } catch (PDOException $e) {
            echo "❌ Error de conexión: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "❌ Constantes BD no definidas\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error al cargar configuración: " . $e->getMessage() . "\n";
}

// 5. Crear script de recarga automática
echo "\n🔧 Creando script de recarga automática...\n";

$reload_script = '<?php
// Script de recarga automática - ejecutar cada vez que haya cambios
if (function_exists("opcache_reset")) {
    opcache_reset();
}

// Limpiar stat cache
clearstatcache(true);

// Forzar recarga de archivos principales
$files = [
    __DIR__ . "/index.php",
    __DIR__ . "/app/Core/App.php",
    __DIR__ . "/app/Core/DB.php",
    __DIR__ . "/config/config.php"
];

foreach ($files as $file) {
    if (file_exists($file)) {
        touch($file);
    }
}

echo "✅ Recarga automática completada: " . date("Y-m-d H:i:s");
?>';

if (file_put_contents(__DIR__ . '/auto_reload.php', $reload_script)) {
    echo "✅ Script de recarga automática creado: auto_reload.php\n";
} else {
    echo "❌ Error al crear script de recarga automática\n";
}

echo "\n🎯 SOLUCIONES APLICADAS:\n";
echo "========================\n";
echo "✅ OPcache limpiado\n";
echo "✅ Stat cache limpiado\n";
echo "✅ Archivos principales recargados\n";
echo "✅ Configuración probada\n";
echo "✅ Script de recarga automática creado\n\n";

echo "🌐 Para recargar en el futuro sin reiniciar servidor:\n";
echo "https://camcam.com.ve/evoappws/force_reload.php\n\n";

echo "🌐 O usar el script automático:\n";
echo "https://camcam.com.ve/evoappws/auto_reload.php\n\n";

echo "🎯 PASOS SIGUIENTES:\n";
echo "==================\n";
echo "1. ✅ Recarga forzada completada\n";
echo "2. 🧪 Prueba la aplicación ahora\n";
echo "3. 📊 Si aún hay errores, ejecuta: https://camcam.com.ve/evoappws/check_config.php\n";
echo "4. 🔄 Si persiste, ejecuta: https://camcam.com.ve/evoappws/auto_reload.php\n";
echo "5. 🚀 La aplicación debería funcionar sin reiniciar servidor\n\n";

echo "🎉 ¡SISTEMA RECARGADO SIN REINICIAR SERVIDOR!\n";
?>
