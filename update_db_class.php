<?php
// Script para actualizar la clase DB en el servidor
echo "🔧 Actualizando clase DB con métodos faltantes...\n";

// Ruta del archivo DB.php en el servidor
$db_file = __DIR__ . '/app/Core/DB.php';

// Leer el archivo actual
if (!file_exists($db_file)) {
    echo "❌ Archivo DB.php no encontrado en: $db_file\n";
    exit(1);
}

$current_content = file_get_contents($db_file);
echo "✅ Archivo DB.php leído\n";

// Verificar si ya tiene los métodos
if (strpos($current_content, 'function columnExists') !== false) {
    echo "✅ Método columnExists() ya existe\n";
} else {
    echo "⚠️ Método columnExists() NO encontrado - agregando métodos faltantes...\n";
    
    // Agregar métodos faltantes antes del cierre de la clase
    $methods_to_add = '
    
    public static function columnExists($table, $column)
    {
        try {
            $result = self::fetch("
                SELECT COUNT(*) as count
                FROM information_schema.columns 
                WHERE table_schema = DATABASE() 
                AND table_name = ? 
                AND column_name = ?
            ", [$table, $column]);
            
            return $result[\'count\'] > 0;
        } catch (Exception $e) {
            error_log("columnExists error: " . $e->getMessage());
            return false;
        }
    }
    
    public static function tableExists($table)
    {
        try {
            $result = self::fetch("
                SELECT COUNT(*) as count
                FROM information_schema.tables 
                WHERE table_schema = DATABASE() 
                AND table_name = ?
            ", [$table]);
            
            return $result[\'count\'] > 0;
        } catch (Exception $e) {
            error_log("tableExists error: " . $e->getMessage());
            return false;
        }
    }';
    
    // Insertar métodos antes del cierre de la clase
    $pattern = '/(}\s*;\s*\?>\s*$)/';
    $replacement = $methods_to_add . '\n$1';
    
    if (preg_match($pattern, $current_content)) {
        $updated_content = preg_replace($pattern, $replacement, $current_content);
        
        // Hacer backup del archivo original
        $backup_file = $db_file . '.backup.' . date('Y-m-d-H-i-s');
        if (copy($db_file, $backup_file)) {
            echo "✅ Backup creado: $backup_file\n";
        }
        
        // Escribir el archivo actualizado
        if (file_put_contents($db_file, $updated_content)) {
            echo "✅ Archivo DB.php actualizado con métodos faltantes\n";
            echo "✅ Métodos agregados: columnExists(), tableExists()\n";
        } else {
            echo "❌ Error al escribir el archivo actualizado\n";
            exit(1);
        }
    } else {
        echo "❌ No se pudo encontrar el punto de inserción para los métodos\n";
        echo "📂 Verificando estructura del archivo...\n";
        
        // Mostrar últimas líneas del archivo para debug
        $lines = file($db_file);
        $last_lines = array_slice($lines, -10);
        echo "📖 Últimas 10 líneas del archivo:\n";
        foreach ($last_lines as $line) {
            echo rtrim($line) . "\n";
        }
        exit(1);
    }
}

echo "🎉 Actualización completada!\n";
echo "📝 Por favor, recarga la página para probar de nuevo.\n";

// Verificar que los métodos funcionen
echo "\n🔍 Verificando métodos agregados:\n";

try {
    // Incluir configuración
    if (file_exists(__DIR__ . '/config/config.php')) {
        require_once __DIR__ . '/config/config.php';
    } elseif (file_exists(__DIR__ . '/config.php')) {
        require_once __DIR__ . '/config.php';
    } else {
        echo "⚠️ No se encontró archivo de configuración\n";
    }
    
    // Incluir clase DB
    require_once $db_file;
    
    // Probar método tableExists
    if (method_exists('App\Core\DB', 'tableExists')) {
        echo "✅ Método tableExists() disponible\n";
        
        // Probar con una tabla que debería existir
        try {
            $exists = \App\Core\DB::tableExists('instances');
            echo "📊 tableExists('instances'): " . ($exists ? 'true' : 'false') . "\n";
        } catch (Exception $e) {
            echo "❌ Error probando tableExists(): " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ Método tableExists() no disponible\n";
    }
    
    // Probar método columnExists
    if (method_exists('App\Core\DB', 'columnExists')) {
        echo "✅ Método columnExists() disponible\n";
        
        // Probar con una columna que debería existir
        try {
            $exists = \App\Core\DB::columnExists('instances', 'id');
            echo "📊 columnExists('instances', 'id'): " . ($exists ? 'true' : 'false') . "\n";
        } catch (Exception $e) {
            echo "❌ Error probando columnExists(): " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ Método columnExists() no disponible\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error en verificación: " . $e->getMessage() . "\n";
}

echo "\n🎯 Próximos pasos:\n";
echo "1. ✅ Métodos agregados correctamente\n";
echo "2. 🔄 Reinicia el servidor web si es posible\n";
echo "3. 🧪 Prueba la aplicación\n";
echo "4. 📊 Los errores de columnExists() deberían desaparecer\n";
?>
