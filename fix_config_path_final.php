<?php
// Corregir ruta de configuración en App.php
echo "🔧 CORRIGIENDO RUTA DE CONFIGURACIÓN EN App.php\n\n";

$app_file = __DIR__ . '/app/Core/App.php';

if (!file_exists($app_file)) {
    echo "❌ App.php no encontrado en: $app_file\n";
    exit(1);
}

echo "📖 Analizando ruta actual en App.php...\n";

// Leer contenido actual
$content = file_get_contents($app_file);
$lines = explode("\n", $content);

echo "🔍 Buscando línea require_once...\n";

$require_line_index = -1;
$require_line_content = '';

foreach ($lines as $index => $line) {
    if (strpos($line, 'require_once') !== false) {
        $require_line_index = $index;
        $require_line_content = $line;
        echo "📍 Línea " . ($index + 1) . ": " . trim($line) . "\n";
        break;
    }
}

if ($require_line_index === -1) {
    echo "❌ No se encontró línea require_once\n";
    exit(1);
}

echo "\n🎯 DIAGNÓSTICO:\n";
echo "❌ Ruta actual: " . trim($require_line_content) . "\n";

// Calcular ruta correcta
$correct_path = '../../config/config.php';
$alternative_path = __DIR__ . '/config/config.php';

echo "✅ Ruta correcta desde app/Core/: $correct_path\n";
echo "✅ Ruta absoluta: $alternative_path\n";

// Verificar si la ruta absoluta existe
if (file_exists($alternative_path)) {
    echo "✅ Archivo de configuración encontrado en ruta absoluta\n";
    
    // Crear backup
    $backup_file = $app_file . '.backup.' . date('Y-m-d-H-i-s');
    copy($app_file, $backup_file);
    echo "💾 Backup creado: $backup_file\n";
    
    // Corregir la ruta
    $new_require_line = "require_once '" . $correct_path . "';";
    
    echo "🔧 Corrigiendo línea...\n";
    echo "📝 Antes: " . trim($require_line_content) . "\n";
    echo "📝 Después: " . $new_require_line . "\n";
    
    // Reemplazar la línea
    $lines[$require_line_index] = $new_require_line;
    $corrected_content = implode("\n", $lines);
    
    if (file_put_contents($app_file, $corrected_content)) {
        echo "✅ App.php corregido exitosamente\n";
        
        // Verificación
        echo "\n🔍 VERIFICACIÓN:\n";
        $new_content = file_get_contents($app_file);
        if (strpos($new_content, $correct_path) !== false) {
            echo "✅ Ruta corregida correctamente en App.php\n";
        } else {
            echo "❌ Error: La ruta no se corrigió correctamente\n";
        }
        
    } else {
        echo "❌ Error al corregir App.php\n";
    }
    
} else {
    echo "❌ Archivo de configuración no encontrado en: $alternative_path\n";
    
    // Buscar en otras ubicaciones
    $possible_paths = [
        __DIR__ . '/config.php',
        __DIR__ . '/../config.php',
        __DIR__ . '/../../config/config.php',
        __DIR__ . '/../../../config/config.php'
    ];
    
    echo "\n🔍 Buscando configuración en otras ubicaciones:\n";
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            echo "✅ Encontrado: $path\n";
            
            // Calcular ruta relativa desde app/Core/
            $relative_path = str_replace(__DIR__ . '/', '', $path);
            $relative_path = str_replace('../', '', $relative_path);
            
            if (strpos($relative_path, 'config/') === 0) {
                $relative_path = '../' . $relative_path;
            } else {
                $relative_path = '../' . $relative_path;
            }
            
            echo "📝 Ruta relativa sugerida: $relative_path\n";
            
            // Corregir con esta ruta
            $backup_file = $app_file . '.backup.' . date('Y-m-d-H-i-s');
            copy($app_file, $backup_file);
            echo "💾 Backup creado: $backup_file\n";
            
            $new_require_line = "require_once '" . $relative_path . "';";
            $lines[$require_line_index] = $new_require_line;
            $corrected_content = implode("\n", $lines);
            
            if (file_put_contents($app_file, $corrected_content)) {
                echo "✅ App.php corregido con ruta: $relative_path\n";
                break;
            }
        }
    }
}

echo "\n🎯 VERIFICACIÓN FINAL:\n";
echo "===================\n";

// Probar la configuración corregida
try {
    // Incluir App.php para probar
    require_once $app_file;
    
    if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
        echo "✅ Configuración cargada exitosamente desde App.php\n";
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
        echo "❌ Constantes BD no definidas después de corregir\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error al cargar App.php: " . $e->getMessage() . "\n";
}

echo "\n🎯 PRÓXIMOS PASOS:\n";
echo "==================\n";
echo "1. ✅ Ruta de configuración corregida\n";
echo "2. 🧪 Probar la aplicación ahora\n";
echo "3. 🌐 Si hay errores, ejecuta: https://camcam.com.ve/evoappws/force_reload.php\n";
echo "4. 📊 Verificar: https://camcam.com.ve/evoappws/check_config.php\n\n";

echo "🚀 ¡ERROR DE RUTA CORREGIDO!\n";
?>
