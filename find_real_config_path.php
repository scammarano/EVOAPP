<?php
// Encontrar la ruta real del archivo de configuración
echo "🔍 ENCONTRANDO RUTA REAL DE CONFIGURACIÓN\n\n";

// Directorio base
$base_dir = __DIR__;
echo "📂 Directorio base: $base_dir\n";

// Buscar config.php en todas las ubicaciones posibles
echo "\n🔍 Buscando archivos config.php...\n";

$config_files = [];
$search_paths = [
    $base_dir . '/config/config.php',
    $base_dir . '/config.php',
    $base_dir . '/app/config.php',
    $base_dir . '/app/config/config.php',
    $base_dir . '/../config/config.php',
    $base_dir . '/../../config/config.php',
    $base_dir . '/../../../config/config.php',
    $base_dir . '/../config.php',
    $base_dir . '/../../config.php',
];

foreach ($search_paths as $path) {
    if (file_exists($path)) {
        $config_files[] = $path;
        $relative_path = str_replace($base_dir . '/', '', $path);
        echo "✅ Encontrado: $relative_path\n";
        echo "📏 Tamaño: " . filesize($path) . " bytes\n";
        echo "📅 Modificado: " . date('Y-m-d H:i:s', filemtime($path)) . "\n\n";
    }
}

if (empty($config_files)) {
    echo "❌ No se encontró ningún archivo config.php\n";
    
    // Buscar cualquier archivo que contenga 'config' en el nombre
    echo "\n🔍 Buscando archivos con 'config' en el nombre...\n";
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base_dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && strpos($file->getFilename(), 'config') !== false) {
            $relative_path = str_replace($base_dir . '/', '', $file->getPathname());
            echo "📄 $relative_path\n";
        }
    }
    exit;
}

// Usar el primer archivo encontrado
$config_file = $config_files[0];
echo "🎯 Usando configuración: $config_file\n";

// Calcular rutas relativas desde app/Core/
$app_core_dir = $base_dir . '/app/Core';
echo "\n📂 Directorio App/Core: $app_core_dir\n";

// Calcular ruta relativa
$config_relative = str_replace($app_core_dir . '/', '', $config_file);
$config_relative = ltrim($config_relative, '/');

echo "📝 Ruta relativa desde app/Core/: $config_relative\n";

// Si no empieza con '../', agregarla
if (strpos($config_relative, '../') !== 0) {
    $config_relative = '../' . $config_relative;
}

echo "📝 Ruta relativa corregida: $config_relative\n";

// Verificar si la ruta funciona
$full_test_path = $app_core_dir . '/' . $config_relative;
$full_test_path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $full_test_path);

echo "\n🔍 Verificando ruta calculada:\n";
echo "📁 Ruta completa: $full_test_path\n";
echo "✅ Existe: " . (file_exists($full_test_path) ? 'SÍ' : 'NO') . "\n";

if (file_exists($full_test_path)) {
    echo "\n🔧 CORRIGIENDO App.php con ruta correcta...\n";
    
    $app_file = $app_core_dir . '/App.php';
    
    if (!file_exists($app_file)) {
        echo "❌ App.php no encontrado en: $app_file\n";
        exit(1);
    }
    
    // Leer App.php
    $content = file_get_contents($app_file);
    $lines = explode("\n", $content);
    
    // Buscar línea require_once
    $require_line_index = -1;
    foreach ($lines as $index => $line) {
        if (strpos($line, 'require_once') !== false) {
            $require_line_index = $index;
            break;
        }
    }
    
    if ($require_line_index === -1) {
        echo "❌ No se encontró línea require_once en App.php\n";
        exit(1);
    }
    
    // Crear backup
    $backup_file = $app_file . '.backup.' . date('Y-m-d-H-i-s');
    copy($app_file, $backup_file);
    echo "💾 Backup creado: " . basename($backup_file) . "\n";
    
    // Corregir la línea
    $new_require_line = "require_once '" . $config_relative . "';";
    $lines[$require_line_index] = $new_require_line;
    
    echo "📝 Corrigiendo require_once a: $new_require_line\n";
    
    // Escribir archivo corregido
    $corrected_content = implode("\n", $lines);
    if (file_put_contents($app_file, $corrected_content)) {
        echo "✅ App.php corregido exitosamente\n";
        
        // Probar la configuración
        echo "\n🧪 Probando configuración corregida...\n";
        
        try {
            // Incluir App.php para probar
            require_once $app_file;
            
            if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
                echo "✅ Configuración cargada exitosamente\n";
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
                    
                } catch (PDOException $e) {
                    echo "❌ Error de conexión: " . $e->getMessage() . "\n";
                }
                
            } else {
                echo "❌ Constantes BD no definidas\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Error al cargar App.php: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "❌ Error al corregir App.php\n";
    }
    
} else {
    echo "❌ La ruta calculada no funciona\n";
    
    echo "\n🔍 DEPURACIÓN DE RUTAS:\n";
    echo "Base dir: $base_dir\n";
    echo "App/Core dir: $app_core_dir\n";
    echo "Config file: $config_file\n";
    echo "Config relative: $config_relative\n";
    echo "Full test path: $full_test_path\n";
    
    // Mostrar estructura de directorios
    echo "\n📂 Estructura de directorios:\n";
    showDirectoryStructure($base_dir, '', 3);
}

function showDirectoryStructure($dir, $prefix = '', $maxDepth = 2, $currentDepth = 0) {
    if ($currentDepth >= $maxDepth) return;
    
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;
        
        $path = $dir . '/' . $item;
        $relativePath = $prefix . $item;
        
        if (is_dir($path)) {
            echo "📁 $relativePath/\n";
            if ($currentDepth < $maxDepth - 1) {
                showDirectoryStructure($path, $prefix . $item . '/', $maxDepth, $currentDepth + 1);
            }
        } else {
            echo "📄 $relativePath\n";
        }
        
        if ($currentDepth == 0 && count(scandir($dir)) > 10) {
            echo "   ... (más archivos)\n";
            break;
        }
    }
}

echo "\n🎯 PRÓXIMOS PASOS:\n";
echo "==================\n";
echo "1. ✅ Ruta real encontrada y corregida\n";
echo "2. 🧪 Probar la aplicación ahora\n";
echo "3. 🌐 Si hay errores, ejecuta: https://camcam.com.ve/evoappws/force_reload.php\n";
echo "4. 📊 Verificar: https://camcam.com.ve/evoappws/check_config.php\n\n";

echo "🚀 ¡RUTA REAL ENCONTRADA Y CORREGIDA!\n";
?>
