<?php
// Corregir rutas de configuración - usar solo la correcta
echo "🔧 CORRIGIENDO RUTAS DE CONFIGURACIÓN\n\n";

echo "📂 Análisis de archivos config.php:\n";

$config_files = [
    __DIR__ . '/config/config.php' => 'La correcta (la tuya)',
    __DIR__ . '/config.php' => 'La innecesaria (creada por script)'
];

$correct_config = null;
$wrong_config = null;

foreach ($config_files as $path => $description) {
    if (file_exists($path)) {
        echo "✅ $description: $path\n";
        echo "📏 Tamaño: " . filesize($path) . " bytes\n";
        echo "📅 Modificado: " . date('Y-m-d H:i:s', filemtime($path)) . "\n";
        
        // Verificar contenido
        $content = file_get_contents($path);
        if (strpos($content, 'grupoecc_evoappWS') !== false) {
            echo "🎯 ¡Esta es la configuración correcta!\n";
            $correct_config = $path;
        } else {
            echo "⚠️ Esta podría ser incorrecta\n";
            $wrong_config = $path;
        }
        echo "\n";
    } else {
        echo "❌ $description: $path (no existe)\n\n";
    }
}

echo "🎯 ACCIONES RECOMENDADAS:\n\n";

if ($correct_config && $wrong_config) {
    echo "✅ Configuración correcta encontrada: $correct_config\n";
    echo "❌ Configuración innecesaria: $wrong_config\n\n";
    
    echo "🔧 OPCIÓN 1: Eliminar la innecesaria\n";
    echo "=====================================\n";
    echo "Puedes eliminar: $wrong_config\n";
    echo "Ya que la correcta está en: $correct_config\n\n";
    
    echo "🔧 OPCIÓN 2: Verificar qué usa el sistema\n";
    echo "=====================================\n";
    
    // Verificar qué incluye index.php
    $index_file = __DIR__ . '/index.php';
    if (file_exists($index_file)) {
        $index_content = file_get_contents($index_file);
        
        echo "📖 Análisis de index.php:\n";
        
        if (strpos($index_content, 'config/config.php') !== false) {
            echo "✅ index.php incluye: config/config.php (CORRECTO)\n";
        } elseif (strpos($index_content, 'config.php') !== false) {
            echo "⚠️ index.php incluye: config.php (Podría ser incorrecto)\n";
        } else {
            echo "❌ index.php no incluye configuración\n";
        }
    }
    
    // Verificar qué incluye App.php
    $app_file = __DIR__ . '/app/Core/App.php';
    if (file_exists($app_file)) {
        $app_content = file_get_contents($app_file);
        
        echo "\n📖 Análisis de App.php:\n";
        
        if (strpos($app_content, 'config/config.php') !== false) {
            echo "✅ App.php incluye: config/config.php (CORRECTO)\n";
        } elseif (strpos($app_content, 'config.php') !== false) {
            echo "⚠️ App.php incluye: config.php (Podría ser incorrecto)\n";
        } else {
            echo "❌ App.php no incluye configuración\n";
        }
    }
    
    echo "\n🔧 PASOS A SEGUIR:\n";
    echo "==================\n";
    echo "1. ✅ Mantén solo: $correct_config\n";
    echo "2. ❌ Elimina: $wrong_config\n";
    echo "3. 🔧 Asegúrate que todos los archivos incluyan: config/config.php\n";
    echo "4. 🔄 Reinicia el servidor\n";
    
} elseif ($correct_config && !$wrong_config) {
    echo "✅ Solo existe la configuración correcta: $correct_config\n";
    echo "🎉 ¡Perfecto! No hay duplicación\n";
} elseif (!$correct_config && $wrong_config) {
    echo "❌ Solo existe la configuración incorrecta: $wrong_config\n";
    echo "🔧 Necesitas crear la correcta o mover esta a la ubicación correcta\n";
} else {
    echo "❌ No se encontró ninguna configuración\n";
}

echo "\n🎯 SOLUCIÓN DEFINITIVA:\n";
echo "====================\n";
echo "1. ✅ Tu configuración correcta está en: /config/config.php\n";
echo "2. ❌ Elimina /config.php si existe (es innecesaria)\n";
echo "3. 🔧 Asegúrate que todos los archivos incluyan: config/config.php\n";
echo "4. 🔄 Reinicia el servidor para cargar cambios\n\n";

echo "📝 Para eliminar el archivo innecesario (si tienes acceso SSH):\n";
echo "rm " . __DIR__ . "/config.php\n\n";

echo "🌐 Para verificar después de corregir:\n";
echo "https://camcam.com.ve/evoappws/check_config.php\n";
?>
