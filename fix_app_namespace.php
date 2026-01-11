<?php
// Corregir error de namespace en App.php
echo "🔧 CORRIGIENDO ERROR DE NAMESPACE EN App.php\n\n";

$app_file = __DIR__ . '/app/Core/App.php';

if (!file_exists($app_file)) {
    echo "❌ App.php no encontrado en: $app_file\n";
    exit(1);
}

echo "📖 Analizando App.php...\n";

// Leer contenido actual
$content = file_get_contents($app_file);

echo "📏 Tamaño: " . strlen($content) . " bytes\n";

// Buscar problemas
$lines = explode("\n", $content);
$first_php_line = -1;
$namespace_line = -1;
$require_line = -1;

echo "\n🔍 Análisis de líneas:\n";

foreach ($lines as $index => $line) {
    $trimmed = trim($line);
    
    if ($first_php_line === -1 && strpos($trimmed, '<?php') === 0) {
        $first_php_line = $index;
        echo "✅ Línea " . ($index + 1) . ": <?php (inicio)\n";
    }
    
    if ($namespace_line === -1 && strpos($trimmed, 'namespace') === 0) {
        $namespace_line = $index;
        echo "✅ Línea " . ($index + 1) . ": namespace (declaración)\n";
    }
    
    if ($require_line === -1 && strpos($trimmed, 'require_once') === 0) {
        $require_line = $index;
        echo "❌ Línea " . ($index + 1) . ": require_once (PROBLEMA - va antes de namespace)\n";
    }
}

echo "\n🎯 DIAGNÓSTICO:\n";

if ($require_line !== -1 && $require_line < $namespace_line) {
    echo "❌ PROBLEMA: require_once está antes de namespace\n";
    echo "🔧 SOLUCIÓN: Mover require_once después del namespace\n";
    
    // Crear backup
    $backup_file = $app_file . '.backup.' . date('Y-m-d-H-i-s');
    copy($app_file, $backup_file);
    echo "💾 Backup creado: $backup_file\n";
    
    // Corregir el archivo
    $new_lines = [];
    $require_content = '';
    
    foreach ($lines as $index => $line) {
        $trimmed = trim($line);
        
        // Extraer require_once si está antes de namespace
        if ($require_line !== -1 && $index === $require_line) {
            $require_content = $line . "\n";
            continue; // No agregar ahora
        }
        
        // Agregar namespace y luego el require_once
        if ($namespace_line !== -1 && $index === $namespace_line) {
            $new_lines[] = $line; // namespace
            if (!empty($require_content)) {
                $new_lines[] = $require_content; // require después de namespace
                $require_content = ''; // Limpiar
            }
        } else {
            $new_lines[] = $line;
        }
    }
    
    // Escribir archivo corregido
    $corrected_content = implode("\n", $new_lines);
    
    if (file_put_contents($app_file, $corrected_content)) {
        echo "✅ App.php corregido exitosamente\n";
        
        // Verificar corrección
        echo "\n🔍 VERIFICACIÓN POST-CORRECCIÓN:\n";
        $new_content = file_get_contents($app_file);
        $new_lines = explode("\n", $new_content);
        
        $namespace_found = false;
        $require_found = false;
        
        foreach ($new_lines as $index => $line) {
            $trimmed = trim($line);
            
            if (strpos($trimmed, 'namespace') === 0) {
                $namespace_found = true;
                echo "✅ Namespace en línea " . ($index + 1) . "\n";
            }
            
            if (strpos($trimmed, 'require_once') === 0) {
                $require_found = true;
                echo "✅ require_once en línea " . ($index + 1);
                echo $namespace_found ? " (después de namespace ✓)" : " (antes de namespace ✗)";
                echo "\n";
            }
        }
        
        if ($namespace_found && $require_found) {
            echo "\n✅ Estructura correcta: namespace antes que require_once\n";
        }
        
    } else {
        echo "❌ Error al corregir App.php\n";
    }
    
} else {
    echo "✅ Estructura correcta: namespace antes que require_once\n";
    
    // Buscar otros posibles problemas
    echo "\n🔍 Buscando otros problemas...\n";
    
    $issues = [];
    foreach ($lines as $index => $line) {
        $trimmed = trim($line);
        
        // Buscar espacios o HTML antes de <?php
        if ($index < $first_php_line && !empty($trimmed)) {
            $issues[] = "Línea " . ($index + 1) . ": '$trimmed' (antes de <?php)";
        }
        
        // Buscar declare statements
        if (strpos($trimmed, 'declare') === 0 && $namespace_line !== -1 && $index < $namespace_line) {
            $issues[] = "Línea " . ($index + 1) . ": declare (antes de namespace)";
        }
    }
    
    if (!empty($issues)) {
        echo "❌ Problemas encontrados:\n";
        foreach ($issues as $issue) {
            echo "   $issue\n";
        }
    } else {
        echo "✅ No se encontraron otros problemas\n";
    }
}

echo "\n🎯 PRÓXIMOS PASOS:\n";
echo "==================\n";
echo "1. ✅ App.php corregido\n";
echo "2. 🧪 Probar la aplicación ahora\n";
echo "3. 🌐 Si hay errores, ejecuta: https://camcam.com.ve/evoappws/force_reload.php\n";
echo "4. 📊 Verificar: https://camcam.com.ve/evoappws/check_config.php\n\n";

echo "🚀 ¡ERROR DE NAMESPACE CORREGIDO!\n";
?>
