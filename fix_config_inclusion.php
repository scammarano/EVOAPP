<?php
// Corregir inclusión de configuración - usar la ruta correcta
echo "🔧 CORRIGIENDO INCLUSIÓN DE CONFIGURACIÓN\n\n";

echo "📂 Verificando archivos de configuración:\n";

$config_correcta = __DIR__ . '/config/config.php';
$config_incorrecta = __DIR__ . '/config.php';

echo "✅ Configuración correcta: $config_correcta\n";
if (file_exists($config_correcta)) {
    echo "📏 Tamaño: " . filesize($config_correcta) . " bytes\n";
    echo "📅 Modificado: " . date('Y-m-d H:i:s', filemtime($config_correcta)) . "\n";
} else {
    echo "❌ No existe\n";
}

echo "❌ Configuración incorrecta: $config_incorrecta\n";
if (file_exists($config_incorrecta)) {
    echo "📏 Tamaño: " . filesize($config_incorrecta) . " bytes\n";
    echo "📅 Modificado: " . date('Y-m-d H:i:s', filemtime($config_incorrecta)) . "\n";
} else {
    echo "✅ Eliminada correctamente\n";
}

echo "\n📖 Analizando archivos que incluyen configuración:\n";

// Analizar index.php
$index_file = __DIR__ . '/index.php';
if (file_exists($index_file)) {
    $index_content = file_get_contents($index_file);
    
    echo "\n📄 index.php:\n";
    
    if (strpos($index_content, 'config/config.php') !== false) {
        echo "✅ Incluye: config/config.php (CORRECTO)\n";
    } elseif (strpos($index_content, 'config.php') !== false) {
        echo "⚠️ Incluye: config.php (INCORRECTO - necesita corrección)\n";
        
        // Corregir index.php
        echo "🔧 Corrigiendo index.php...\n";
        
        $backup_file = $index_file . '.backup.' . date('Y-m-d-H-i-s');
        copy($index_file, $backup_file);
        
        // Reemplazar 'config.php' con 'config/config.php'
        $corrected_content = str_replace("require_once 'config.php';", "require_once 'config/config.php';", $index_content);
        $corrected_content = str_replace('require_once "config.php";', 'require_once "config/config.php";', $corrected_content);
        $corrected_content = str_replace("require_once './config.php';", "require_once './config/config.php';", $corrected_content);
        $corrected_content = str_replace('require_once "./config.php";', 'require_once "./config/config.php";', $corrected_content);
        
        if (file_put_contents($index_file, $corrected_content)) {
            echo "✅ index.php corregido para incluir config/config.php\n";
            echo "💾 Backup creado: $backup_file\n";
        } else {
            echo "❌ Error al corregir index.php\n";
        }
    } else {
        echo "❌ No incluye configuración\n";
    }
} else {
    echo "❌ index.php no encontrado\n";
}

// Analizar App.php
$app_file = __DIR__ . '/app/Core/App.php';
if (file_exists($app_file)) {
    $app_content = file_get_contents($app_file);
    
    echo "\n📄 app/Core/App.php:\n";
    
    if (strpos($app_content, 'config/config.php') !== false) {
        echo "✅ Incluye: config/config.php (CORRECTO)\n";
    } elseif (strpos($app_content, 'config.php') !== false) {
        echo "⚠️ Incluye: config.php (INCORRECTO - necesita corrección)\n";
        
        // Corregir App.php
        echo "🔧 Corrigiendo App.php...\n";
        
        $backup_file = $app_file . '.backup.' . date('Y-m-d-H-i-s');
        copy($app_file, $backup_file);
        
        // Reemplazar 'config.php' con 'config/config.php'
        $corrected_content = str_replace("require_once 'config.php';", "require_once '../config/config.php';", $app_content);
        $corrected_content = str_replace('require_once "config.php";', 'require_once "../config/config.php";', $corrected_content);
        $corrected_content = str_replace("require_once '../config.php';", "require_once '../config/config.php';", $corrected_content);
        $corrected_content = str_replace('require_once "../config.php";', 'require_once "../config/config.php";', $corrected_content);
        
        if (file_put_contents($app_file, $corrected_content)) {
            echo "✅ App.php corregido para incluir ../config/config.php\n";
            echo "💾 Backup creado: $backup_file\n";
        } else {
            echo "❌ Error al corregir App.php\n";
        }
    } else {
        echo "❌ No incluye configuración\n";
        
        // Agregar inclusión si no tiene
        echo "🔧 Agregando inclusión a App.php...\n";
        
        $backup_file = $app_file . '.backup.' . date('Y-m-d-H-i-s');
        copy($app_file, $backup_file);
        
        // Agregar al principio del archivo después de <?php
        $lines = file($app_file);
        $new_content = "<?php\nrequire_once '../config/config.php';\n";
        
        // Omitir la primera línea (<?php) y agregar el resto
        for ($i = 1; $i < count($lines); $i++) {
            $new_content .= $lines[$i];
        }
        
        if (file_put_contents($app_file, $new_content)) {
            echo "✅ Inclusión agregada a App.php\n";
            echo "💾 Backup creado: $backup_file\n";
        } else {
            echo "❌ Error al agregar inclusión a App.php\n";
        }
    }
} else {
    echo "❌ App.php no encontrado\n";
}

echo "\n🎯 VERIFICACIÓN FINAL:\n";
echo "====================\n";

// Verificar que la configuración correcta funcione
if (file_exists($config_correcta)) {
    echo "✅ Probando configuración correcta...\n";
    
    try {
        require_once $config_correcta;
        
        if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
            echo "✅ Constantes BD definidas correctamente:\n";
            echo "📊 BD: " . DB_NAME . "\n";
            echo "👤 Usuario: " . DB_USER . "\n";
            echo "🌐 Host: " . DB_HOST . "\n";
        } else {
            echo "❌ Constantes BD no definidas en config/config.php\n";
        }
    } catch (Exception $e) {
        echo "❌ Error al cargar configuración: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Configuración correcta no encontrada\n";
}

echo "\n🎯 ACCIONES REQUERIDAS:\n";
echo "======================\n";
echo "1. ✅ Archivos corregidos para usar config/config.php\n";
echo "2. 🔄 Reinicia el servidor web (Apache/Nginx)\n";
echo "3. 🧪 Ejecuta check_config.php para verificar\n";
echo "4. ✅ Debería mostrar constantes BD definidas\n\n";

echo "🌐 Para verificar después de corregir:\n";
echo "https://camcam.com.ve/evoappws/check_config.php\n\n";

echo "🚀 Después de reiniciar el servidor:\n";
echo "✅ Las constantes BD deberían estar definidas\n";
echo "✅ El sistema debería funcionar correctamente\n";
echo "✅ Todos los errores deberían desaparecer\n";
?>
